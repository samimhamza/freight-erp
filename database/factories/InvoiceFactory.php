<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\HouseBill;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'type' => InvoiceType::SHIPPING,
            'customer_id' => Customer::factory(),
            'house_bill_id' => HouseBill::factory(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => InvoiceStatus::DRAFT,
        ];
    }

    public function clearance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InvoiceType::CLEARANCE,
        ]);
    }

    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InvoiceType::SHIPPING,
        ]);
    }

    public function houseBill(): static
    {
        return $this->shipping();
    }
}
