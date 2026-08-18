<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title'       => rtrim($this->faker->sentence(4), '.'),
            'description' => $this->faker->paragraph(),
            'employee_id' => Employee::inRandomOrder()->value('id'),
            'status'      => $this->faker->randomElement(Task::STATUSES),
            'priority'    => $this->faker->randomElement(Task::PRIORITIES),
            'deadline'    => $this->faker->dateTimeBetween('-10 days', '+40 days')->format('Y-m-d'),
            'position'    => 0,
        ];
    }
}
