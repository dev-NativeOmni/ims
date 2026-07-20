<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin')
            || $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'program_id' => [
                'required',
                'integer',
                Rule::exists('programs', 'id'),
            ],
            'pendamping_adab_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'level' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'program_id' => 'program',
            'pendamping_adab_id' => 'pendamping adab',
            'name' => 'nama kelas',
            'level' => 'level',
        ];
    }
}
