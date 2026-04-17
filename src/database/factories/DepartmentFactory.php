<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('dept???')),
            'name' => $this->faker->unique()->words(2, true) . ' Department',
            'department_type' => 'teaching',
            'is_active' => true,
        ];
    }
}
