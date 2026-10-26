<?php

namespace Database\Factories;

use App\Models\ExportAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportAgent>
 */
class ExportAgentFactory extends Factory
{
    protected $model = ExportAgent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'fax' => fake()->phoneNumber(),
            'address' => fake()->address(),
        ];
    }
}
