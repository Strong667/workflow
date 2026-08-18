<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'full_name'     => $this->full_name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'department_id' => $this->department_id,
            'department'    => new DepartmentResource($this->whenLoaded('department')),
            'position'      => $this->position,
            'hire_date'     => $this->hire_date?->toDateString(),
            'avatar'        => $this->avatar,
            'tasks_count'   => $this->whenCounted('tasks'),
            'tasks'         => TaskResource::collection($this->whenLoaded('tasks')),
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
