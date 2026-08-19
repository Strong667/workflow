<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['sometimes', Rule::in($this->assignableRoles())],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'role.in' => 'Менеджер может выдавать только роли «Менеджер» и «Сотрудник»',
        ];
    }

    /** @return array<int, string> */
    private function assignableRoles(): array
    {
        return $this->user()?->isAdmin()
            ? [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_EMPLOYEE]
            : [User::ROLE_MANAGER, User::ROLE_EMPLOYEE];
    }
}
