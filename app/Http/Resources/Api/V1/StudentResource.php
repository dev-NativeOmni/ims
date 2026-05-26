<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'class_room_id' => $this->class_room_id,
            'teacher_id' => $this->teacher_id,
            'student_number' => $this->student_number,
            'name' => $this->name,
            'gender' => $this->gender,
            'birth_date' => optional($this->birth_date)->format('Y-m-d'),
            'status' => $this->status,

            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'status' => $this->user->status,
                ] : null;
            }),

            'class_room' => $this->whenLoaded('classRoom', function () {
                return $this->classRoom ? [
                    'id' => $this->classRoom->id,
                    'program_id' => $this->classRoom->program_id,
                    'name' => $this->classRoom->name,
                    'level' => $this->classRoom->level ?? null,
                    'status' => $this->classRoom->status ?? null,
                    'program' => $this->classRoom->relationLoaded('program') && $this->classRoom->program ? [
                        'id' => $this->classRoom->program->id,
                        'name' => $this->classRoom->program->name,
                        'status' => $this->classRoom->program->status ?? null,
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

            'parents' => $this->whenLoaded('parents', function () {
                return $this->parents->map(function ($parent) {
                    return [
                        'id' => $parent->id,
                        'phone' => $parent->phone,
                        'relation' => $parent->pivot?->relation,
                        'user' => $parent->relationLoaded('user') && $parent->user ? [
                            'id' => $parent->user->id,
                            'name' => $parent->user->name,
                            'email' => $parent->user->email,
                        ] : null,
                    ];
                })->values();
            }),
        ];
    }
}