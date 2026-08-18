<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'title'       => [$required, 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'status'      => ['sometimes', Rule::in(Task::STATUSES)],
            'priority'    => ['sometimes', Rule::in(Task::PRIORITIES)],
            'deadline'    => ['nullable', 'date'],
            'position'    => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
