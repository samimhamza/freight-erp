<?php

namespace Tests\Feature;

use App\Enums\ContainerType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Widgets\FinancialPerformanceChart;
use App\Models\Carrier;
use App\Models\Charge;
use App\Models\Consignor;
use App\Models\Container;
use App\Models\Customer;
use App\Models\HouseBill;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MasterBill;
use App\Models\RemittanceAdvice;
use App\Models\RemittanceAdviceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialPerformanceChartTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected User $user;

    protected ?\Closure $gateCallback = null;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(function ($user, $ability, $arguments) {
            if ($this->gateCallback !== null) {
                return ($this->gateCallback)($user, $ability, $arguments);
            }

            return true;
        });

        $this->user = User::factory()->create();
    }

    protected function createInvoice(array $attributes = []): Invoice
    {
        $carrier = Carrier::factory()->create();
        $masterBill = MasterBill::factory()->create(['carrier_id' => $carrier->id]);
        $customer = Customer::create([
            'name' => 'Acme Test Customer '.fake()->unique()->numerify('#####'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('97150#######'),
        ]);
        $consignor = Consignor::create([
            'name' => 'Consignor '.fake()->unique()->numerify('#####'),
        ]);
        $houseBill = HouseBill::create([
            'master_bill_id' => $masterBill->id,
            'house_bill' => 'HBL-'.fake()->unique()->numerify('#####'),
            'consignor_id' => $consignor->id,
            'customer_id' => $customer->id,
            'notify_party_id' => $customer->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
        $container = Container::create([
            'container_number' => 'CONT'.fake()->unique()->numerify('#######'),
            'type' => ContainerType::HC40,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        return Invoice::create(array_merge([
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'type' => InvoiceType::CLEARANCE,
            'customer_id' => $customer->id,
            'house_bill_id' => $houseBill->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => InvoiceStatus::PAID,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ], $attributes));
    }

    public function test_financial_performance_chart_renders_successfully(): void
    {
        $this->actingAs($this->user);

        Livewire::test(FinancialPerformanceChart::class)
            ->assertSuccessful()
            ->assertSee('Financial Performance');
    }

    public function test_chart_defaults_to_monthly_filter_with_twelve_months_data(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(FinancialPerformanceChart::class);

        $this->assertSame('monthly', $component->get('filter'));

        $data = $component->instance()->getCachedData();

        $this->assertCount(3, $data['datasets']);
        $this->assertSame('Customer Invoices (Revenue)', $data['datasets'][0]['label']);
        $this->assertSame('Remittance Advices (Expenses)', $data['datasets'][1]['label']);
        $this->assertSame('Net Margin', $data['datasets'][2]['label']);
        $this->assertCount(12, $data['labels']);
        $this->assertSame(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], $data['labels']);
    }

    public function test_chart_switches_to_quarterly_filter(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(FinancialPerformanceChart::class)
            ->set('filter', 'quarterly');

        $this->assertSame('quarterly', $component->get('filter'));

        $data = $component->instance()->getCachedData();

        $this->assertCount(3, $data['datasets']);
        $this->assertCount(4, $data['labels']);
        $this->assertSame(['Q1 (Jan - Mar)', 'Q2 (Apr - Jun)', 'Q3 (Jul - Sep)', 'Q4 (Oct - Dec)'], $data['labels']);
    }

    public function test_chart_switches_to_yearly_filter(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(FinancialPerformanceChart::class)
            ->set('filter', 'yearly');

        $this->assertSame('yearly', $component->get('filter'));

        $data = $component->instance()->getCachedData();

        $currentYear = now()->year;
        $expectedYears = [
            (string) ($currentYear - 4),
            (string) ($currentYear - 3),
            (string) ($currentYear - 2),
            (string) ($currentYear - 1),
            (string) $currentYear,
        ];

        $this->assertCount(3, $data['datasets']);
        $this->assertCount(5, $data['labels']);
        $this->assertSame($expectedYears, $data['labels']);
    }

    public function test_chart_calculates_revenue_expenses_and_net_correctly_for_monthly_view(): void
    {
        $this->actingAs($this->user);

        $currentYear = now()->year;
        $charge = Charge::factory()->create();
        $carrier = Carrier::factory()->create();
        // Create March Customer Invoice (Month 3): 2 x 500 = 1000 + 50 VAT = 1050
        $marchInvoice = $this->createInvoice([
            'invoice_number' => 'INV-TEST-MARCH',
            'invoice_date' => Carbon::create($currentYear, 3, 15)->toDateString(),
            'due_date' => Carbon::create($currentYear, 4, 15)->toDateString(),
            'status' => InvoiceStatus::PAID,
        ]);

        InvoiceItem::create([
            'invoice_id' => $marchInvoice->id,
            'charge_id' => $charge->id,
            'quantity' => 2,
            'amount' => 500.00,
            'vat' => 50.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // Create March Remittance Advice (Month 3): 1 x 400 = 400 + 20 VAT = 420
        $masterBill = MasterBill::factory()->create(['carrier_id' => $carrier->id]);
        $marchPayable = RemittanceAdvice::create([
            'advice_number' => 'RADV-TEST-MARCH',
            'carrier_id' => $carrier->id,
            'master_bill_id' => $masterBill->id,
            'advice_date' => Carbon::create($currentYear, 3, 20)->toDateString(),
            'due_date' => Carbon::create($currentYear, 4, 20)->toDateString(),
            'status' => InvoiceStatus::PAID,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        RemittanceAdviceItem::create([
            'remittance_advice_id' => $marchPayable->id,
            'charge_id' => $charge->id,
            'quantity' => 1,
            'amount' => 400.00,
            'vat' => 20.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $component = Livewire::test(FinancialPerformanceChart::class);
        $data = $component->instance()->getCachedData();

        // Index 2 corresponds to March (month 3, 0-indexed)
        $this->assertEquals(1050.00, $data['datasets'][0]['data'][2]);
        $this->assertEquals(420.00, $data['datasets'][1]['data'][2]);
        $this->assertEquals(630.00, $data['datasets'][2]['data'][2]);

        // Also test quarterly aggregation (March is in Q1, index 0)
        $quarterlyComponent = Livewire::test(FinancialPerformanceChart::class)
            ->set('filter', 'quarterly');
        $quarterlyData = $quarterlyComponent->instance()->getCachedData();

        $this->assertEquals(1050.00, $quarterlyData['datasets'][0]['data'][0]);
        $this->assertEquals(420.00, $quarterlyData['datasets'][1]['data'][0]);
        $this->assertEquals(630.00, $quarterlyData['datasets'][2]['data'][0]);
    }

    public function test_chart_excludes_cancelled_invoices(): void
    {
        $this->actingAs($this->user);

        $currentYear = now()->year;
        $charge = Charge::factory()->create();
        // Cancelled invoice
        $cancelledInvoice = $this->createInvoice([
            'invoice_number' => 'INV-TEST-CANCELLED',
            'invoice_date' => Carbon::create($currentYear, 5, 10)->toDateString(),
            'due_date' => Carbon::create($currentYear, 6, 10)->toDateString(),
            'status' => InvoiceStatus::CANCELLED,
        ]);

        InvoiceItem::create([
            'invoice_id' => $cancelledInvoice->id,
            'charge_id' => $charge->id,
            'quantity' => 1,
            'amount' => 5000.00,
            'vat' => 250.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $component = Livewire::test(FinancialPerformanceChart::class);
        $data = $component->instance()->getCachedData();

        // Index 4 corresponds to May (month 5, 0-indexed)
        $this->assertEquals(0.00, $data['datasets'][0]['data'][4]);
    }

    public function test_description_includes_aggregate_metrics(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(FinancialPerformanceChart::class);
        $description = $component->instance()->getDescription();

        $this->assertStringContainsString('This Year (Monthly)', $description);
        $this->assertStringContainsString('Invoiced: AED', $description);
        $this->assertStringContainsString('Payables: AED', $description);
        $this->assertStringContainsString('Net Margin: AED', $description);
    }

    public function test_user_with_only_invoice_permission_sees_only_revenue_dataset(): void
    {
        $this->gateCallback = fn ($user, $ability, $args) => ($args[0] ?? null) === Invoice::class;

        $this->actingAs($this->user);

        $this->assertTrue(FinancialPerformanceChart::canView());

        $component = Livewire::test(FinancialPerformanceChart::class);
        $data = $component->instance()->getCachedData();

        $this->assertCount(1, $data['datasets']);
        $this->assertSame('Customer Invoices (Revenue)', $data['datasets'][0]['label']);

        $description = $component->instance()->getDescription();
        $this->assertStringContainsString('Invoiced: AED', $description);
        $this->assertStringNotContainsString('Payables: AED', $description);
        $this->assertStringNotContainsString('Net Margin: AED', $description);
    }

    public function test_user_with_only_payable_invoice_permission_sees_only_payables_dataset(): void
    {
        $this->gateCallback = fn ($user, $ability, $args) => ($args[0] ?? null) === RemittanceAdvice::class;

        $this->actingAs($this->user);

        $this->assertTrue(FinancialPerformanceChart::canView());

        $component = Livewire::test(FinancialPerformanceChart::class);
        $data = $component->instance()->getCachedData();

        $this->assertCount(1, $data['datasets']);
        $this->assertSame('Remittance Advices (Expenses)', $data['datasets'][0]['label']);

        $description = $component->instance()->getDescription();
        $this->assertStringContainsString('Payables: AED', $description);
        $this->assertStringNotContainsString('Invoiced: AED', $description);
        $this->assertStringNotContainsString('Net Margin: AED', $description);
    }

    public function test_user_with_no_invoice_permissions_cannot_view_financial_performance_chart(): void
    {
        $this->gateCallback = fn () => false;

        $this->actingAs($this->user);

        $this->assertFalse(FinancialPerformanceChart::canView());
    }
}
