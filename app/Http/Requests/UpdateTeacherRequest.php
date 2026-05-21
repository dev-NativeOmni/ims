<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin')
            || $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        $teacher = $this->route('teacher');
        $userId = $teacher?->user_id;
        $teacherId = $teacher?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
            'employee_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('teacher_profiles', 'employee_number')->ignore($teacherId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama guru',
            'email' => 'email',
            'password' => 'password',
            'status' => 'status',
            'employee_number' => 'nomor pegawai',
            'phone' => 'nomor telepon',
        ];
    }
}
