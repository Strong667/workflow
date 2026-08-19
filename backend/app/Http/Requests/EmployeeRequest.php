<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        $employee = $this->route('employee');
        $isCreate = $this->isMethod('POST');

        return [
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            // Email — он же логин: уникален и среди карточек, и среди аккаунтов.
            'email'         => [
                'required', 'email', 'max:255',
                Rule::unique('employees', 'email')->ignore($employeeId),
                Rule::unique('users', 'email')->ignore($employee?->user_id),
            ],
            'phone'         => ['nullable', 'string', 'max:32'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position'      => ['nullable', 'string', 'max:120'],
            'hire_date'     => ['nullable', 'date'],
            'avatar'        => ['nullable', 'string', 'max:2048'],

            'role'          => [$isCreate ? 'required' : 'sometimes', Rule::in($this->assignableRoles())],
            'password'      => [$isCreate ? 'required' : 'nullable', 'string', 'min:6'],
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
     * Менеджер не выдаёт роль администратора: иначе роль повышала бы
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
