<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last  = $this->faker->lastName();

        return [
            'first_name'    => $first,
            'last_name'     => $last,
            'email'         => $this->faker->unique()->safeEmail(),
            'phone'         => '+7 700 '.$this->faker->numberBetween(1000000, 9999999),
            'department_id' => Department::inRandomOrder()->value('id'),
            'position'      => $this->faker->jobTitle(),
            'hire_date'     => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'avatar'        => null,
        ];
    }
}
