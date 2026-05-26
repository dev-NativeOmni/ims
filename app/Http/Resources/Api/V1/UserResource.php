<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role_id' => $this->role_id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'email_verified_at' => optional($this->email_verified_at)->toISOString(),

            'role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'display_name' => $this->role->display_name,
            ] : null,

            'profiles' => [
                'teacher' => $this->teacherProfile ? [
                    'id' => $this->teacherProfile->id,
                    'employee_number' => $this->teacherProfile->employee_number,
                    'phone' => $this->teacherProfile->phone,
                ] : null,

                'parent' => $this->parentProfile ? [
                    'id' => $this->parentProfile->id,
                    'phone' => $this->parentProfile->phone,
                ] : null,

                'student' => $this->studentProfile ? [
                    'id' => $this->studentProfile->id,
                    'student_number' => $this->studentProfile->student_number,
                    'name' => $this->studentProfile->name,
                    'status' => $this->studentProfile->status,
                ] : null,
            ],
        ];
    }
}