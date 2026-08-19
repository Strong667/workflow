<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId    = $this->route('user')?->id;
        $isCreate  = $this->isMethod('POST');

        return [
            'name'     => [$isCreate ? 'required' : 'sometimes', 'string', 'max:120'],
            'email'    => [$isCreate ? 'required' : 'sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role'     => [$isCreate ? 'required' : 'sometimes', Rule::in($this->assignableRoles())],
            'password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:6'],
            'language' => ['sometimes', Rule::in(['ru', 'en', 'kk'])],
            'theme'    => ['sometimes', Rule::in(['light', 'dark'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'role.in' => 'Менеджер может выдавать только роли «Менеджер» и «Сотрудник»',
        ];
    }

    /**
     * Менеджер не может создать администратора: иначе роль повышала бы
     * саму себя в обход ограничений.
     *
     * @return array<int, string>
     */
    private function assignableRoles(): array
    {
        return $this->user()?->isAdmin()
            ? [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_EMPLOYEE]
            : [User::ROLE_MANAGER, User::ROLE_EMPLOYEE];
    }
}
