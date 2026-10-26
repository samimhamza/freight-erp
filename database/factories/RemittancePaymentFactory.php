<?php

namespace Database\Factories;

use App\Models\RemittanceAdvice;
use App\Models\RemittancePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RemittancePayment>
 */
class RemittancePaymentFactory extends Factory
{
    protected $model = RemittancePayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_number' => (string) fake()->numberBetween(1, 999),
            'payment_reference' => fake()->unique()->bothify('RREF-#####'),
            'remittance_advice_id' => RemittanceAdvice::factory(),
            'date' => now(),
            'amount' => fake()->randomFloat(2, 50, 200),
        ];
    }
}
