<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");

        return $setting;
    }

    /**
     * Returns the 4 adab categories with their questions.
     * Each category has a 'title', 'desc', and 'questions' (array of strings).
     */
    public static function getAdabQuestions(): array
    {
        $default = [
            [
                'title' => '🕋 Adab Kepada Allah',
                'desc' => 'Menjaga hubungan ketakwaan dan ibadah sehari-hari kepada Allah Subhanahu wa Ta\'ala.',
                'questions' => [
                    'Apakah Anda melaksanakan shalat fardhu tepat waktu hari ini?',
                    'Apakah Anda mengawali aktivitas hari ini dengan membaca Basmalah?',
                    'Apakah Anda selalu berdoa setelah selesai shalat fardhu hari ini?',
                    'Apakah Anda bersyukur atas segala nikmat yang Anda rasakan hari ini?',
                    'Apakah Anda menyempatkan diri berdzikir (membaca tasbih/tahmid/takbir) hari ini?',
                ],
            ],
            [
                'title' => '👥 Adab Kepada Sesama Teman',
                'desc' => 'Menjalin hubungan yang baik, saling menghormati, dan berlaku adil terhadap sesama.',
                'questions' => [
                    'Apakah Anda bersikap sopan dan santun kepada teman-teman hari ini?',
                    'Apakah Anda menghindari perkataan kasar, mengejek, atau menyakiti teman?',
                    'Apakah Anda membantu teman yang membutuhkan pertolongan hari ini?',
                    'Apakah Anda menjaga amanah dan kejujuran dalam pergaulan hari ini?',
                    'Apakah Anda ikut menjaga kerukunan dan ketenangan di lingkungan asrama/kelas?',
                ],
            ],
            [
                'title' => '📚 Adab Ketika Belajar',
                'desc' => 'Menjaga ketertiban, kebersihan, kepatuhan, dan doa dalam menuntut ilmu.',
                'questions' => [
                    'Apakah Anda datang/masuk kelas tepat waktu dan menyiapkan peralatan belajar?',
                    'Apakah Anda menyimak penjelasan guru dengan khusyuk dan tidak mengobrol saat pelajaran?',
                    'Apakah Anda mencatat materi pelajaran dengan rapi dan tertib?',
                    'Apakah Anda mengawali dan mengakhiri belajar dengan berdoa?',
                    'Apakah Anda menjaga kebersihan dan kerapian tempat belajar Anda?',
                ],
            ],
            [
                'title' => '🌿 Adab terhadap Lingkungan',
                'desc' => 'Menjaga kebersihan, ketertiban, dan kelestarian lingkungan sebagai bentuk syukur kepada Allah.',
                'questions' => [
                    'Apakah Anda membuang sampah pada tempatnya hari ini?',
                    'Apakah Anda menjaga kebersihan kamar/asrama Anda hari ini?',
                    'Apakah Anda turut merawat fasilitas sekolah/pesantren dengan baik?',
                    'Apakah Anda bersikap hemat dalam menggunakan air, listrik, atau barang fasilitas?',
                    'Apakah Anda tidak merusak atau mencoret-coret benda/properti milik bersama?',
                ],
            ],
        ];

        $json = self::get('adab_questions');
        if ($json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && count($decoded) >= 2) {
                return $decoded;
            }
        }

        return $default;
    }

    /**
     * Convert a 0-100 percentage score to a letter grade.
     */
    public static function getAdabGrade(float $score): string
    {
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }
        if ($score >= 60) {
            return 'D';
        }

        return 'E';
    }

    /**
     * Get grade label in Bahasa Indonesia.
     */
    public static function getAdabGradeLabel(string $grade): string
    {
        return match ($grade) {
            'A' => 'Mumtaz (Sangat Baik)',
            'B' => 'Jayyid Jiddan (Baik Sekali)',
            'C' => 'Jayyid (Baik)',
            'D' => 'Maqbul (Cukup)',
            default => 'Dha\'if (Kurang)',
        };
    }
}
