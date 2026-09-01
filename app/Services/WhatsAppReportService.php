<?php

namespace App\Services;

use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\UmmiRecord;

class WhatsAppReportService
{
    /**
     * Format phone number to standard international format without leading + or 0 (for WhatsApp: 628xxx).
     */
    public function formatPhoneNumber(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (empty($clean)) {
            return null;
        }

        if (str_starts_with($clean, '0')) {
            $clean = '62'.substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62'.$clean;
        }

        return $clean;
    }

    /**
     * Get parent phone from student's parent relationship.
     */
    public function getStudentParentPhone(?Student $student): ?string
    {
        if (! $student) {
            return null;
        }

        $parentPhone = $student->parents?->pluck('phone')->filter()->first();

        return $this->formatPhoneNumber($parentPhone);
    }

    /**
     * Generate structured WhatsApp message for HafalanRecord.
     */
    public function generateHafalanMessage(HafalanRecord $record): string
    {
        $studentName = $record->student?->name ?? 'Santri';
        $className = $record->student?->classRoom?->name ?? '-';
        $dateFormatted = $record->submitted_at
            ? $record->submitted_at->translatedFormat('l, j F Y')
            : now()->translatedFormat('l, j F Y');

        $surahName = $record->surah?->name_latin ?? ('Surah #'.$record->surah_id);
        $ayahRange = "{$record->ayah_start} - {$record->ayah_end}";
        $lines = $record->lines_count;
        $submissionType = $record->submission_type_label;
        $score = $record->score !== null ? $record->score_letter.' ('.number_format((float) $record->score, 0).')' : ($record->score_letter ?? '-');
        $status = $record->status_label;
        $notes = ! empty(trim($record->notes ?? '')) ? trim($record->notes) : '-';
        $teacherName = $record->teacher?->user?->name ?? 'Musyrif Halaqah';

        $text = "Assalamu'alaikum Warahmatullahi Wabarakatuh,\n";
        $text .= "Yth. Bapak/Ibu Wali dari ananda *{$studentName}* ({$className})\n\n";
        $text .= "Berikut kami sampaikan laporan capaian setoran hafalan Al-Qur'an hari ini:\n";
        $text .= "📅 *Tanggal:* {$dateFormatted}\n";
        $text .= "📖 *Surah:* {$surahName} (Ayat {$ayahRange})\n";
        $text .= "📏 *Capaian:* {$lines} Baris\n";
        $text .= "🎯 *Jenis:* {$submissionType}\n";
        $text .= "⭐ *Nilai:* {$score}\n";
        $text .= "📊 *Status:* {$status}\n";
        $text .= "📝 *Catatan Musyrif:* {$notes}\n\n";
        $text .= "_Semoga ananda senantiasa istiqomah dan diberkahi Allah SWT dalam menjaga kalam-Nya. Aamiin._ 🤲\n\n";
        $text .= "— *{$teacherName}*\n";
        $text .= "*Halaqah Tahfizh SMA Islam Al Azhar 7 Sukoharjo*";

        return $text;
    }

    /**
     * Generate structured WhatsApp message for MurajaahRecord.
     */
    public function generateMurajaahMessage(MurajaahRecord $record): string
    {
        $studentName = $record->student?->name ?? 'Santri';
        $className = $record->student?->classRoom?->name ?? '-';
        $dateFormatted = $record->reviewed_at
            ? $record->reviewed_at->translatedFormat('l, j F Y')
            : now()->translatedFormat('l, j F Y');

        $surahName = $record->surah?->name_latin ?? ('Surah #'.$record->surah_id);
        $ayahRange = $record->ayah_range;
        $status = $record->status_label;
        $overallScore = $record->overall_score !== null ? number_format((float) $record->overall_score, 0) : '-';
        $fluencyScore = $record->fluency_score !== null ? number_format((float) $record->fluency_score, 0) : '-';
        $tajwidScore = $record->tajwid_score !== null ? number_format((float) $record->tajwid_score, 0) : '-';
        $makhrajScore = $record->makhraj_score !== null ? number_format((float) $record->makhraj_score, 0) : '-';
        $notes = ! empty(trim($record->notes ?? '')) ? trim($record->notes) : '-';
        $teacherName = $record->teacher?->user?->name ?? 'Musyrif Halaqah';

        $text = "Assalamu'alaikum Warahmatullahi Wabarakatuh,\n";
        $text .= "Yth. Bapak/Ibu Wali dari ananda *{$studentName}* ({$className})\n\n";
        $text .= "Berikut kami sampaikan laporan kegiatan Muraja'ah Al-Qur'an hari ini:\n";
        $text .= "📅 *Tanggal:* {$dateFormatted}\n";
        $text .= "📖 *Surah:* {$surahName} (Ayat {$ayahRange})\n";
        $text .= "📊 *Status:* {$status}\n";
        $text .= "⭐ *Penilaian:*\n";
        $text .= "  • Kelancaran: {$fluencyScore}\n";
        $text .= "  • Tajwid: {$tajwidScore}\n";
        $text .= "  • Makhraj: {$makhrajScore}\n";
        $text .= "  • Nilai Akhir: *{$overallScore}*\n";
        $text .= "📝 *Catatan Musyrif:* {$notes}\n\n";
        $text .= "_Baarakallaahu lanaa wa lakum fil qur'aanil 'azhiim._ ✨\n\n";
        $text .= "— *{$teacherName}*\n";
        $text .= "*Halaqah Tahfizh SMA Islam Al Azhar 7 Sukoharjo*";

        return $text;
    }

    /**
     * Generate structured WhatsApp message for UmmiRecord.
     */
    public function generateUmmiMessage(UmmiRecord $record): string
    {
        $studentName = $record->student?->name ?? 'Santri';
        $className = $record->student?->classRoom?->name ?? '-';
        $dateFormatted = $record->tanggal
            ? $record->tanggal->translatedFormat('l, j F Y')
            : now()->translatedFormat('l, j F Y');

        $jilid = $record->ummi_jilid ?? 'Jilid 1';
        $halaman = $record->ummi_halaman ?? '-';
        $tatapMuka = $record->tatap_muka ?? '-';
        $materi = $record->materi ?: '-';
        $nilai = $record->nilai ?: '-';
        $keterangan = ! empty(trim($record->keterangan ?? '')) ? trim($record->keterangan) : '-';
        $hafalanInfo = '';
        if ($record->surah) {
            $hafalanInfo = "📖 *Hafalan Surah:* {$record->surah->name_latin} (Ayat {$record->hafalan_ayah})\n";
        }
        $teacherName = $record->teacher?->user?->name ?? 'Musyrif Halaqah';

        $text = "Assalamu'alaikum Warahmatullahi Wabarakatuh,\n";
        $text .= "Yth. Bapak/Ibu Wali dari ananda *{$studentName}* ({$className})\n\n";
        $text .= "Berikut kami sampaikan laporan pembelajaran Al-Qur'an Metode UMMI hari ini:\n";
        $text .= "📅 *Tanggal:* {$dateFormatted}\n";
        $text .= "👥 *Tatap Muka ke:* {$tatapMuka}\n";
        $text .= "🌱 *Jilid / Halaman:* {$jilid} (Halaman {$halaman})\n";
        $text .= "📚 *Materi:* {$materi}\n";
        if ($hafalanInfo) {
            $text .= $hafalanInfo;
        }
        $text .= "⭐ *Nilai:* {$nilai}\n";
        $text .= "📝 *Catatan Guru:* {$keterangan}\n\n";
        $text .= "_Semoga ananda senantiasa istiqomah dan diberi kemudahan serta kefasihan membaca Al-Qur'an._ 🤲\n\n";
        $text .= "— *{$teacherName}*\n";
        $text .= "*Halaqah Tahfizh SMA Islam Al Azhar 7 Sukoharjo*";

        return $text;
    }

    /**
     * Build WhatsApp URL with phone and encoded message.
     */
    public function buildWhatsAppUrl(?string $phone, string $message): string
    {
        $encodedMessage = rawurlencode($message);
        $cleanPhone = $this->formatPhoneNumber($phone);

        if ($cleanPhone) {
            return "https://wa.me/{$cleanPhone}?text={$encodedMessage}";
        }

        return "https://api.whatsapp.com/send?text={$encodedMessage}";
    }

    public function getHafalanShareUrl(HafalanRecord $record): string
    {
        $phone = $this->getStudentParentPhone($record->student);
        $message = $this->generateHafalanMessage($record);

        return $this->buildWhatsAppUrl($phone, $message);
    }

    public function getMurajaahShareUrl(MurajaahRecord $record): string
    {
        $phone = $this->getStudentParentPhone($record->student);
        $message = $this->generateMurajaahMessage($record);

        return $this->buildWhatsAppUrl($phone, $message);
    }

    public function getUmmiShareUrl(UmmiRecord $record): string
    {
        $phone = $this->getStudentParentPhone($record->student);
        $message = $this->generateUmmiMessage($record);

        return $this->buildWhatsAppUrl($phone, $message);
    }
}

