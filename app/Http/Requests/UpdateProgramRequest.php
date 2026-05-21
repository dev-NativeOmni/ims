<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin')
            || $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        $programId = $this->route('program')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('programs', 'name')->ignore($programId),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama program',
            'description' => 'deskripsi',
            'status' => 'status',
        ];
    }
}