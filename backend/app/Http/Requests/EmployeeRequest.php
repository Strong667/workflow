<?php

namespace App\Http\Requests;

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

        return [
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'phone'         => ['nullable', 'string', 'max:32'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position'      => ['nullable', 'string', 'max:120'],
            'hire_date'     => ['nullable', 'date'],
            'avatar'        => ['nullable', 'string', 'max:2048'],
        ];
    }
}
