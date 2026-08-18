<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'                  => ['sometimes', 'string', 'max:120'],
            'email'                 => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'avatar'                => ['nullable', 'string', 'max:2048'],
            'language'              => ['sometimes', Rule::in(['ru', 'en', 'kk'])],
            'theme'                 => ['sometimes', Rule::in(['light', 'dark'])],
            'current_password'      => ['required_with:password', 'nullable', 'string'],
            'password'              => ['nullable', 'string', 'min:6', 'confirmed'],
        ];
    }
}
