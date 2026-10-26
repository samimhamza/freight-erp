<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\MasterBill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterBill>
 */
class MasterBillFactory extends Factory
{
    protected $model = MasterBill::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTime(),
            'console' => fake()->unique()->bothify('CNS-#####'),
            'ocean_bill_of_lading' => fake()->unique()->bothify('OBL-#####'),
            'carrier_id' => Carrier::factory(),
            'etd' => fake()->date(),
            'eta' => fake()->date(),
        ];
    }
}
