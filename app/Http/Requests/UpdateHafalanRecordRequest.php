<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHafalanRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin', 'teacher']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->teacherProfile?->id) {
            $this->merge([
                'teacher_id' => $this->user()->teacherProfile->id,
            ]);
        } else {
            $student = Student::find($this->input('student_id'));
            if ($student) {
                $this->merge([
                    'teacher_id' => $student->teacher_id,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],
            'teacher_id' => [
                Rule::requiredIf(! $this->user()?->hasRole('teacher')),
                'nullable',
                'integer',
                Rule::exists('teacher_profiles', 'id'),
            ],
            'surah_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'surah_ids.*' => [
                'required',
                'integer',
                Rule::exists('surahs', 'id'),
            ],
            'ayah_starts' => [
                'required',
                'array',
                'min:1',
            ],
            'ayah_starts.*' => [
                'required',
                'integer',
                'min:1',
            ],
            'ayah_ends' => [
                'required',
                'array',
                'min:1',
            ],
            'ayah_ends.*' => [
                'required',
                'integer',
                'min:1',
            ],
            'submission_types' => [
                'required',
                'array',
                'min:1',
            ],
            'submission_types.*' => [
                'required',
                Rule::in(['new', 'continuation', 'revision']),
            ],
            'scores' => [
                'nullable',
                'array',
            ],
            'scores.*' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'statuses' => [
                'required',
                'array',
                'min:1',
            ],
            'statuses.*' => [
                'required',
                Rule::in(['passed', 'repeat', 'needs_improvement']),
            ],
            'baris' => [
                'nullable',
                'array',
            ],
            'baris.*' => [
                'nullable',
                'numeric',
                'min:0',
                'max:500',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'submitted_at' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $surahIds = $this->input('surah_ids') ?: [];
            $ayahStarts = $this->input('ayah_starts') ?: [];
            $ayahEnds = $this->input('ayah_ends') ?: [];

            foreach ($surahIds as $idx => $surahId) {
                $surah = Surah::find($surahId);
                if ($surah) {
                    $ayahStart = $ayahStarts[$idx] ?? null;
                    $ayahEnd = $ayahEnds[$idx] ?? null;

                    if ($ayahStart !== null && $ayahEnd !== null) {
                        if ((int) $ayahEnd < (int) $ayahStart) {
                            $validator->errors()->add(
                                "ayah_ends.{$idx}",
                                'Ayat akhir harus lebih besar atau sama dengan ayat mulai.'
                            );
                        }

                        if ((int) $ayahEnd > $surah->total_ayah) {
                            $validator->errors()->add(
                                "ayah_ends.{$idx}",
                                'Ayat akhir tidak boleh melebihi jumlah ayat surah '.$surah->name_latin.' ('.$surah->total_ayah.' ayat).'
                            );
                        }
                    }
                }
            }

            $student = Student::find($this->input('student_id'));

            if ($student && $student->status !== 'active') {
                $validator->errors()->add(
                    'student_id',
                    'Murid nonaktif tidak bisa menerima input setoran hafalan.'
                );
            }

            if ($student && ! $student->teacher_id) {
                $fallbackTeacherId = $this->user()?->teacherProfile?->id ?? TeacherProfile::query()->value('id');
                if ($fallbackTeacherId) {
                    $student->update(['teacher_id' => $fallbackTeacherId]);
                    $this->merge(['teacher_id' => $fallbackTeacherId]);
                } else {
                    $validator->errors()->add(
                        'student_id',
                        'Murid ini belum memiliki guru pembimbing.'
                    );
                }
            }

            if ($student && $this->user()?->hasRole('teacher')) {
                $teacherId = $this->user()?->teacherProfile?->id;

                if (! $teacherId || ((int) $student->teacher_id !== (int) $teacherId)) {
                    $validator->errors()->add(
                        'student_id',
                        'Guru hanya boleh mengubah setoran untuk murid bimbingannya.'
                    );
                }
            }

            // Duplicate passed setoran prevention on the same date (excluding current record)
            $submittedAt = $this->input('submitted_at');
            $studentId = $this->input('student_id');
            $statuses = $this->input('statuses') ?: [];
            $currentRecordId = $this->route('hafalanRecord')?->id ?? $this->route('hafalan_record');

            if ($submittedAt && $studentId) {
                foreach ($surahIds as $idx => $surahId) {
                    $surah = Surah::find($surahId);
                    $aStart = (int) ($ayahStarts[$idx] ?? 1);
                    $aEnd = (int) ($ayahEnds[$idx] ?? 1);
                    $status = $statuses[$idx] ?? 'passed';

                    if ($surah && $status === 'passed') {
                        $duplicateQuery = \App\Models\HafalanRecordSurah::query()
                            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
                            ->whereNull('hafalan_records.deleted_at')
                            ->where('hafalan_records.student_id', $studentId)
                            ->whereDate('hafalan_records.submitted_at', $submittedAt)
                            ->where('hafalan_record_surahs.surah_id', $surahId)
                            ->where('hafalan_record_surahs.ayah_start', $aStart)
                            ->where('hafalan_record_surahs.ayah_end', $aEnd)
                            ->where('hafalan_record_surahs.status', 'passed');

                        if ($currentRecordId) {
                            $duplicateQuery->where('hafalan_records.id', '!=', $currentRecordId);
                        }

                        if ($duplicateQuery->exists()) {
                            $validator->errors()->add(
                                "surah_ids.{$idx}",
                                "Setoran untuk surah {$surah->name_latin} ayat {$aStart}-{$aEnd} pada tanggal ini sudah tercatat pada data lain."
                            );
                        }
                    }
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'murid',
            'surah_ids' => 'surah',
            'surah_ids.*' => 'surah',
            'ayah_starts.*' => 'ayat mulai',
            'ayah_ends.*' => 'ayat akhir',
            'submission_types.*' => 'jenis setoran',
            'scores.*' => 'nilai',
            'statuses.*' => 'status setoran',
            'notes' => 'catatan',
            'submitted_at' => 'tanggal setoran',
        ];
    }
}
