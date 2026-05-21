<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin')
            || $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        $parent = $this->route('parent');
        $userId = $parent?->user_id;

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
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'address' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama orangtua/wali',
            'email' => 'email',
            'password' => 'password',
            'status' => 'status',
            'phone' => 'nomor telepon',
            'address' => 'alamat',
        ];
    }
}