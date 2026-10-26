<?php

namespace Database\Factories;

use App\Models\Charge;
use App\Models\RemittanceAdvice;
use App\Models\RemittanceAdviceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RemittanceAdviceItem>
 */
class RemittanceAdviceItemFactory extends Factory
{
    protected $model = RemittanceAdviceItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'remittance_advice_id' => RemittanceAdvice::factory(),
            'charge_id' => Charge::factory(),
            'quantity' => 1,
            'amount' => fake()->randomFloat(2, 50, 500),
            'vat' => fake()->randomFloat(2, 0, 25),
        ];
    }
}

