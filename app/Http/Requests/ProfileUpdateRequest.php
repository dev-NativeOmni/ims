<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isAdmin = $this->user()?->hasAnyRole(['super_admin', 'admin']);

        return [
            'name' => $isAdmin ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'username' => $isAdmin ? [
                'required',
                'string',
                'lowercase',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ] : ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
