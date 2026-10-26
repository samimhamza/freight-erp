<?php

namespace Database\Factories;

use App\Models\ImportAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportAgent>
 */
class ImportAgentFactory extends Factory
{
    protected $model = ImportAgent::class;

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
