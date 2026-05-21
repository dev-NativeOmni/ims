<?php

namespace Database\Seeders;

use App\Models\Ayah;
use App\Models\Surah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuranDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->surahs() as $surahData) {
                $surah = Surah::updateOrCreate(
                    ['number' => $surahData['number']],
                    [
                        'name_ar' => $surahData['name_ar'],
                        'name_latin' => $surahData['name_latin'],
                        'total_ayah' => $surahData['total_ayah'],
                        'juz_start' => $surahData['juz_start'],
                        'juz_end' => $surahData['juz_end'],
                    ]
                );

                $this->seedAyahsForSurah($surah);
            }
        });
    }

    private function seedAyahsForSurah(Surah $surah): void
    {
        $now = now();
        $rows = [];

        for ($ayahNumber = 1; $ayahNumber <= $surah->total_ayah; $ayahNumber++) {
            $rows[] = [
                'surah_id' => $surah->id,
                'ayah_number' => $ayahNumber,
                'juz' => null,
                'text_ar' => null,
                'translation_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Ayah::upsert(
                $chunk,
                ['surah_id', 'ayah_number'],
                ['juz', 'text_ar', 'translation_id', 'updated_at']
            );
        }
    }

    private function surahs(): array
    {
        $s = fn (
            int $number,
            string $nameAr,
            string $nameLatin,
            int $totalAyah,
            int $juzStart,
            int $juzEnd
        ): array => [
            'number' => $number,
            'name_ar' => $nameAr,
            'name_latin' => $nameLatin,
            'total_ayah' => $totalAyah,
            'juz_start' => $juzStart,
            'juz_end' => $juzEnd,
        ];

        return [
            $s(1, 'الفاتحة', 'Al-Fatihah', 7, 1, 1),
            $s(2, 'البقرة', 'Al-Baqarah', 286, 1, 3),
            $s(3, 'آل عمران', "Ali 'Imran", 200, 3, 4),
            $s(4, 'النساء', 'An-Nisa', 176, 4, 6),
            $s(5, 'المائدة', "Al-Ma'idah", 120, 6, 7),
            $s(6, 'الأنعام', "Al-An'am", 165, 7, 8),
            $s(7, 'الأعراف', "Al-A'raf", 206, 8, 9),
            $s(8, 'الأنفال', 'Al-Anfal', 75, 9, 10),
            $s(9, 'التوبة', 'At-Taubah', 129, 10, 11),
            $s(10, 'يونس', 'Yunus', 109, 11, 11),
            $s(11, 'هود', 'Hud', 123, 11, 12),
            $s(12, 'يوسف', 'Yusuf', 111, 12, 13),
            $s(13, 'الرعد', "Ar-Ra'd", 43, 13, 13),
            $s(14, 'إبراهيم', 'Ibrahim', 52, 13, 13),
            $s(15, 'الحجر', 'Al-Hijr', 99, 14, 14),
            $s(16, 'النحل', 'An-Nahl', 128, 14, 14),
            $s(17, 'الإسراء', 'Al-Isra', 111, 15, 15),
            $s(18, 'الكهف', 'Al-Kahf', 110, 15, 16),
            $s(19, 'مريم', 'Maryam', 98, 16, 16),
            $s(20, 'طه', 'Taha', 135, 16, 16),
            $s(21, 'الأنبياء', 'Al-Anbiya', 112, 17, 17),
            $s(22, 'الحج', 'Al-Hajj', 78, 17, 17),
            $s(23, 'المؤمنون', "Al-Mu'minun", 118, 18, 18),
            $s(24, 'النور', 'An-Nur', 64, 18, 18),
            $s(25, 'الفرقان', 'Al-Furqan', 77, 18, 19),
            $s(26, 'الشعراء', "Ash-Shu'ara", 227, 19, 19),
            $s(27, 'النمل', 'An-Naml', 93, 19, 20),
            $s(28, 'القصص', 'Al-Qasas', 88, 20, 20),
            $s(29, 'العنكبوت', "Al-'Ankabut", 69, 20, 21),
            $s(30, 'الروم', 'Ar-Rum', 60, 21, 21),
            $s(31, 'لقمان', 'Luqman', 34, 21, 21),
            $s(32, 'السجدة', 'As-Sajdah', 30, 21, 21),
            $s(33, 'الأحزاب', 'Al-Ahzab', 73, 21, 22),
            $s(34, 'سبأ', 'Saba', 54, 22, 22),
            $s(35, 'فاطر', 'Fatir', 45, 22, 22),
            $s(36, 'يس', 'Ya-Sin', 83, 22, 23),
            $s(37, 'الصافات', 'As-Saffat', 182, 23, 23),
            $s(38, 'ص', 'Sad', 88, 23, 23),
            $s(39, 'الزمر', 'Az-Zumar', 75, 23, 24),
            $s(40, 'غافر', 'Ghafir', 85, 24, 24),
            $s(41, 'فصلت', 'Fussilat', 54, 24, 25),
            $s(42, 'الشورى', 'Ash-Shura', 53, 25, 25),
            $s(43, 'الزخرف', 'Az-Zukhruf', 89, 25, 25),
            $s(44, 'الدخان', 'Ad-Dukhan', 59, 25, 25),
            $s(45, 'الجاثية', 'Al-Jathiyah', 37, 25, 25),
            $s(46, 'الأحقاف', 'Al-Ahqaf', 35, 26, 26),
            $s(47, 'محمد', 'Muhammad', 38, 26, 26),
            $s(48, 'الفتح', 'Al-Fath', 29, 26, 26),
            $s(49, 'الحجرات', 'Al-Hujurat', 18, 26, 26),
            $s(50, 'ق', 'Qaf', 45, 26, 26),
            $s(51, 'الذاريات', 'Adh-Dhariyat', 60, 26, 27),
            $s(52, 'الطور', 'At-Tur', 49, 27, 27),
            $s(53, 'النجم', 'An-Najm', 62, 27, 27),
            $s(54, 'القمر', 'Al-Qamar', 55, 27, 27),
            $s(55, 'الرحمن', 'Ar-Rahman', 78, 27, 27),
            $s(56, 'الواقعة', "Al-Waqi'ah", 96, 27, 27),
            $s(57, 'الحديد', 'Al-Hadid', 29, 27, 27),
            $s(58, 'المجادلة', 'Al-Mujadilah', 22, 28, 28),
            $s(59, 'الحشر', 'Al-Hashr', 24, 28, 28),
            $s(60, 'الممتحنة', 'Al-Mumtahanah', 13, 28, 28),
            $s(61, 'الصف', 'As-Saff', 14, 28, 28),
            $s(62, 'الجمعة', "Al-Jumu'ah", 11, 28, 28),
            $s(63, 'المنافقون', 'Al-Munafiqun', 11, 28, 28),
            $s(64, 'التغابن', 'At-Taghabun', 18, 28, 28),
            $s(65, 'الطلاق', 'At-Talaq', 12, 28, 28),
            $s(66, 'التحريم', 'At-Tahrim', 12, 28, 28),
            $s(67, 'الملك', 'Al-Mulk', 30, 29, 29),
            $s(68, 'القلم', 'Al-Qalam', 52, 29, 29),
            $s(69, 'الحاقة', 'Al-Haqqah', 52, 29, 29),
            $s(70, 'المعارج', "Al-Ma'arij", 44, 29, 29),
            $s(71, 'نوح', 'Nuh', 28, 29, 29),
            $s(72, 'الجن', 'Al-Jinn', 28, 29, 29),
            $s(73, 'المزمل', 'Al-Muzzammil', 20, 29, 29),
            $s(74, 'المدثر', 'Al-Muddaththir', 56, 29, 29),
            $s(75, 'القيامة', 'Al-Qiyamah', 40, 29, 29),
            $s(76, 'الإنسان', 'Al-Insan', 31, 29, 29),
            $s(77, 'المرسلات', 'Al-Mursalat', 50, 29, 29),
            $s(78, 'النبأ', 'An-Naba', 40, 30, 30),
            $s(79, 'النازعات', "An-Nazi'at", 46, 30, 30),
            $s(80, 'عبس', "'Abasa", 42, 30, 30),
            $s(81, 'التكوير', 'At-Takwir', 29, 30, 30),
            $s(82, 'الانفطار', 'Al-Infitar', 19, 30, 30),
            $s(83, 'المطففين', 'Al-Mutaffifin', 36, 30, 30),
            $s(84, 'الانشقاق', 'Al-Inshiqaq', 25, 30, 30),
            $s(85, 'البروج', 'Al-Buruj', 22, 30, 30),
            $s(86, 'الطارق', 'At-Tariq', 17, 30, 30),
            $s(87, 'الأعلى', "Al-A'la", 19, 30, 30),
            $s(88, 'الغاشية', 'Al-Ghashiyah', 26, 30, 30),
            $s(89, 'الفجر', 'Al-Fajr', 30, 30, 30),
            $s(90, 'البلد', 'Al-Balad', 20, 30, 30),
            $s(91, 'الشمس', 'Ash-Shams', 15, 30, 30),
            $s(92, 'الليل', 'Al-Lail', 21, 30, 30),
            $s(93, 'الضحى', 'Ad-Duha', 11, 30, 30),
            $s(94, 'الشرح', 'Ash-Sharh', 8, 30, 30),
            $s(95, 'التين', 'At-Tin', 8, 30, 30),
            $s(96, 'العلق', "Al-'Alaq", 19, 30, 30),
            $s(97, 'القدر', 'Al-Qadr', 5, 30, 30),
            $s(98, 'البينة', 'Al-Bayyinah', 8, 30, 30),
            $s(99, 'الزلزلة', 'Az-Zalzalah', 8, 30, 30),
            $s(100, 'العاديات', "Al-'Adiyat", 11, 30, 30),
            $s(101, 'القارعة', "Al-Qari'ah", 11, 30, 30),
            $s(102, 'التكاثر', 'At-Takathur', 8, 30, 30),
            $s(103, 'العصر', "Al-'Asr", 3, 30, 30),
            $s(104, 'الهمزة', 'Al-Humazah', 9, 30, 30),
            $s(105, 'الفيل', 'Al-Fil', 5, 30, 30),
            $s(106, 'قريش', 'Quraish', 4, 30, 30),
            $s(107, 'الماعون', "Al-Ma'un", 7, 30, 30),
            $s(108, 'الكوثر', 'Al-Kawthar', 3, 30, 30),
            $s(109, 'الكافرون', 'Al-Kafirun', 6, 30, 30),
            $s(110, 'النصر', 'An-Nasr', 3, 30, 30),
            $s(111, 'المسد', 'Al-Masad', 5, 30, 30),
            $s(112, 'الإخلاص', 'Al-Ikhlas', 4, 30, 30),
            $s(113, 'الفلق', 'Al-Falaq', 5, 30, 30),
            $s(114, 'الناس', 'An-Nas', 6, 30, 30),
        ];
    }
}