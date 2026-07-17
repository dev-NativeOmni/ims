<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HafalanRecordResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'student_id' => $this->student_id,
            'teacher_id' => $this->teacher_id,
            'surah_id' => $this->surah_id,

            'ayah_start' => $this->ayah_start,
            'ayah_end' => $this->ayah_end,
            'ayah_range' => $this->ayah_start.' - '.$this->ayah_end,

            'submission_type' => $this->submission_type,
            'submission_type_label' => $this->submission_type_label ?? $this->submissionTypeLabel(),

            'score' => $this->score,
            'status' => $this->status,
            'status_label' => $this->status_label ?? $this->statusLabel(),

            'notes' => $this->notes,
            'submitted_at' => optional($this->submitted_at)->format('Y-m-d'),

            'student' => $this->whenLoaded('student', function () {
                return $this->student ? [
                    'id' => $this->student->id,
                    'user_id' => $this->student->user_id,
                    'class_room_id' => $this->student->class_room_id,
                    'teacher_id' => $this->student->teacher_id,
                    'student_number' => $this->student->student_number,
                    'name' => $this->student->name,
                    'gender' => $this->student->gender,
                    'status' => $this->student->status,
                    'class_room' => $this->student->relationLoaded('classRoom') && $this->student->classRoom ? [
                        'id' => $this->student->classRoom->id,
                        'program_id' => $this->student->classRoom->program_id,
                        'name' => $this->student->classRoom->name,
                        'program' => $this->student->classRoom->relationLoaded('program') && $this->student->classRoom->program ? [
                            'id' => $this->student->classRoom->program->id,
                            'name' => $this->student->classRoom->program->name,
                        ] : null,
                    ] : null,
                ] : null;
            }),

            'teacher' => $this->whenLoaded('teacher', function () {
                return $this->teacher ? [
                    'id' => $this->teacher->id,
                    'employee_number' => $this->teacher->employee_number,
                    'phone' => $this->teacher->phone,
                    'user' => $this->teacher->relationLoaded('user') && $this->teacher->user ? [
                        'id' => $this->teacher->user->id,
                        'name' => $this->teacher->user->name,
                        'email' => $this->teacher->user->email,
                    ] : null,
                ] : null;
            }),

            'surah' => $this->whenLoaded('surah', function () {
                return $this->surah ? [
                    'id' => $this->surah->id,
                    'number' => $this->surah->number,
                    'name_ar' => $this->surah->name_ar,
                    'name_latin' => $this->surah->name_latin,
                    'total_ayah' => $this->surah->total_ayah,
                    'juz_start' => $this->surah->juz_start,
                    'juz_end' => $this->surah->juz_end,
                ] : null;
            }),

            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }

    private function submissionTypeLabel(): string
    {
        return match ($this->submission_type) {
            'new' => 'Baru',
            'continuation' => 'Lanjutan',
            'revision' => 'Perbaikan',
            default => '-',
        };
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            'passed' => 'Lulus',
            'repeat' => 'Ulang',
            'needs_improvement' => 'Perlu Perbaikan',
            default => '-',
        };
    }
}
