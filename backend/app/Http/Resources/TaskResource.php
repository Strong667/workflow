<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'employee_id' => $this->employee_id,
            'employee'    => new EmployeeResource($this->whenLoaded('employee')),
            'status'      => $this->status,
            'priority'    => $this->priority,
            'deadline'    => $this->deadline?->toDateString(),
            'position'    => $this->position,
            'is_overdue'  => $this->isOverdue(),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
