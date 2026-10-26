<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Carrier;
use App\Models\MasterBill;
use App\Models\RemittanceAdvice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RemittanceAdvice>
 */
class RemittanceAdviceFactory extends Factory
{
    protected $model = RemittanceAdvice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'advice_number' => fake()->unique()->bothify('RADV-#####'),
            'master_bill_id' => MasterBill::factory(),
            'carrier_id' => function (array $attributes) {
                return MasterBill::find($attributes['master_bill_id'])?->carrier_id
                    ?? Carrier::factory();
            },
            'advice_date' => fake()->date(),
            'due_date' => fake()->date(),
            'status' => InvoiceStatus::DRAFT,
            'sent_at' => null,
        ];
    }
}

