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

    public static function getAdabQuestions()
    {
        $default = [
            [
                'title' => '🕋 Adab Kepada Allah',
                'desc' => 'Menjaga hubungan ketakwaan dan ibadah sehari-hari kepada Allah Subhanahu wa Ta\'ala.',
                'questions' => [
                    'q1' => 'Apakah Anda melaksanakan shalat fardhu tepat waktu hari ini?',
                    'q2' => 'Apakah Anda mengawali aktivitas hari ini dengan membaca Basmalah?',
                    'q3' => 'Apakah Anda selalu berdoa setelah selesai shalat fardhu hari ini?',
                    'q4' => 'Apakah Anda bersyukur atas segala nikmat yang Anda rasakan hari ini?',
                    'q5' => 'Apakah Anda menyempatkan diri berdzikir (membaca tasbih/tahmid/takbir) hari ini?'
                ]
            ],
            [
                'title' => '💚 Adab Kepada Rasulullah',
                'desc' => 'Menghidupkan kecintaan dan amalan sunnah sesuai ajaran Nabi Muhammad Shallallahu \'Alaihi wa Sallam.',
                'questions' => [
                    'q6' => 'Apakah Anda membaca shalawat kepada Nabi Muhammad hari ini?',
                    'q7' => 'Apakah Anda berusaha menjalankan sunnah Nabi (seperti makan/minum dengan duduk dan tangan kanan) hari ini?',
                    'q8' => 'Apakah Anda menyempatkan diri membaca doa/dzikir pagi atau petang hari ini?',
                    'q9' => 'Apakah Anda membaca doa harian (sebelum/sesudah tidur, makan, atau masuk kamar mandi) hari ini?',
                    'q10' => 'Apakah Anda mendengarkan, membaca, atau merenungkan hadits Rasulullah hari ini?'
                ]
            ],
            [
                'title' => '📚 Adab Belajar',
                'desc' => 'Menjaga ketertiban, kebersihan, kepatuhan, dan doa dalam menuntut ilmu.',
                'questions' => [
                    'q11' => 'Apakah Anda datang/masuk kelas tepat waktu dan menyiapkan peralatan belajar?',
                    'q12' => 'Apakah Anda menyimak penjelasan guru dengan khusyuk dan tidak mengobrol saat pelajaran?',
                    'q13' => 'Apakah Anda mencatat materi pelajaran dengan rapi dan tertib?',
                    'q14' => 'Apakah Anda mengawali dan mengakhiri belajar dengan berdoa?',
                    'q15' => 'Apakah Anda menjaga kebersihan dan kerapian tempat belajar Anda?'
                ]
            ],
        ];

        $json = self::get('adab_questions');
        if ($json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && count($decoded) === 3) {
                return $decoded;
            }
        }
        return $default;
    }
}
