<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\HafalanTarget;
use App\Models\UmmiRecord;
use App\Services\HafalanTargetAutoCompletionService;
use App\Services\UmmiProgressService;
use App\Support\UmmiBook;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Koreksi jilid Ummi yang terlanjur tersimpan di luar daftar buku (Jilid 1-3, Gharib, Tajwid),
 * mis. "Jilid 4" dari input teks bebas lama:
 * - Salah tulis jilid yang sebenarnya valid ("jilid 2", "2", "Ghoroib") -> dirapikan.
 * - Nomor jilid di luar 1-3 -> ikut jilid pertemuan SEBELUMNYA murid itu; kalau belum ada
 *   pertemuan sebelumnya, ikut pertemuan sesudahnya di bulan yang sama.
 * - Selain itu (mis. "Al-Qur'an") -> tidak diubah, dikoreksi guru lewat form edit.
 * - Koreksi khusus satu halaqoh (--halaqoh + --dari + --jadi) mengalahkan aturan di atas, mis.
 *   "Jilid 4" di halaqoh Ustadzah Isti sebenarnya "Jilid 3".
 *
 * Halaman tidak diubah. Setiap perubahan dicatat di Audit Log (jilid lama & baru).
 * --hitung-ulang-target: target Ummi yang tercapai gara-gara jilid salah diaktifkan ulang lalu
 * dinilai ulang dari posisi yang sudah dikoreksi.
 */
class FixInvalidUmmiJilid extends Command
{
    protected $signature = 'tad:perbaiki-jilid-ummi
        {--dry-run : Tampilkan usulan koreksi tanpa mengubah data}
        {--force : Jalankan tanpa pertanyaan konfirmasi}
        {--halaqoh= : Koreksi khusus: sebagian nama guru halaqoh, mis. "Isti"}
        {--dari= : Koreksi khusus: jilid tersimpan persis, mis. "Jilid 4"}
        {--jadi= : Koreksi khusus: jilid pengganti, mis. "Jilid 3"}
        {--hitung-ulang-target : Aktifkan ulang target Ummi tercapai yang terdampak lalu nilai ulang}';

    /** @var array{halaqoh: string, dari: string, jadi: string}|null */
    private ?array $override = null;

    protected $description = 'Koreksi jilid Ummi tidak valid (mis. Jilid 4) mengikuti jilid pertemuan sebelumnya.';

    public function handle(): int
    {
        $override = array_filter([
            'halaqoh' => trim((string) $this->option('halaqoh')),
            'dari' => trim((string) $this->option('dari')),
            'jadi' => trim((string) $this->option('jadi')),
        ]);
        if ($override !== []) {
            if (count($override) !== 3) {
                $this->error('Koreksi khusus butuh ketiganya: --halaqoh, --dari, dan --jadi.');

                return self::FAILURE;
            }
            if (! in_array($override['jadi'], UmmiBook::BOOKS, true)) {
                $this->error('--jadi harus salah satu dari: '.implode(', ', UmmiBook::BOOKS).'.');

                return self::FAILURE;
            }
            $this->override = $override;
        }

        $studentIds = UmmiRecord::query()
            ->whereNotNull('ummi_jilid')
            ->where('ummi_jilid', '!=', '')
            ->whereNotIn('ummi_jilid', UmmiBook::BOOKS)
            ->distinct()
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            $this->info('Tidak ada data jilid Ummi yang tidak valid.');

            return self::SUCCESS;
        }

        $fixes = collect();
        $manual = collect();
        UmmiRecord::query()
            ->with('student:id,name,teacher_id', 'student.teacher.user:id,name')
            ->whereIn('student_id', $studentIds)
            ->orderBy('student_id')
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->groupBy('student_id')
            ->each(function (Collection $records) use ($fixes, $manual) {
                [$studentFixes, $studentManual] = $this->resolve($records->values());
                $studentFixes->each(fn ($fix) => $fixes->push($fix));
                $studentManual->each(fn ($row) => $manual->push($row));
            });

        if ($fixes->isNotEmpty()) {
            $this->info("Akan dikoreksi otomatis: {$fixes->count()} data");
            $this->table(
                ['ID', 'Murid', 'Halaqoh', 'Tanggal', 'Halaman', 'Jilid lama', 'Jilid baru', 'Dasar'],
                $fixes->map(fn ($f) => [
                    $f['record']->id,
                    $f['record']->student?->name ?? '-',
                    $this->halaqohName($f['record']),
                    $f['record']->tanggal?->toDateString(),
                    $f['record']->ummi_halaman ?? '-',
                    $f['record']->ummi_jilid,
                    $f['jilid'],
                    $f['reason'],
                ])->all()
            );
        }

        if ($manual->isNotEmpty()) {
            $this->warn("Perlu dikoreksi manual oleh guru (tidak diubah): {$manual->count()} data");
            $this->table(
                ['ID', 'Murid', 'Halaqoh', 'Tanggal', 'Halaman', 'Jilid', 'Alasan'],
                $manual->map(fn ($m) => [
                    $m['record']->id,
                    $m['record']->student?->name ?? '-',
                    $this->halaqohName($m['record']),
                    $m['record']->tanggal?->toDateString(),
                    $m['record']->ummi_halaman ?? '-',
                    $m['record']->ummi_jilid,
                    $m['reason'],
                ])->all()
            );
        }

        $targets = $this->completedTargets($fixes);

        if ($fixes->isEmpty()) {
            $this->info('Tidak ada yang bisa dikoreksi otomatis.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Mode uji coba: tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Terapkan koreksi pada {$fixes->count()} data?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($fixes) {
            foreach ($fixes as $fix) {
                $record = $fix['record'];
                $old = $record->ummi_jilid;
                $record->update(['ummi_jilid' => $fix['jilid']]);

                AuditLog::query()->create([
                    'user_name' => 'Sistem (tad:perbaiki-jilid-ummi)',
                    'action' => 'update',
                    'auditable_type' => UmmiRecord::class,
                    'auditable_id' => $record->id,
                    'auditable_label' => 'Setoran Ummi',
                    'auditable_name' => $record->student?->name,
                    'description' => "Koreksi jilid Ummi tidak valid: {$old} -> {$fix['jilid']} ({$fix['reason']})",
                    'old_values' => ['ummi_jilid' => $old],
                    'new_values' => ['ummi_jilid' => $fix['jilid']],
                ]);
            }
        });

        $this->info("{$fixes->count()} data dikoreksi. Jilid lama tercatat di Audit Log.");

        if ($this->option('hitung-ulang-target') && $targets->isNotEmpty()) {
            $this->recalculateTargets($targets);
        }

        return self::SUCCESS;
    }

    private function halaqohName(UmmiRecord $record): string
    {
        return $record->student?->teacher?->user?->name ?? '-';
    }

    /**
     * Aktifkan ulang target lalu nilai ulang dengan posisi yang sudah dikoreksi: yang memang
     * tercapai akan tercapai lagi, yang tidak kembali aktif (atau terlewat bila deadline lewat).
     */
    private function recalculateTargets(Collection $targets): void
    {
        DB::transaction(function () use ($targets) {
            foreach ($targets as $target) {
                $old = $target->only(['status', 'book_status', 'surah_status', 'completed_at']);
                $target->update(['status' => 'active', 'book_status' => null, 'surah_status' => null, 'completed_at' => null]);

                AuditLog::query()->create([
                    'user_name' => 'Sistem (tad:perbaiki-jilid-ummi)',
                    'action' => 'update',
                    'auditable_type' => HafalanTarget::class,
                    'auditable_id' => $target->id,
                    'auditable_label' => 'Target Ummi',
                    'auditable_name' => $target->student?->name,
                    'description' => 'Target Ummi diaktifkan ulang untuk dinilai ulang setelah koreksi jilid.',
                    'old_values' => $old,
                    'new_values' => ['status' => 'active'],
                ]);
            }
        });

        app(HafalanTargetAutoCompletionService::class)->syncStudents($targets->pluck('student_id')->unique()->values()->all());

        $this->table(
            ['ID Target', 'Murid', 'Target', 'Status sekarang'],
            HafalanTarget::query()->with('student:id,name')->whereIn('id', $targets->pluck('id'))->orderBy('id')->get()
                ->map(fn ($t) => [$t->id, $t->student?->name ?? '-', trim($t->ummi_jilid.' Hal. '.$t->halaman_buku), $t->status])
                ->all()
        );
        $this->info('Target dinilai ulang.');
    }

    /**
     * Target Ummi yang sudah "tercapai" tidak pernah dikembalikan ke aktif oleh sistem, jadi target
     * yang tercapai sejak setoran berjilid salah (Jilid 4 terbaca Jilid 3) perlu dicek ulang --
     * manual, atau otomatis dengan --hitung-ulang-target.
     */
    private function completedTargets(Collection $fixes): Collection
    {
        // Hanya koreksi yang menggeser posisi buku: "2" -> "Jilid 2" atau "Jilid 4" -> "Jilid 3"
        // (dulu sudah terbaca Jilid 3) tidak mengubah apa pun, jadi targetnya tidak perlu dinilai ulang.
        $earliestByStudent = $fixes
            ->filter(fn ($f) => UmmiProgressService::pageValue($f['record']->ummi_jilid, $f['record']->ummi_halaman)
                !== UmmiProgressService::pageValue($f['jilid'], $f['record']->ummi_halaman))
            ->groupBy(fn ($f) => $f['record']->student_id)
            ->map(fn ($items) => $items->min(fn ($f) => $f['record']->tanggal));

        $targets = HafalanTarget::query()
            ->with('student:id,name')
            ->whereIn('student_id', $earliestByStudent->keys())
            ->whereNotNull('ummi_jilid')
            ->where('status', 'completed')
            ->get()
            ->filter(fn ($t) => $t->completed_at === null || $t->completed_at->gte($earliestByStudent[$t->student_id]));

        if ($targets->isEmpty()) {
            return $targets;
        }

        $this->warn($this->option('hitung-ulang-target')
            ? "Target Ummi berstatus tercapai yang akan dinilai ulang: {$targets->count()} target"
            : "Target Ummi berstatus tercapai yang perlu dicek ulang (tidak diubah): {$targets->count()} target");
        $this->table(
            ['ID Target', 'Murid', 'Target', 'Tercapai'],
            $targets->map(fn ($t) => [
                $t->id,
                $t->student?->name ?? '-',
                trim($t->ummi_jilid.' Hal. '.$t->halaman_buku),
                $t->completed_at?->toDateString() ?? '-',
            ])->all()
        );

        return $targets;
    }

    /**
     * Riwayat Ummi satu murid (urut tanggal, id) -> [koreksi otomatis, perlu manual].
     *
     * @return array{0: Collection, 1: Collection}
     */
    private function resolve(Collection $records): array
    {
        // Jilid valid tiap pertemuan setelah dirapikan (null = kosong / tidak valid).
        $normalized = $records->map(fn (UmmiRecord $r) => $this->normalize($r->ummi_jilid));

        $fixes = collect();
        $manual = collect();
        foreach ($records as $i => $record) {
            $jilid = $record->ummi_jilid;
            if (blank($jilid) || in_array($jilid, UmmiBook::BOOKS, true)) {
                continue;
            }

            if ($this->override !== null
                && $jilid === $this->override['dari']
                && str_contains(mb_strtolower($this->halaqohName($record)), mb_strtolower($this->override['halaqoh']))) {
                $fixes->push(['record' => $record, 'jilid' => $this->override['jadi'], 'reason' => 'koreksi khusus halaqoh '.$this->halaqohName($record)]);

                continue;
            }

            if ($normalized[$i] !== null) {
                $fixes->push(['record' => $record, 'jilid' => $normalized[$i], 'reason' => 'perbaikan penulisan']);

                continue;
            }

            if (! preg_match('/\d+/', $jilid)) {
                $manual->push(['record' => $record, 'reason' => 'bukan nomor jilid']);

                continue;
            }

            $previous = $this->nearest($records, $normalized, $i, -1);
            $next = $previous === null ? $this->nearest($records, $normalized, $i, 1, sameMonth: true) : null;

            if ($previous !== null) {
                $fixes->push(['record' => $record, 'jilid' => $normalized[$previous], 'reason' => 'pertemuan sebelumnya '.$records[$previous]->tanggal?->toDateString()]);
            } elseif ($next !== null) {
                $fixes->push(['record' => $record, 'jilid' => $normalized[$next], 'reason' => 'pertemuan sesudahnya '.$records[$next]->tanggal?->toDateString()]);
            } else {
                $manual->push(['record' => $record, 'reason' => 'tidak ada pertemuan lain dengan jilid valid']);
            }
        }

        return [$fixes, $manual];
    }

    private function nearest(Collection $records, Collection $normalized, int $from, int $step, bool $sameMonth = false): ?int
    {
        $month = $records[$from]->tanggal?->format('Y-m');
        for ($i = $from + $step; $i >= 0 && $i < $records->count(); $i += $step) {
            if ($sameMonth && $records[$i]->tanggal?->format('Y-m') !== $month) {
                return null;
            }
            if ($normalized[$i] !== null) {
                return $i;
            }
        }

        return null;
    }

    /** "Jilid 2" / "jilid 2" / "2" -> "Jilid 2", "Ghoroib" -> "Gharib"; di luar daftar -> null. */
    private function normalize(?string $jilid): ?string
    {
        $value = trim((string) $jilid);
        if (in_array($value, UmmiBook::BOOKS, true)) {
            return $value;
        }
        if (preg_match('/^(jilid)?\s*([1-3])$/i', $value, $m)) {
            return 'Jilid '.$m[2];
        }
        if (preg_match('/^gh?(a|o)r(o|i)?i?b$/i', $value)) {
            return 'Gharib';
        }
        if (preg_match('/^tajwid$/i', $value)) {
            return 'Tajwid';
        }

        return null;
    }
}
