<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\Student;
use App\Models\UmmiRecord;
use App\Services\UmmiTatapMukaService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Hapus setoran ganda yang IDENTIK (mis. akibat tombol Simpan terklik dua kali). Yang disimpan
 * selalu data paling awal; setiap yang dihapus dicatat di Audit Log beserta isinya.
 *
 * - Hafalan: baris dengan murid, tanggal, surah, ayat awal-akhir & status sama persis (program "seminggu
 *   sekali": pekan yang sama, karena satu pertemuan per pekan).
 * - Ummi: sesi murid di tanggal yang sama dengan Jilid, Halaman, Materi, Nilai & surah-ayat sama
 *   persis; juga baris surah identik di dalam satu sesi. Sesi ganda yang isinya BERBEDA tidak
 *   dihapus -- hanya ditampilkan untuk dicek manual.
 * - --gabung-sesi: sesi tanggal sama yang TANPA Jilid & Halaman digabung ke satu-satunya sesi
 *   tanggal itu yang berisi Jilid/Halaman (surahnya dipindah, yang identik dilewati).
 */
class RemoveDuplicateSetoran extends Command
{
    protected $signature = 'tad:hapus-setoran-ganda
        {--dry-run : Tampilkan setoran ganda tanpa menghapus}
        {--force : Jalankan tanpa pertanyaan konfirmasi}
        {--gabung-sesi : Gabungkan sesi Ummi tanpa Jilid/Halaman ke sesi tanggal sama yang berisi Jilid/Halaman}';

    protected $description = 'Hapus setoran hafalan & Ummi yang ganda identik (sisakan yang paling awal).';

    public function handle(UmmiTatapMukaService $tatapMuka): int
    {
        $hafalanDupes = $this->hafalanDuplicates();
        [$ummiDupes, $ummiLineDupes, $ummiConflicts] = $this->ummiDuplicates();
        $merges = $this->option('gabung-sesi') ? $this->mergeableSessions($ummiConflicts) : collect();
        $mergedIds = $merges->flatMap(fn ($m) => [$m['from']->id, $m['into']->id])->all();
        $ummiConflicts = $ummiConflicts->reject(fn ($r) => in_array($r->id, $mergedIds, true))->values();

        $this->info("Setoran hafalan ganda identik: {$hafalanDupes->count()} baris");
        if ($hafalanDupes->isNotEmpty()) {
            // "Dibuat" & "Lewat": kapan baris ganda dibuat, dan halaman/akun yang membuat sesinya (Audit Log),
            // untuk melacak penyebabnya.
            $this->table(['ID baris', 'Murid', 'Kelas', 'Tanggal', 'Surah', 'Ayat', 'Status', 'Sama dengan ID', 'Dibuat', 'Lewat'], $hafalanDupes->map(fn ($d) => [
                $d['line']->id,
                $d['line']->hafalanRecord?->student?->name ?? '-',
                $d['line']->hafalanRecord?->student?->classRoom?->name ?? '-',
                $d['line']->hafalanRecord?->submitted_at?->toDateString(),
                $d['line']->surah?->name_latin ?? '-',
                "{$d['line']->ayah_start}-{$d['line']->ayah_end}",
                $d['line']->status,
                $d['keep']->id,
                $d['line']->created_at?->format('d/m H:i'),
                $this->createdVia($d['line']->hafalan_record_id),
            ])->all());
        }

        $this->info("Sesi Ummi ganda identik: {$ummiDupes->count()} sesi, baris surah ganda di dalam sesi: {$ummiLineDupes->count()}");
        if ($ummiDupes->isNotEmpty()) {
            $this->table(['ID sesi', 'Murid', 'Tanggal', 'Jilid', 'Halaman', 'Sama dengan ID'], $ummiDupes->map(fn ($d) => [
                $d['record']->id,
                $d['record']->student?->name ?? '-',
                $d['record']->tanggal?->toDateString(),
                $d['record']->ummi_jilid ?? '-',
                $d['record']->ummi_halaman ?? '-',
                $d['keep']->id,
            ])->all());
        }

        if ($merges->isNotEmpty()) {
            $this->info("Sesi Ummi tanpa Jilid/Halaman yang digabung ke sesi tanggal sama: {$merges->count()}");
            $this->table(['Murid', 'Tanggal', 'Sesi dihapus (ID)', 'Digabung ke (ID)', 'Jilid', 'Halaman', 'Surah dipindah'], $merges->map(fn ($m) => [
                $m['into']->student?->name ?? '-',
                $m['into']->tanggal?->toDateString(),
                $m['from']->id,
                $m['into']->id,
                $m['into']->ummi_jilid ?? '-',
                $m['into']->ummi_halaman ?? '-',
                $m['moved']->count(),
            ])->all());
        }

        if ($ummiConflicts->isNotEmpty()) {
            $this->warn("Sesi Ummi di tanggal yang sama tapi isinya BERBEDA (tidak dihapus, cek manual): {$ummiConflicts->count()}");
            $this->table(['ID sesi', 'Murid', 'Tanggal', 'Jilid', 'Halaman', 'Nilai'], $ummiConflicts->map(fn ($r) => [
                $r->id, $r->student?->name ?? '-', $r->tanggal?->toDateString(), $r->ummi_jilid ?? '-', $r->ummi_halaman ?? '-', $r->nilai ?? '-',
            ])->all());
        }

        $total = $hafalanDupes->count() + $ummiDupes->count() + $ummiLineDupes->count() + $merges->count();
        if ($total === 0) {
            $this->info('Tidak ada setoran ganda identik.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Mode uji coba: tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Hapus {$total} setoran ganda identik (yang paling awal tetap disimpan)?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($hafalanDupes, $ummiDupes, $ummiLineDupes, $merges) {
            foreach ($merges as $m) {
                $this->log(UmmiRecord::class, $m['from']->id, $m['from']->student?->name, 'Sesi Ummi digabung', $m['from']->toArray() + ['surahs' => $m['from']->surahs->toArray()], $m['into']->id);
                $order = (int) $m['into']->surahs()->max('sort_order');
                foreach ($m['moved'] as $line) {
                    $line->update(['ummi_record_id' => $m['into']->id, 'sort_order' => ++$order]);
                }
                $m['from']->surahs()->delete(); // sisa: baris yang identik dengan sesi tujuan
                $m['from']->delete();
            }

            foreach ($hafalanDupes as $d) {
                $line = $d['line'];
                $this->log(HafalanRecordSurah::class, $line->id, $line->hafalanRecord?->student?->name, 'Setoran hafalan', $line->toArray(), $d['keep']->id);
                $header = $line->hafalanRecord;
                $line->delete();
                if ($header && ! $header->surahs()->exists()) {
                    $header->delete();
                }
            }

            foreach ($ummiLineDupes as $d) {
                $this->log(UmmiRecord::class, $d['line']->ummi_record_id, null, 'Baris surah Ummi', $d['line']->toArray(), $d['keep']->id);
                $d['line']->delete();
            }

            foreach ($ummiDupes as $d) {
                $record = $d['record'];
                $this->log(UmmiRecord::class, $record->id, $record->student?->name, 'Setoran Ummi', $record->toArray() + ['surahs' => $record->surahs->toArray()], $d['keep']->id);
                $record->surahs()->delete();
                $record->delete();
            }
        });

        // Sesi Ummi terhapus -> urutan TM halaqohnya dirapikan lagi.
        $tatapMuka->renumber($ummiDupes->map(fn ($d) => [$d['record']->student_id, $d['record']->tanggal])
            ->merge($merges->map(fn ($m) => [$m['into']->student_id, $m['into']->tanggal]))
            ->all());

        $this->info("{$total} setoran ganda identik dihapus. Isinya tercatat di Audit Log.");

        return self::SUCCESS;
    }

    /** @return Collection<int, array{line: HafalanRecordSurah, keep: HafalanRecordSurah}> */
    /**
     * Halaman & akun yang membuat sesi setoran ini menurut Audit Log, mis. "spreadsheet-input/save · Guru A".
     */
    private function createdVia(?int $headerId): string
    {
        $log = $headerId ? AuditLog::query()->with('user:id,name')
            ->where('auditable_type', (new HafalanRecord)->getMorphClass())
            ->where('auditable_id', $headerId)
            ->where('action', 'created')
            ->first() : null;
        if (! $log) {
            return '-';
        }

        $path = trim((string) parse_url((string) $log->url, PHP_URL_PATH), '/') ?: 'konsol';

        return $path.' · '.($log->user?->name ?? $log->user_name ?? '-');
    }

    private function hafalanDuplicates(): Collection
    {
        // Diproses per murid supaya memori tetap kecil walau datanya banyak.
        $result = collect();
        foreach (HafalanRecord::query()->distinct()->pluck('student_id') as $studentId) {
            // Program "seminggu sekali": satu pertemuan per pekan, jadi setoran identik di pekan yang sama
            // (tanggal berbeda) juga ganda -- mis. salinan dari spreadsheet per pekan.
            $weekly = Student::query()->with('classRoom.program')->find($studentId)?->classRoom?->program?->meeting_frequency === 'seminggu sekali';
            HafalanRecordSurah::query()
                ->whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $studentId))
                ->with(['hafalanRecord.student:id,name,class_room_id', 'hafalanRecord.student.classRoom:id,name', 'surah:id,name_latin'])
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($l) => implode('|', [
                    $weekly ? $l->hafalanRecord->submitted_at?->format('o-\\WW') : $l->hafalanRecord->submitted_at?->toDateString(),
                    $l->surah_id,
                    $l->ayah_start,
                    $l->ayah_end,
                    $l->status,
                ]))
                ->filter(fn ($group) => $group->count() > 1)
                ->each(fn ($group) => $group->slice(1)->each(fn ($l) => $result->push(['line' => $l, 'keep' => $group->first()])));
        }

        return $result;
    }

    /**
     * @return array{0: Collection, 1: Collection, 2: Collection} [sesi identik, baris surah identik dalam satu sesi, sesi berbeda di tanggal sama]
     */
    private function ummiDuplicates(): array
    {
        $signature = fn (UmmiRecord $r) => implode('|', [
            $r->ummi_jilid, $r->ummi_halaman, $r->materi, $r->nilai,
            $r->surahs->map(fn ($s) => $s->surah_id.':'.str_replace(' ', '', (string) $s->hafalan_ayah))->unique()->sort()->implode(','),
        ]);

        $sessions = collect();
        $lines = collect();
        $conflicts = collect();

        // Diproses per murid supaya memori tetap kecil walau datanya banyak.
        foreach (UmmiRecord::query()->distinct()->pluck('student_id') as $studentId) {
            $records = UmmiRecord::query()->with(['student:id,name', 'surahs'])->where('student_id', $studentId)->orderBy('id')->get();
            $removedIds = [];

            $records->groupBy(fn ($r) => $r->tanggal?->toDateString())
                ->filter(fn ($group) => $group->count() > 1)
                ->each(function ($group) use ($signature, $sessions, $conflicts, &$removedIds) {
                    $keep = $group->first();
                    foreach ($group->slice(1) as $record) {
                        if ($signature($record) === $signature($keep)) {
                            $sessions->push(['record' => $record, 'keep' => $keep]);
                            $removedIds[] = $record->id;
                        } else {
                            $conflicts->push($keep, $record);
                        }
                    }
                });

            $records->reject(fn ($r) => in_array($r->id, $removedIds, true))
                ->each(fn ($r) => $r->surahs
                    ->groupBy(fn ($s) => $s->surah_id.':'.str_replace(' ', '', (string) $s->hafalan_ayah))
                    ->filter(fn ($g) => $g->count() > 1)
                    ->each(fn ($g) => $g->sortBy('id')->slice(1)->each(fn ($s) => $lines->push(['line' => $s, 'keep' => $g->sortBy('id')->first()]))));
        }

        return [$sessions, $lines, $conflicts->unique('id')->values()];
    }

    /**
     * Dari sesi-sesi tanggal sama yang isinya berbeda: yang tanpa Jilid & Halaman digabung ke
     * satu-satunya sesi tanggal itu yang berisi Jilid/Halaman. Lebih dari satu sesi berisi buku
     * (mis. halaman 17-19 dan 21-23) tidak digabung -- tetap cek manual.
     *
     * @return Collection<int, array{from: UmmiRecord, into: UmmiRecord, moved: Collection}>
     */
    private function mergeableSessions(Collection $conflicts): Collection
    {
        $hasBook = fn (UmmiRecord $r) => filled($r->ummi_jilid) || filled($r->ummi_halaman);
        $key = fn ($s) => $s->surah_id.':'.str_replace(' ', '', (string) $s->hafalan_ayah);

        return $conflicts
            ->groupBy(fn ($r) => $r->student_id.'|'.$r->tanggal?->toDateString())
            ->flatMap(function ($group) use ($hasBook, $key) {
                $withBook = $group->filter($hasBook);
                if ($withBook->count() !== 1) {
                    return [];
                }
                $into = $withBook->first();
                $existing = $into->surahs->map($key)->all();

                return $group->reject($hasBook)->map(fn ($from) => [
                    'from' => $from,
                    'into' => $into,
                    'moved' => $from->surahs->reject(fn ($s) => in_array($key($s), $existing, true))->unique($key)->values(),
                ])->values();
            })
            ->values();
    }

    private function log(string $type, int $id, ?string $name, string $label, array $old, int $keptId): void
    {
        AuditLog::query()->create([
            'user_name' => 'Sistem (tad:hapus-setoran-ganda)',
            'action' => 'delete',
            'auditable_type' => $type,
            'auditable_id' => $id,
            'auditable_label' => $label,
            'auditable_name' => $name,
            'description' => "Hapus {$label} ganda identik (data yang disimpan: ID {$keptId})",
            'old_values' => $old,
            'new_values' => null,
        ]);
    }
}
