<?php

namespace App\Http\Controllers;

use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\StudentReport;
use App\Models\UmmiRecord;
use App\Services\AcademicCalendarService;
use App\Services\HafalanProgressService;
use App\Services\QuranLineTargetService;
use App\Services\StudentProgressService;
use App\Services\UmmiProgressService;
use App\Support\AcademicYear;
use App\Support\AyahLabel;
use App\Support\Signatures;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentReportController extends Controller
{
    public function __construct(
        protected StudentProgressService $progressService,
        protected QuranLineTargetService $positionCheck
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $visibleStudentQuery = $this->progressService->visibleStudentQuery($user);

        if ($request->filled('class_room_id')) {
            $visibleStudentQuery->where('class_room_id', $request->integer('class_room_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $visibleStudentQuery->where('name', 'like', "%{$search}%");
        }

        $students = $visibleStudentQuery->with(['classRoom'])->orderBy('name')->paginate(15)->withQueryString();

        $classRooms = ClassRoom::query()->orderBy('name')->get();

        return view('reports.digital-report-index', compact('students', 'classRooms'));
    }

    public function show(Student $student, Request $request)
    {
        $user = $request->user();

        // Authorize
        $canView = $this->progressService->visibleStudentQuery($user)
            ->where('id', $student->id)
            ->exists();
        abort_unless($canView, 403);

        $student->load(['classRoom.program', 'teacher.user', 'parents.user']);

        [$academicYear, $semester, $term] = $this->requestedPeriod($request);

        $report = StudentReport::firstOrCreate([
            'student_id' => $student->id,
            'academic_year' => $academicYear,
            'term' => $term,
        ], [
            'semester' => $semester,
            'status' => 'draft',
        ]);

        $data = $this->getReportData($student, $academicYear, $semester, null, $term);
        $data['report'] = $report;

        $totalSetoran = HafalanRecordSurah::whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))->where('status', 'passed')->count();
        $totalMurajaah = MurajaahRecord::where('student_id', $student->id)->where('status', 'passed')->count();

        $canEditNotes = $user->hasAnyRole(['super_admin', 'admin', 'teacher']) && $report->status !== 'locked' && ! $report->isLocked();

        return view('reports.digital-report', array_merge(
            $data,
            [
                'totalSetoran' => $totalSetoran,
                'totalMurajaah' => $totalMurajaah,
                'canEditNotes' => $canEditNotes,
                'academicYearOptions' => AcademicYear::options($academicYear),
            ]
        ));
    }

    public function update(Request $request, Student $student)
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['super_admin', 'admin', 'teacher']), 403);

        $validated = $request->validate([
            'academic_year' => ['required', 'string', fn ($attribute, $value, $fail) => AcademicYear::isValid($value) ?: $fail('Tahun ajaran tidak dipakai.')],
            'term' => 'required|integer|in:'.implode(',', array_keys(self::REPORT_PERIODS)),
            'teacher_notes' => 'nullable|string',
            'tahfizh_target_term' => 'nullable|string|max:255',
            'status' => 'required|string|in:draft,published,locked',
        ]);

        $period = [
            'student_id' => $student->id,
            'academic_year' => $validated['academic_year'],
            'term' => (int) $validated['term'],
        ];
        if (StudentReport::where($period)->whereNotNull('locked_at')->exists()) {
            return redirect()->back()->with('error', 'Rapor periode ini sudah dikunci; catatan tidak dapat diubah.');
        }

        $report = StudentReport::updateOrCreate($period, [
            'semester' => self::semesterOfPeriod((int) $validated['term']),
            'teacher_notes' => $validated['teacher_notes'],
            'tahfizh_target_term' => $validated['tahfizh_target_term'] ?? null,
            'status' => $validated['status'],
            'created_by' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Catatan rapor digital berhasil diperbarui.');
    }

    public function print(Student $student, Request $request)
    {
        $user = $request->user();
        abort_unless(self::canPrint($user), 403, 'Akses cetak rapor tidak diizinkan untuk akun ini.');

        $canView = $this->progressService->visibleStudentQuery($user)
            ->where('id', $student->id)
            ->exists();
        abort_unless($canView, 403);

        [$academicYear, $semester, $term] = $this->requestedPeriod($request);

        $locked = StudentReport::where(['student_id' => $student->id, 'academic_year' => $academicYear, 'term' => $term])
            ->whereNotNull('locked_at')
            ->first();
        $sheet = $locked?->snapshot ?? $this->buildSheet($this->getReportData($student, $academicYear, $semester, null, $term));

        return view('reports.digital-report-print', [
            'sheet' => $sheet,
            'lockedAt' => $locked?->locked_at,
            'signatureUris' => self::signatureUris([$sheet]),
        ]);
    }

    public function printClass(ClassRoom $classRoom, Request $request)
    {
        $user = $request->user();
        abort_unless(self::canPrint($user), 403, 'Akses cetak rapor kelas tidak diizinkan untuk akun ini.');

        [$academicYear, $semester, $term] = $this->requestedPeriod($request);

        // Periode terkunci: cetak isi yang dibekukan untuk santri kelas ini saat dikunci,
        // walau santrinya sudah pindah/naik kelas.
        $lockedReports = StudentReport::where(['academic_year' => $academicYear, 'term' => $term, 'locked_class_room_id' => $classRoom->id])
            ->whereNotNull('locked_at')
            ->whereIn('student_id', $this->progressService->visibleStudentQuery($user)->select('id'))
            ->get();

        if ($lockedReports->isNotEmpty()) {
            $sheets = $lockedReports->pluck('snapshot')->sortBy(fn ($sheet) => $sheet['student']['name'])->values()->all();
            $lockedAt = $lockedReports->max('locked_at');
        } else {
            $students = $this->progressService->visibleStudentQuery($user)
                ->where('class_room_id', $classRoom->id)
                ->with(['classRoom.program', 'teacher.user', 'parents.user'])
                ->orderBy('name')
                ->get();
            abort_if($students->isEmpty(), 404, 'Tidak ada murid di kelas ini yang dapat Anda akses.');

            $batchContext = $this->batchContext($students->pluck('id')->all(), $academicYear, $term);
            $sheets = $students
                ->map(fn ($student) => $this->buildSheet($this->getReportData($student, $academicYear, $semester, $batchContext, $term)))
                ->all();
            $lockedAt = null;
        }

        return view('reports.digital-report-class-print', [
            'classRoom' => $classRoom,
            'sheets' => $sheets,
            'academicYear' => $academicYear,
            'term' => $term,
            'lockedAt' => $lockedAt,
            'signatureUris' => self::signatureUris($sheets),
        ]);
    }

    /**
     * Kunci rapor satu kelas untuk satu periode: isi rapor tiap santri dibekukan apa adanya.
     * Santri yang sudah terkunci (mis. lewat kelas lamanya) tidak ditimpa.
     */
    public function lockClass(ClassRoom $classRoom, Request $request)
    {
        $validated = $request->validate([
            'academic_year' => 'required|string',
            'term' => 'required|integer|in:'.implode(',', array_keys(self::REPORT_PERIODS)),
        ]);
        $academicYear = $validated['academic_year'];
        abort_unless(AcademicYear::isValid($academicYear), 422, 'Tahun ajaran tidak dipakai.');
        $term = (int) $validated['term'];
        $semester = self::semesterOfPeriod($term);
        $period = self::REPORT_PERIODS[$term];

        // Titimangsa rapor = tanggal BLP; jangan bekukan rapor yang tanggalnya masih kosong.
        $reportDate = self::reportDate($academicYear, $semester, $term);
        if (! $reportDate['is_set']) {
            return redirect()->back()->with('error', "Tanggal BLP {$reportDate['exam']} untuk {$period} {$academicYear} belum diisi. Isi dulu di Pengaturan Rapor sebelum mengunci.");
        }

        $students = Student::where('class_room_id', $classRoom->id)
            ->with(['classRoom.program', 'teacher.user', 'parents.user'])
            ->orderBy('name')
            ->get();
        if ($students->isEmpty()) {
            return redirect()->back()->with('error', "Kelas {$classRoom->name} belum punya santri.");
        }

        $batchContext = $this->batchContext($students->pluck('id')->all(), $academicYear, $term);
        $archivedSignatures = collect(Signatures::OFFICIALS)
            ->map(fn ($official, $key) => Signatures::archive(Signatures::officialFile($key)))
            ->all();

        $locked = 0;
        DB::transaction(function () use ($students, $batchContext, $archivedSignatures, $academicYear, $semester, $term, $classRoom, $request, &$locked) {
            foreach ($students as $student) {
                $report = StudentReport::firstOrNew(['student_id' => $student->id, 'academic_year' => $academicYear, 'term' => $term]);
                if ($report->isLocked()) {
                    continue;
                }

                $sheet = $this->buildSheet($this->getReportData($student, $academicYear, $semester, $batchContext, $term));
                foreach ($archivedSignatures as $key => $path) {
                    $sheet['signatories'][$key]['signature'] = $path;
                }

                $report->fill([
                    'semester' => $semester,
                    'status' => $report->status ?? 'draft',
                    'snapshot' => $sheet,
                    'locked_at' => now(),
                    'locked_by' => $request->user()->id,
                    'locked_class_room_id' => $classRoom->id,
                ])->save();
                $locked++;
            }
        });

        return redirect()->back()->with('success', "Rapor {$period} {$academicYear} kelas {$classRoom->name} dikunci ({$locked} santri).");
    }

    /**
     * Buka kunci (khusus Super Admin): simpanan dibuang, rapor kembali dihitung dari data terkini.
     */
    public function unlockClass(ClassRoom $classRoom, Request $request)
    {
        abort_unless($request->user()->hasRole('super_admin'), 403, 'Hanya Super Admin yang dapat membuka kunci rapor.');

        $validated = $request->validate([
            'academic_year' => 'required|string',
            'term' => 'required|integer|in:'.implode(',', array_keys(self::REPORT_PERIODS)),
        ]);

        $count = StudentReport::where([
            'academic_year' => $validated['academic_year'],
            'term' => (int) $validated['term'],
            'locked_class_room_id' => $classRoom->id,
        ])->update(['snapshot' => null, 'locked_at' => null, 'locked_by' => null, 'locked_class_room_id' => null]);

        $period = self::REPORT_PERIODS[(int) $validated['term']];

        return redirect()->back()->with('success', "Kunci rapor {$period} {$validated['academic_year']} kelas {$classRoom->name} dibuka ({$count} santri).");
    }

    /**
     * Data seluruh santri sekelas diambil sekaligus (bukan per santri) untuk cetak/kunci per kelas.
     */
    private function batchContext(array $studentIds, string $academicYear, int $term): array
    {
        return [
            'reports' => StudentReport::whereIn('student_id', $studentIds)
                ->where('academic_year', $academicYear)
                ->where('term', $term)
                ->get()
                ->keyBy('student_id'),
            'hafalanRecords' => HafalanRecord::flattenSurahs(
                HafalanRecord::with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                    ->whereIn('student_id', $studentIds)
                    ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                    ->latest('submitted_at')
                    ->latest()
                    ->get()
            )->groupBy('student_id'),
            'murajaahRecords' => MurajaahRecord::with('surah')
                ->whereIn('student_id', $studentIds)
                ->where('status', 'passed')
                ->latest('reviewed_at')
                ->latest()
                ->get()
                ->groupBy('student_id'),
            'targetRecords' => HafalanTarget::with('surah')
                ->whereIn('student_id', $studentIds)
                ->orderBy('target_date', 'asc')
                ->get()
                ->groupBy('student_id'),
            'ummiRecords' => UmmiRecord::with('surahs.surah')
                ->whereIn('student_id', $studentIds)
                ->latest('tanggal')
                ->latest()
                ->get()
                ->groupBy('student_id'),
            'adabRecords' => AdabRecord::whereIn('student_id', $studentIds)
                ->get()
                ->groupBy('student_id'),
            'violations' => StudentPoint::violations()
                ->whereIn('student_id', $studentIds)
                ->get()
                ->groupBy('student_id'),
            'rewards' => StudentPoint::whereIn('student_id', $studentIds)
                ->where('type', 'reward')
                ->get()
                ->groupBy('student_id'),
        ];
    }

    /**
     * Periode yang diminta: `term` (1-4), atau `semester` lama (triwulan berjalan/terakhirnya),
     * atau periode aktif di Pengaturan Rapor. Tahun ajaran bawaan juga dari Pengaturan Rapor.
     *
     * @return array{0: string, 1: int, 2: int} [tahun ajaran, semester, term]
     */
    private function requestedPeriod(Request $request): array
    {
        // Tahun ajaran sebelum AcademicYear::FIRST (mis. 2025/2026) tidak dipakai: diganti tahun aktif.
        $academicYear = (string) $request->input('academic_year');
        $academicYear = AcademicYear::isValid($academicYear) ? $academicYear : AcademicYear::active();
        $term = $request->integer('term');

        if (! isset(self::REPORT_PERIODS[$term])) {
            $term = $request->filled('semester')
                ? self::resolveTanseTerm($academicYear, $request->integer('semester') === 2 ? 2 : 1)['term']
                : self::activePeriod();
        }

        return [$academicYear, self::semesterOfPeriod($term), $term];
    }

    /**
     * Periode rapor aktif tahun ajaran aktif, otomatis dari tanggal BLP: periode pertama yang
     * tanggal BLP-nya belum lewat (hari BLP itu sendiri masih periode tersebut). Lewat semua = Semester II.
     */
    public static function activePeriod(): int
    {
        $today = now()->startOfDay();
        foreach (self::periodDeadlines(AcademicYear::active()) as $term => $deadline) {
            if ($today->lte($deadline)) {
                return $term;
            }
        }

        return 4;
    }

    /**
     * Isi rapor cetak yang sudah siap tampil (teks, nilai, nama pejabat, berkas tanda tangan).
     * Bentuk ini yang disimpan apa adanya saat periode dikunci, jadi hanya berisi data biasa.
     */
    private function buildSheet(array $data): array
    {
        $letterhead = self::letterhead();
        $tanseTerm = $data['tanseTerm'];

        return [
            'academic_year' => $data['academicYear'],
            'semester' => $data['semester'],
            'term' => $tanseTerm['term'],
            'period_label' => self::REPORT_PERIODS[$tanseTerm['term']],
            'student' => [
                'id' => $data['student']->id,
                'name' => $data['student']->name,
                'number' => $data['student']->student_number,
                'class' => $data['student']->classRoom?->name,
                'program' => $data['student']->classRoom?->program?->name,
            ],
            // Satu baris triwulan rapor (lihat tahfizhTermRow()); nilai hanya ditampilkan untuk Kelas 10/Ummi.
            // Predikat nilai memakai skala yang sama dengan Nilai Adab (Mumtaz >= 90, Jayyid Jiddan >= 80, ...).
            'tahfizh' => $data['tahfizhTerm'] + [
                'final_score' => $data['tahfizhScore']['final_score'],
                'final_predicate' => Setting::getAdabGradeLabel(Setting::getAdabGrade((float) $data['tahfizhScore']['final_score'])),
            ],
            'adab' => [
                'categories' => collect($data['adabCategories'])->pluck('title')->values()->all(),
                'grade' => $data['adabGrade'],
                'score' => round($data['avgTotal']),
                'grade_label' => $data['adabGradeLabel'],
                'description' => self::adabDescription((float) $data['avgTotal']),
            ],
            'tanse' => [
                'term_label' => $tanseTerm['label'],
                'reward_points' => $data['rewards']->sum('points'),
                'violation_points' => $data['violations']->sum('points'),
                'grade' => $data['tanseGrade'],
                'notes' => $data['autoTanseNotes'],
            ],
            'teacher_notes' => $data['report']?->teacher_notes,
            'letterhead' => $letterhead['header'] + ['date' => $data['reportDate']['date']],
            'signatories' => $letterhead['signatories'],
        ];
    }

    /**
     * Judul kop & pejabat penanda tangan dari Pengaturan Rapor.
     */
    private static function letterhead(): array
    {
        $official = fn (string $key, string $title, string $defaultNik) => [
            'title' => $title,
            'name' => Setting::get(Signatures::OFFICIALS[$key]['name'], Signatures::OFFICIALS[$key]['default']),
            'nik' => Setting::get(Signatures::OFFICIALS[$key]['nik'], $defaultNik),
            'signature' => Signatures::officialFile($key),
        ];

        return [
            'header' => [
                'main_title' => Setting::get('report_main_title', 'LAPORAN TAHFIZH, ADAB DAN TANSE'),
                'school_name' => Setting::get('report_school_name', 'SMA ISLAM AL AZHAR 7 SUKOHARJO'),
                'city' => Setting::get('report_city', 'Sukoharjo'),
            ],
            'signatories' => [
                'coord_tahfizh' => $official('coord_tahfizh', 'Koordinator Tahfizh', '15.06.0393'),
                'coord_keagamaan' => $official('coord_keagamaan', 'Koordinator Keagamaan', '15.06.0393'),
                'headmaster' => $official('headmaster', Setting::get('report_headmaster_title', 'Kepala SMA Islam Al Azhar 7 Sukoharjo'), '08.04.0160'),
                'coord_tanse' => $official('coord_tanse', 'Koordinator Tanse', '15.06.0393'),
            ],
        ];
    }

    /**
     * Data URI tanda tangan untuk semua rapor yang dicetak, per berkas (dibaca sekali saja).
     *
     * @return array<string, ?string>
     */
    private static function signatureUris(array $sheets): array
    {
        return collect($sheets)
            ->flatMap(fn ($sheet) => collect($sheet['signatories'])->pluck('signature'))
            ->filter()
            ->unique()
            ->mapWithKeys(fn ($path) => [$path => Signatures::dataUri($path)])
            ->all();
    }

    public static function adabDescription(float $avgTotal): string
    {
        return match (true) {
            $avgTotal >= 90 => 'Sangat baik (Mumtaz), konsisten beribadah kepada Allah, berperilaku sopan terhadap sesama teman, menerapkan adab belajar secara tertib dan disiplin, serta menjaga kebersihan lingkungan dengan sangat baik.',
            $avgTotal >= 80 => 'Baik sekali (Jayyid Jiddan), rutin melaksanakan ibadah harian, bersikap sopan kepada teman, tertib dalam mengikuti pelajaran, dan turut menjaga kebersihan lingkungan dengan baik.',
            $avgTotal >= 70 => 'Baik (Jayyid), menunjukkan kesopanan kepada guru dan teman, mengikuti kegiatan belajar dengan tertib, dan menjaga kebersihan diri serta lingkungan.',
            $avgTotal >= 60 => 'Cukup (Maqbul), sudah berusaha membiasakan adab harian dengan cukup baik, namun masih memerlukan pengawasan dan motivasi berkala agar lebih konsisten.',
            default => "Kurang (Dha'if), memerlukan pembinaan moral intensif serta bimbingan khusus baik di sekolah maupun asrama untuk meningkatkan kedisiplinan dan adab sehari-hari.",
        };
    }

    /**
     * Deskripsi Tanse bawaan per predikat; bisa diubah di Pengaturan Rapor (lihat tanseRules()).
     */
    public const TANSE_NOTES = [
        'A' => 'Alhamdulillah ananda sudah Sangat Baik dalam menerapkan budaya sekolah, disiplin, bertanggung jawab, santun, peduli, dan menjadi teladan bagi lingkungan sekitar. Semoga tetap istiqomah dalam menjalankan pembiasaan budaya sekolah dan berprestasi',
        'B' => 'Alhamdulillah ananda sudah Baik dalam menerapkan budaya sekolah dan masih memerlukan bimbingan serta pembiasaan dalam kedisiplinan, tanggung jawab, dan sikap santun. Semoga bisa istiqomah dalam menjalankan pembiasaan budaya sekolah.',
        'C' => 'Alhamdulillah ananda sudah Cukup Baik dalam menerapkan budaya sekolah, namun masih memerlukan bimbingan, pendampingan, pembiasaan dan konsistensi dalam kedisiplinan, tanggung jawab, dan sikap santun.',
    ];

    public const TANSE_DEFAULT_A_MIN = 90;

    public const TANSE_DEFAULT_B_MIN = 80;

    /**
     * Batas nilai & deskripsi predikat Tanse dari Pengaturan Rapor (default di atas).
     *
     * @return array{a_min: int, b_min: int, notes: array{A: string, B: string, C: string}}
     */
    public static function tanseRules(): array
    {
        $notes = json_decode((string) Setting::get('report_tanse_notes'), true) ?: [];

        return [
            'a_min' => (int) Setting::get('report_tanse_a_min', self::TANSE_DEFAULT_A_MIN),
            'b_min' => (int) Setting::get('report_tanse_b_min', self::TANSE_DEFAULT_B_MIN),
            'notes' => collect(self::TANSE_NOTES)->map(fn ($default, $grade) => trim((string) ($notes[$grade] ?? '')) ?: $default)->all(),
        ];
    }

    /**
     * Predikat Tanse dari skor (100 - poin pelanggaran triwulan): A >= batas A, B >= batas B, selain itu C.
     */
    public static function tanseGrade(int $score): string
    {
        $rules = self::tanseRules();

        return match (true) {
            $score >= $rules['a_min'] => 'A',
            $score >= $rules['b_min'] => 'B',
            default => 'C',
        };
    }

    public static function tanseNote(string $grade): string
    {
        return self::tanseRules()['notes'][$grade] ?? self::TANSE_NOTES['C'];
    }

    /**
     * Triwulan yang dipakai bagian Tanse. Semester 1 = triwulan 1 (Jul-Sep) & 2 (Okt-Des),
     * semester 2 = triwulan 3 (Jan-Mar) & 4 (Apr-Jun) -- sama dengan Laporan Triwulan.
     * Tanpa pilihan yang valid: triwulan yang sedang berjalan bila masih di semester itu,
     * selain itu triwulan terakhir semester tersebut.
     *
     * @return array{term: int, terms: array<int, string>, label: string, start: Carbon, end: Carbon}
     */
    /**
     * Cetak/unduh rapor: semua yang boleh melihat rapor, kecuali Pendamping Adab (lihat saja).
     */
    public static function canPrint($user): bool
    {
        return $user !== null && ! $user->hasAnyRole(['student', 'parent', 'pendamping_adab']);
    }

    /**
     * Tanggal BLP (titimangsa rapor) per semester: ASTS & ASAS (semester 1), ASTS & ASAT
     * (semester 2). Diatur per tahun ajaran di Pengaturan Rapor.
     */
    public const BLP_EXAMS = [
        1 => ['1_asts' => 'ASTS', '1_asas' => 'ASAS'],
        2 => ['2_asts' => 'ASTS', '2_asat' => 'ASAT'],
    ];

    /**
     * Tanggal BLP tersimpan untuk satu tahun ajaran: ['1_asts' => 'Y-m-d'|null, ...].
     *
     * @return array<string, string|null>
     */
    public static function blpDates(string $academicYear): array
    {
        $saved = json_decode((string) Setting::get(self::blpSettingKey($academicYear)), true) ?: [];
        $keys = array_merge(array_keys(self::BLP_EXAMS[1]), array_keys(self::BLP_EXAMS[2]));

        return collect($keys)->mapWithKeys(fn ($key) => [$key => $saved[$key] ?? null])->all();
    }

    public static function blpSettingKey(string $academicYear): string
    {
        return 'report_blp_dates_'.str_replace('/', '-', $academicYear);
    }

    /**
     * BLP penutup tiap periode rapor: Tengah Semester = ASTS, Semester I = ASAS, Semester II = ASAT.
     */
    public const PERIOD_BLP = [1 => '1_asts', 2 => '1_asas', 3 => '2_asts', 4 => '2_asat'];

    /**
     * Titimangsa rapor = tanggal BLP periodenya. Belum diatur = null (rapor menampilkan titik-titik).
     *
     * @return array{date: ?string, exam: string, is_set: bool}
     */
    public static function reportDate(string $academicYear, int $semester, int $term): array
    {
        $key = self::PERIOD_BLP[$term] ?? self::PERIOD_BLP[1];
        $saved = self::blpDates($academicYear)[$key];

        return [
            'date' => $saved ? Carbon::parse($saved)->locale('id')->translatedFormat('d F Y') : null,
            'exam' => self::BLP_EXAMS[(int) $key[0]][$key],
            'is_set' => $saved !== null,
        ];
    }

    /**
     * Hari terakhir tiap periode: tanggal BLP-nya, atau akhir triwulan bila BLP belum diatur.
     *
     * @return array<int, Carbon>
     */
    public static function periodDeadlines(string $academicYear): array
    {
        $blp = self::blpDates($academicYear);

        return collect(self::PERIOD_BLP)->mapWithKeys(fn ($key, $term) => [
            $term => $blp[$key]
                ? Carbon::parse($blp[$key])->startOfDay()
                : self::resolveTanseTerm($academicYear, self::semesterOfPeriod($term), $term)['end']->startOfDay(),
        ])->all();
    }

    /**
     * Periode rapor aktif (Pengaturan Rapor), dipetakan ke triwulan: tengah semester =
     * triwulan pertama semester itu, akhir semester = triwulan kedua.
     */
    public const REPORT_PERIODS = [
        1 => 'Tengah Semester I',
        2 => 'Semester I',
        3 => 'Tengah Semester II',
        4 => 'Semester II',
    ];

    /**
     * Nomor term rapor (= periode 1-4) dalam angka romawi, untuk baris "Term" di identitas rapor.
     */
    public const TERM_ROMAN = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];

    public static function semesterOfPeriod(int $period): int
    {
        return $period >= 3 ? 2 : 1;
    }

    public static function resolveTanseTerm(string $academicYear, int $semester, ?int $requested = null): array
    {
        $startYear = (int) explode('/', $academicYear)[0];
        $all = [
            1 => ['Triwulan 1 (Jul - Sep)', Carbon::create($startYear, 7, 1)],
            2 => ['Triwulan 2 (Okt - Des)', Carbon::create($startYear, 10, 1)],
            3 => ['Triwulan 3 (Jan - Mar)', Carbon::create($startYear + 1, 1, 1)],
            4 => ['Triwulan 4 (Apr - Jun)', Carbon::create($startYear + 1, 4, 1)],
        ];
        $semesterTerms = $semester === 2 ? [3, 4] : [1, 2];

        $term = in_array($requested, $semesterTerms, true) ? $requested : null;
        foreach ($semesterTerms as $candidate) {
            $term ??= now()->between($all[$candidate][1], $all[$candidate][1]->copy()->addMonths(2)->endOfMonth()) ? $candidate : null;
        }
        $term ??= end($semesterTerms);

        return [
            'term' => $term,
            'terms' => collect($semesterTerms)->mapWithKeys(fn ($t) => [$t => $all[$t][0]])->all(),
            'label' => $all[$term][0],
            'start' => $all[$term][1]->copy()->startOfDay(),
            'end' => $all[$term][1]->copy()->addMonths(2)->endOfMonth(),
        ];
    }

    /**
     * Satu baris tabel Tahfizh rapor untuk triwulan rapor, dihitung sama dengan tabel Term/Indeks
     * Laporan Triwulan: Kelas 10/Ummi = posisi Jilid|Halaman & Surah|Ayat, tuntas dari posisi buku;
     * selain itu target guru triwulan + posisi setoran terjauh, tuntas dari baris (termBreakdown).
     * Ayat cukup ayat terakhir.
     *
     * @param  array{start: Carbon, end: Carbon}  $raporTerm
     * @param  Collection|null  $allTargets  semua target murid (cetak per kelas), null = query
     */
    private function tahfizhTermRow(Student $student, array $raporTerm, $allTargets, $hafalanAll, $ummiAll): array
    {
        $start = $raporTerm['start'];
        $end = $raporTerm['end']->copy()->endOfDay();

        $termTargets = ($allTargets ?? HafalanTarget::with('surah')->where('student_id', $student->id)->get())
            ->filter(fn ($t) => $t->target_date && Carbon::parse($t->target_date)->between($start, $end))
            ->sortByDesc('target_date')
            ->values();
        $hafalanUntilEnd = $hafalanAll->filter(fn ($h) => $h->submitted_at && Carbon::parse($h->submitted_at)->lte($end))->values();
        $notes = $termTargets->first(fn ($t) => filled($t->notes))?->notes;

        if ($student->tahfizh_level === 'ummi') {
            $ummiUntilEnd = $ummiAll->filter(fn ($u) => $u->tanggal && Carbon::parse($u->tanggal)->lte($end))->values();
            $position = app(UmmiProgressService::class)->termPosition($termTargets, $ummiUntilEnd, $hafalanUntilEnd);

            return [
                'layout' => 'ummi',
                'target' => ['jilid' => $position['target_jilid'], 'halaman' => $position['target_halaman'], 'surah' => $position['target_surah'], 'ayat' => AyahLabel::end($position['target_ayat'])],
                'capaian' => ['jilid' => $position['capaian_jilid'], 'halaman' => $position['capaian_halaman'], 'surah' => $position['capaian_surah'], 'ayat' => AyahLabel::end($position['capaian_ayat'])],
                'lines' => null,
                'completed' => $position['is_tuntas'],
                'notes' => $notes,
            ];
        }

        $capaian = $this->positionCheck->latestByPosition($hafalanUntilEnd, $student->hafalan_direction);
        $breakdown = TargetRules::linesForLevel($student->tahfizh_level) === null ? null : app(HafalanProgressService::class)->termBreakdown(
            $student,
            $termTargets->filter(fn ($t) => $t->surah),
            app(AcademicCalendarService::class)->termMonths($start),
            now()->min($raporTerm['end'])
        );
        $target = $breakdown['target'] ?? $termTargets->first(fn ($t) => $t->surah);

        return [
            'layout' => 'reguler',
            'target' => ['surah' => $target?->surah?->name_latin ?? '-', 'ayat' => $target ? AyahLabel::end($target->ayah) : '-'],
            'capaian' => ['surah' => $capaian?->surah?->name_latin ?? '-', 'ayat' => $capaian ? AyahLabel::end($capaian->ayah_end) : '-'],
            'lines' => $breakdown ? ['achieved' => $breakdown['evaluation']['achieved_lines'] + 0, 'target' => (int) $breakdown['evaluation']['target_lines']] : null,
            'completed' => (bool) ($breakdown['evaluation']['reached'] ?? false),
            'notes' => $notes,
        ];
    }

    private function getReportData(Student $student, string $academicYear, int $semester, ?array $batch = null, ?int $term = null): array
    {
        if (! $student->relationLoaded('classRoom')) {
            $student->load(['classRoom.program', 'teacher.user', 'parents.user']);
        }

        // Tahfizh
        $progress = $this->progressService->calculate($student);

        if ($batch) {
            $studentHafalanAll = $batch['hafalanRecords']->get($student->id, collect());
            $hafalanRecords = $studentHafalanAll->take(5);
            $murajaahRecords = $batch['murajaahRecords']->get($student->id, collect())->take(5);
            $targetRecords = $batch['targetRecords']->get($student->id, collect())->take(5);
            $report = $batch['reports']->get($student->id);
            $studentUmmiAll = $batch['ummiRecords']->get($student->id, collect());
            $adabRecords = $batch['adabRecords']->get($student->id, collect());
            $violations = $batch['violations']->get($student->id, collect());
            $rewards = $batch['rewards']->get($student->id, collect());
        } else {
            $studentHafalanAll = HafalanRecord::flattenSurahs(
                HafalanRecord::with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                    ->where('student_id', $student->id)
                    ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                    ->latest('submitted_at')
                    ->latest()
                    ->get()
            );
            $hafalanRecords = $studentHafalanAll->take(5);
            $murajaahRecords = MurajaahRecord::with('surah')->where('student_id', $student->id)->where('status', 'passed')->latest('reviewed_at')->latest()->limit(5)->get();
            $targetRecords = HafalanTarget::with('surah')->where('student_id', $student->id)->orderBy('target_date', 'asc')->limit(5)->get();
            $report = StudentReport::where([
                'student_id' => $student->id,
                'academic_year' => $academicYear,
                'term' => self::resolveTanseTerm($academicYear, $semester, $term)['term'],
            ])->first();
            $studentUmmiAll = UmmiRecord::with('surahs.surah')->where('student_id', $student->id)->latest('tanggal')->latest()->get();
            $adabRecords = AdabRecord::where('student_id', $student->id)->get();
            $violations = StudentPoint::violations()->where('student_id', $student->id)->get();
            $rewards = StudentPoint::where('student_id', $student->id)->where('type', 'reward')->get();
        }

        foreach ($targetRecords as $target) {
            $matchingRecord = $studentHafalanAll
                ->where('surah_id', $target->surah_id)
                ->where('ayah_end', '>=', $target->ayah)
                ->first();

            if (! $matchingRecord) {
                $matchingRecord = $studentHafalanAll
                    ->where('surah_id', $target->surah_id)
                    ->first();
            }

            $target->matching_record = $matchingRecord;
        }

        $tahfizhScore = Setting::calculateTahfizhScore($student);

        // Compute Tahfizh Level and targets
        $tahfizhLevelLabel = $student->tahfizh_level_label;
        $termTargetText = '';
        if ($report && $report->tahfizh_target_term) {
            $termTargetText = $report->tahfizh_target_term;
        } else {
            $classRoomName = $student->classRoom?->name ?? '';
            $classRoomLevel = $student->classRoom?->level ?? '';
            $isGrade10 = (bool) (
                (preg_match('/\bX\b/i', $classRoomName) && ! preg_match('/\b(XI|XII)\b/i', $classRoomName))
                || preg_match('/\b10\b/i', $classRoomName)
                || preg_match('/^X[-_\s]?E/i', $classRoomName)
                || preg_match('/kelas\s*(X|10)/i', $classRoomName)
                || (preg_match('/\bX\b/i', $classRoomLevel) && ! preg_match('/\b(XI|XII)\b/i', $classRoomLevel))
                || preg_match('/\b10\b/i', $classRoomLevel)
            ) && ! preg_match('/\b(XI|XII|11|12)\b/i', $classRoomName);
            $isUmmiProgram = $isGrade10 || $student->tahfizh_level === 'ummi';

            if ($isUmmiProgram) {
                $termTargetText = 'Metode Bacaan Ummi (Target diisi Musyrif)';
            } else {
                // Target triwulan rapor: pertemuan aktif x baris per level (sama dengan Target Triwulan &
                // Laporan Triwulan), capaian = baris setoran lulus, plus target surah & ayat guru bila ada.
                $raporTerm = self::resolveTanseTerm($academicYear, $semester, $term);
                $termMonths = app(AcademicCalendarService::class)->termMonths($raporTerm['start']);
                $termTargets = HafalanTarget::query()
                    ->with('surah')
                    ->where('student_id', $student->id)
                    ->whereBetween('target_date', [$raporTerm['start']->toDateString(), $raporTerm['end']->toDateString().' 23:59:59'])
                    ->get()
                    ->filter(fn ($t) => $t->surah);
                $breakdown = app(HafalanProgressService::class)->termBreakdown($student, $termTargets, $termMonths, now()->min($raporTerm['end']));
                $levelBaris = TargetRules::linesForLevel($student->tahfizh_level);
                $fixedTermLines = TargetRules::termLinesForStudent($student);
                $termMeetings = $levelBaris ? intdiv((int) $breakdown['evaluation']['target_lines'], $levelBaris) : 0;

                $termTargetText = $fixedTermLines !== null
                    ? "Target {$raporTerm['label']}: {$breakdown['evaluation']['target_lines']} baris per triwulan · Capaian ".($breakdown['evaluation']['achieved_lines'] + 0).' baris'
                    : "Target {$raporTerm['label']}: {$levelBaris} baris x {$termMeetings} pertemuan = "
                        .$breakdown['evaluation']['target_lines'].' baris · Capaian '.($breakdown['evaluation']['achieved_lines'] + 0).' baris';
                if ($breakdown['target']) {
                    $termTargetText .= " · Target hafalan QS. {$breakdown['target']->surah->name_latin} ayat {$breakdown['target']->ayah}";
                }
            }
        }

        // Compute Capaian Terakhir
        $latestCapaianText = '';
        $latestCapaianNotes = '';

        if ($student->tahfizh_level === 'ummi') {
            $latestUmmiRecord = $studentUmmiAll->first();

            if ($latestUmmiRecord) {
                $parts = [];
                if ($latestUmmiRecord->ummi_jilid) {
                    $parts[] = $latestUmmiRecord->ummi_jilid.($latestUmmiRecord->ummi_halaman ? ' Hal. '.$latestUmmiRecord->ummi_halaman : '');
                }

                $surahParts = [];
                foreach ($latestUmmiRecord->surahs as $surahEntry) {
                    $surahParts[] = 'Hafalan QS. '.($surahEntry->surah?->name_latin ?? '').($surahEntry->hafalan_ayah ? ' Ayat '.$surahEntry->hafalan_ayah : '');
                }
                if (! empty($surahParts)) {
                    $parts[] = implode(', ', $surahParts);
                }

                $latestCapaianText = implode(', ', $parts);
                if ($latestUmmiRecord->nilai) {
                    $latestCapaianText .= ' [Nilai: '.$latestUmmiRecord->nilai.']';
                }
                $latestCapaianNotes = (string) $latestUmmiRecord->keterangan;
            } else {
                $latestCapaianText = 'Belum ada catatan UMMI.';
            }
        } else {
            $latestHafalan = null;
            if (isset($isUmmiProgram) && $isUmmiProgram) {
                $latestHafalan = $studentHafalanAll
                    ->filter(fn ($sq) => ($sq->surah?->number ?? 0) >= 78 && ($sq->surah?->number ?? 0) <= 114)
                    ->sortBy(fn ($r) => $r->surah?->number ?? 114)
                    ->first();
            }

            if (! $latestHafalan) {
                $latestHafalan = $this->positionCheck->latestByPosition($studentHafalanAll, $student->hafalan_direction);
            }

            if ($latestHafalan) {
                $latestCapaianText = 'QS. '.($latestHafalan->surah?->name_latin ?? '').' (Ayat '.$latestHafalan->ayah_end.')';
                $latestCapaianNotes = $latestHafalan->notes;
            } else {
                $latestCapaianText = 'Belum ada data setoran.';
            }
        }

        $tahfizhTerm = $this->tahfizhTermRow(
            $student,
            self::resolveTanseTerm($academicYear, $semester, $term),
            $batch ? $batch['targetRecords']->get($student->id, collect()) : null,
            $studentHafalanAll,
            $studentUmmiAll
        );

        // Dynamic Adab Evaluation & Scores
        $adabCategories = Setting::getAdabQuestions();
        $adabCategoryScores = [];

        foreach ($adabCategories as $catIdx => $cat) {
            $total = 0;
            $count = 0;
            foreach ($adabRecords as $r) {
                if (! empty($r->answers) && isset($r->answers["cat_{$catIdx}"])) {
                    $catAns = $r->answers["cat_{$catIdx}"];
                    foreach ($catAns as $ans) {
                        $total += $ans ? 1 : 0;
                        $count++;
                    }
                }
            }
            $adabCategoryScores[$catIdx] = $count > 0 ? round(($total / $count) * 100, 1) : 0;
        }

        $thisYear = (int) now()->format('Y');
        $thisMonth = (int) now()->format('n');
        $adabScoreData = Setting::calculateAdabScore($student->id, $thisYear, $thisMonth);

        $avgAttendanceRate = $adabScoreData['attendance_rate'];
        $avgMentorScore = $adabScoreData['mentor_score'];
        $avgTotal = $adabScoreData['final_score'];
        $adabGrade = $adabScoreData['grade'];
        $adabGradeLabel = $adabScoreData['grade_label'];

        // Tanse (Ketahanan Sekolah): hanya poin dalam triwulan terpilih.
        $tanseTerm = self::resolveTanseTerm($academicYear, $semester, $term);
        $reportDate = self::reportDate($academicYear, $semester, $tanseTerm['term']);
        $inTanseTerm = fn ($point) => $point->date?->between($tanseTerm['start'], $tanseTerm['end']);
        $violations = $violations->filter($inTanseTerm)->values();
        $rewards = $rewards->filter($inTanseTerm)->values();

        $totalViolationPoints = $violations->sum('points');
        $latenessCount = $violations->where('type', 'lateness')->count();
        $attributeCount = $violations->where('type', 'attribute')->count();
        $tatibCount = $violations->where('type', 'violation')->count();

        $tanseScore = max(0, 100 - $totalViolationPoints);
        $tanseGrade = self::tanseGrade($tanseScore);
        $autoTanseNotes = self::tanseNote($tanseGrade);

        return compact(
            'student',
            'academicYear',
            'semester',
            'progress',
            'hafalanRecords',
            'murajaahRecords',
            'targetRecords',
            'tahfizhScore',
            'tahfizhTerm',
            'tahfizhLevelLabel',
            'termTargetText',
            'latestCapaianText',
            'latestCapaianNotes',
            'adabCategories',
            'adabCategoryScores',
            'avgAttendanceRate',
            'avgMentorScore',
            'avgTotal',
            'adabGrade',
            'adabGradeLabel',
            'violations',
            'rewards',
            'totalViolationPoints',
            'latenessCount',
            'attributeCount',
            'tatibCount',
            'autoTanseNotes',
            'tanseScore',
            'tanseGrade',
            'tanseTerm',
            'reportDate',
            'report'
        );
    }

    public function settings(Request $request)
    {
        $classRooms = ClassRoom::orderBy('name')->get();
        $academicYear = AcademicYear::active();
        $reportPeriod = self::activePeriod();
        $reportPeriodUntil = self::periodDeadlines($academicYear)[$reportPeriod];
        $semester = self::semesterOfPeriod($reportPeriod);

        $showTahfizh = Setting::get('report_show_tahfizh', '1') === '1';
        $showAdab = Setting::get('report_show_adab', '1') === '1';
        $showTanse = Setting::get('report_show_tanse', '1') === '1';

        // Template Settings
        $reportMainTitle = Setting::get('report_main_title', 'LAPORAN TAHFIZH, ADAB DAN TANSE');
        $reportSchoolName = Setting::get('report_school_name', 'SMA ISLAM AL AZHAR 7 SUKOHARJO');
        $reportCity = Setting::get('report_city', 'Sukoharjo');

        $coordTahfizhName = Setting::get('report_coord_tahfizh_name', 'Zainal Arifin, S.Pd');
        $coordTahfizhNik = Setting::get('report_coord_tahfizh_nik', '15.06.0393');

        $coordKeagamaanName = Setting::get('report_coord_keagamaan_name', 'Rifqi Ihsan, S.Pd., Gr.');
        $coordKeagamaanNik = Setting::get('report_coord_keagamaan_nik', '15.06.0393');

        $headmasterTitle = Setting::get('report_headmaster_title', 'Kepala SMA Islam Al Azhar 7 Sukoharjo');
        $headmasterName = Setting::get('report_headmaster_name', 'Moh Pandoyo, S.Si., M.Pd., Gr.');
        $headmasterNik = Setting::get('report_headmaster_nik', '08.04.0160');

        $coordTanseName = Setting::get('report_coord_tanse_name', 'Yatim Hermawan, S.E., S.Kom');
        $coordTanseNik = Setting::get('report_coord_tanse_nik', '15.06.0393');

        $blpDates = self::blpDates($academicYear);
        $tanseRules = self::tanseRules();

        // Periode untuk cetak & kunci per kelas (bisa periode lama); bawaan = periode aktif.
        $printYear = AcademicYear::isValid($request->input('print_year')) ? $request->input('print_year') : $academicYear;
        $printTerm = isset(self::REPORT_PERIODS[$request->integer('print_term')]) ? $request->integer('print_term') : $reportPeriod;
        $printPeriodLabel = self::REPORT_PERIODS[$printTerm];
        $academicYearOptions = AcademicYear::options($printYear);
        $classLocks = StudentReport::where('academic_year', $printYear)
            ->where('term', $printTerm)
            ->whereNotNull('locked_class_room_id')
            ->groupBy('locked_class_room_id')
            ->selectRaw('locked_class_room_id, count(*) as total, max(locked_at) as locked_at')
            ->get()
            ->keyBy('locked_class_room_id');
        $canUnlock = $request->user()->hasRole('super_admin');

        // Tanda tangan pejabat (sama dengan Pengaturan Umum); hanya Super Admin yang boleh mengganti.
        $officialSignatures = Signatures::officialPreviews();
        $canEditSignatures = $request->user()->hasRole('super_admin');

        return view('reports.digital-report-settings', compact(
            'classRooms', 'academicYear', 'semester', 'reportPeriod', 'reportPeriodUntil', 'showTahfizh', 'showAdab', 'showTanse', 'blpDates', 'tanseRules',
            'reportMainTitle', 'reportSchoolName', 'reportCity',
            'coordTahfizhName', 'coordTahfizhNik',
            'coordKeagamaanName', 'coordKeagamaanNik',
            'headmasterTitle', 'headmasterName', 'headmasterNik',
            'coordTanseName', 'coordTanseNik',
            'officialSignatures', 'canEditSignatures',
            'printYear', 'printTerm', 'printPeriodLabel', 'academicYearOptions', 'classLocks', 'canUnlock'
        ));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'academic_year' => ['required', function ($attribute, $value, $fail) {
                if (! AcademicYear::isValid((string) $value)) {
                    $fail('Tahun ajaran harus berformat YYYY/YYYY dan paling awal '.AcademicYear::FIRST.'.');
                }
            }],
            'blp_dates' => 'nullable|array', 'blp_dates.*' => 'nullable|date',
            'tanse_a_min' => 'nullable|integer|between:1,100',
            'tanse_b_min' => 'nullable|integer|between:0,100|lt:tanse_a_min',
            'tanse_notes' => 'nullable|array', 'tanse_notes.*' => 'nullable|string|max:1000',
            'signatures' => 'nullable|array', 'signatures.*' => Signatures::UPLOAD_RULES,
            'reset_signatures' => 'nullable|array', 'reset_signatures.*' => 'in:'.implode(',', array_keys(Signatures::OFFICIALS)),
        ], ['tanse_b_min.lt' => 'Batas predikat B harus lebih kecil dari batas predikat A.']);

        // Tanda tangan pejabat: hanya Super Admin (unggahan dari Admin diabaikan).
        if ($request->user()->hasRole('super_admin')) {
            Signatures::saveOfficialUploads($request);
        }

        if ($request->filled('tanse_a_min')) {
            Setting::set('report_tanse_a_min', (string) $request->integer('tanse_a_min'));
            Setting::set('report_tanse_b_min', (string) $request->integer('tanse_b_min'));
            Setting::set('report_tanse_notes', json_encode(collect(self::TANSE_NOTES)
                ->map(fn ($default, $grade) => trim((string) $request->input("tanse_notes.{$grade}")) ?: $default)
                ->all()));
        }

        // Tanggal BLP disimpan untuk tahun ajaran yang sedang diatur di form ini.
        $academicYear = (string) $request->input('academic_year');
        $blp = collect(self::blpDates($academicYear))
            ->map(fn ($old, $key) => $request->input("blp_dates.{$key}") ?: null)
            ->all();
        Setting::set(self::blpSettingKey($academicYear), json_encode($blp));

        Setting::set('academic_year', $academicYear);
        // Semester ikut periode aktif yang ditentukan tanggal BLP.
        Setting::set('semester', (string) self::semesterOfPeriod(self::activePeriod()));
        Setting::set('report_show_tahfizh', $request->has('report_show_tahfizh') ? '1' : '0');
        Setting::set('report_show_adab', $request->has('report_show_adab') ? '1' : '0');
        Setting::set('report_show_tanse', $request->has('report_show_tanse') ? '1' : '0');

        // Template Settings
        Setting::set('report_main_title', $request->input('report_main_title', 'LAPORAN TAHFIZH, ADAB DAN TANSE'));
        Setting::set('report_school_name', $request->input('report_school_name', 'SMA ISLAM AL AZHAR 7 SUKOHARJO'));
        Setting::set('report_city', $request->input('report_city', 'Sukoharjo'));

        Setting::set('report_coord_tahfizh_name', $request->input('report_coord_tahfizh_name', 'Zainal Arifin, S.Pd'));
        Setting::set('report_coord_tahfizh_nik', $request->input('report_coord_tahfizh_nik', '15.06.0393'));

        Setting::set('report_coord_keagamaan_name', $request->input('report_coord_keagamaan_name', 'Rifqi Ihsan, S.Pd., Gr.'));
        Setting::set('report_coord_keagamaan_nik', $request->input('report_coord_keagamaan_nik', '15.06.0393'));

        Setting::set('report_headmaster_title', $request->input('report_headmaster_title', 'Kepala SMA Islam Al Azhar 7 Sukoharjo'));
        Setting::set('report_headmaster_name', $request->input('report_headmaster_name', 'Moh Pandoyo, S.Si., M.Pd., Gr.'));
        Setting::set('report_headmaster_nik', $request->input('report_headmaster_nik', '08.04.0160'));

        Setting::set('report_coord_tanse_name', $request->input('report_coord_tanse_name', 'Yatim Hermawan, S.E., S.Kom'));
        Setting::set('report_coord_tanse_nik', $request->input('report_coord_tanse_nik', '15.06.0393'));

        return redirect()->back()->with('success', 'Pengaturan Rapor Digital & Template Cetak berhasil disimpan.');
    }
}
