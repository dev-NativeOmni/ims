<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\Surah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMurajaahRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin', 'teacher']) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],
            'surah_id' => [
                'required',
                'integer',
                Rule::exists('surahs', 'id'),
            ],
            'ayah_start' => [
                'required',
                'integer',
                'min:1',
            ],
            'ayah_end' => [
                'required',
                'integer',
                'min:1',
                'gte:ayah_start',
            ],
            'fluency_score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'tajwid_score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'makhraj_score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'overall_score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'status' => [
                'required',
                Rule::in(['passed', 'repeat', 'needs_improvement']),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'reviewed_at' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $surah = Surah::find($this->input('surah_id'));

            if ($surah && (int) $this->input('ayah_end') > $surah->total_ayah) {
                $validator->errors()->add(
                    'ayah_end',
                    'Ayat akhir tidak boleh melebihi jumlah ayat surah ' . $surah->name_latin . ' (' . $surah->total_ayah . ' ayat).'
                );
            }

            $student = Student::find($this->input('student_id'));

            if ($student && $student->status !== 'active') {
                $validator->errors()->add(
                    'student_id',
                    'Santri nonaktif tidak bisa menerima input murajaah.'
                );
            }

            if ($student && ! $student->teacher_id) {
                $validator->errors()->add(
                    'student_id',
                    'Santri ini belum memiliki guru pembimbing.'
                );
            }

            if ($student && $this->user()?->hasRole('teacher')) {
                $teacherId = $this->user()?->teacherProfile?->id;

                if (! $teacherId || (int) $student->teacher_id !== (int) $teacherId) {
                    $validator->errors()->add(
                        'student_id',
                        'Guru hanya boleh mengubah murajaah untuk santri bimbingannya.'
                    );
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'santri',
            'surah_id' => 'surah',
            'ayah_start' => 'ayat mulai',
            'ayah_end' => 'ayat akhir',
            'fluency_score' => 'nilai kelancaran',
            'tajwid_score' => 'nilai tajwid',
            'makhraj_score' => 'nilai makhraj',
            'overall_score' => 'nilai keseluruhan',
            'status' => 'status murajaah',
            'notes' => 'catatan',
            'reviewed_at' => 'tanggal murajaah',
        ];
    }
}