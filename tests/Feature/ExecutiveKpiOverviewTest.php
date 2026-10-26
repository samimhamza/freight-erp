<?php

namespace Tests\Feature;

use App\Enums\ContainerType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Widgets\ExecutiveKpiOverview;
use App\Models\Carrier;
use App\Models\Charge;
use App\Models\Consignor;
use App\Models\Container;
use App\Models\Customer;
use App\Models\HouseBill;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MasterBill;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ExecutiveKpiOverviewTest extends TestCase
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
            'name' => 'Acme Customer '.fake()->unique()->numerify('#####'),
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

    public function test_executive_kpi_overview_renders_successfully(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ExecutiveKpiOverview::class)
            ->assertSuccessful()
            ->assertSee('Revenue (YTD)')
            ->assertSee('Outstanding Receivables')
            ->assertSee('Active Shipments')
            ->assertSee('Total Customers');
    }

    public function test_stats_contain_four_cards_with_six_point_sparklines(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ExecutiveKpiOverview::class);
        $stats = $component->instance()->getStats();

        $this->assertCount(4, $stats);

        $this->assertSame('Revenue (YTD)', $stats[0]->getLabel());
        $this->assertSame('Outstanding Receivables', $stats[1]->getLabel());
        $this->assertSame('Active Shipments', $stats[2]->getLabel());
        $this->assertSame('Total Customers', $stats[3]->getLabel());

        // Each card has a 6-month mini sparkline
        foreach ($stats as $stat) {
            $chart = $stat->getChart();
            $this->assertIsArray($chart);
            $this->assertCount(6, $chart);
        }
    }

    public function test_revenue_ytd_accurately_reflects_billed_invoices(): void
    {
        $this->actingAs($this->user);

        $currentYear = now()->year;
        $charge = Charge::factory()->create();

        $invoice = $this->createInvoice([
            'invoice_number' => 'INV-KPI-001',
            'invoice_date' => Carbon::create($currentYear, 2, 10)->toDateString(),
            'due_date' => Carbon::create($currentYear, 3, 10)->toDateString(),
            'status' => InvoiceStatus::PAID,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'charge_id' => $charge->id,
            'quantity' => 3,
            'amount' => 1000.00,
            'vat' => 150.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $component = Livewire::test(ExecutiveKpiOverview::class);
        $stats = $component->instance()->getStats();

        // 3 x 1000 = 3000 + 150 VAT = 3150.00
        $this->assertSame('AED 3,150.00', $stats[0]->getValue());
    }

    public function test_outstanding_receivables_reflects_unpaid_balance(): void
    {
        $this->actingAs($this->user);

        $charge = Charge::factory()->create();

        // Invoice of 2,000 + 100 VAT = 2,100
        $invoice = $this->createInvoice([
            'invoice_number' => 'INV-KPI-REC',
            'invoice_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->addDays(25)->toDateString(),
            'status' => InvoiceStatus::PARTIALLY_PAID,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'charge_id' => $charge->id,
            'quantity' => 1,
            'amount' => 2000.00,
            'vat' => 100.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // Partial payment of 600
        Payment::create([
            'payment_number' => 'PAY-KPI-001',
            'payment_reference' => 'REF-KPI-001',
            'invoice_id' => $invoice->id,
            'date' => now()->toDateString(),
            'amount' => 600.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $component = Livewire::test(ExecutiveKpiOverview::class);
        $stats = $component->instance()->getStats();

        // 2,100 - 600 = 1,500.00 remaining
        $this->assertSame('AED 1,500.00', $stats[1]->getValue());
    }

    public function test_active_shipments_counts_in_transit_bills_only(): void
    {
        $this->actingAs($this->user);

        $carrier = Carrier::factory()->create();
        $customer = Customer::create([
            'name' => 'Shipment Client',
            'email' => 'shipment@client.ae',
            'phone' => '0551122334',
        ]);
        $consignor = Consignor::create([
            'name' => 'Consignor Port',
            'email' => 'port@consignor.com',
            'phone' => '0559988776',
        ]);

        // In-transit Master Bill (ata is NULL)
        $activeMbl = MasterBill::create([
            'date' => now()->subDays(3),
            'console' => 'CNS-ACTIVE-001',
            'ocean_bill_of_lading' => 'OBL-ACTIVE-001',
            'carrier_id' => $carrier->id,
            'etd' => now()->subDays(2)->toDateString(),
            'eta' => now()->addDays(10)->toDateString(),
            'ata' => null,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // House Bill attached to active Master Bill
        HouseBill::create([
            'master_bill_id' => $activeMbl->id,
            'house_bill' => 'HBL-ACTIVE-001',
            'consignor_id' => $consignor->id,
            'customer_id' => $customer->id,
            'notify_party_id' => $customer->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // Completed Master Bill (ata is SET)
        MasterBill::create([
            'date' => now()->subDays(20),
            'console' => 'CNS-COMPLETED-001',
            'ocean_bill_of_lading' => 'OBL-COMPLETED-001',
            'carrier_id' => $carrier->id,
            'etd' => now()->subDays(18)->toDateString(),
            'eta' => now()->subDays(5)->toDateString(),
            'ata' => now()->subDays(5)->toDateString(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $component = Livewire::test(ExecutiveKpiOverview::class);
        $stats = $component->instance()->getStats();

        // 1 active MBL and 1 linked HBL
        $this->assertSame('1 MBL', $stats[2]->getValue());
        $this->assertStringContainsString('1 linked House Bills', $stats[2]->getDescription());
    }

    public function test_total_customers_stat_reflects_registered_clients(): void
    {
        $this->actingAs($this->user);

        Customer::create([
            'name' => 'Client Alpha',
            'email' => 'alpha@client.com',
            'phone' => '0521111111',
        ]);

        Customer::create([
            'name' => 'Client Beta',
            'email' => 'beta@client.com',
            'phone' => '0522222222',
        ]);

        $component = Livewire::test(ExecutiveKpiOverview::class);
        $stats = $component->instance()->getStats();

        $this->assertSame('2', $stats[3]->getValue());
    }

    public function test_user_with_only_invoice_permission_sees_revenue_and_receivables_cards(): void
    {
        $this->gateCallback = fn ($user, $ability, $args) => ($args[0] ?? null) === Invoice::class;

        $this->actingAs($this->user);

        $this->assertTrue(ExecutiveKpiOverview::canView());

        $component = Livewire::test(ExecutiveKpiOverview::class);
        $stats = $component->instance()->getStats();

        $this->assertCount(2, $stats);
        $this->assertSame('Revenue (YTD)', $stats[0]->getLabel());
        $this->assertSame('Outstanding Receivables', $stats[1]->getLabel());
    }

    public function test_user_with_only_master_bill_permission_sees_active_shipments_card(): void
    {
        $this->gateCallback = fn ($user, $ability, $args) => ($args[0] ?? null) === MasterBill::class;

        $this->actingAs($this->user);

        $this->assertTrue(ExecutiveKpiOverview::canView());

        $component = Livewire::test(ExecutiveKpiOverview::class);
        $stats = $component->instance()->getStats();

        $this->assertCount(1, $stats);
        $this->assertSame('Active Shipments', $stats[0]->getLabel());
    }

    public function test_user_with_only_customer_permission_sees_total_customers_card(): void
    {
        $this->gateCallback = fn ($user, $ability, $args) => ($args[0] ?? null) === Customer::class;

        $this->actingAs($this->user);

        $this->assertTrue(ExecutiveKpiOverview::canView());

        $component = Livewire::test(ExecutiveKpiOverview::class);
        $stats = $component->instance()->getStats();

        $this->assertCount(1, $stats);
        $this->assertSame('Total Customers', $stats[0]->getLabel());
    }

    public function test_user_with_no_permissions_cannot_view_executive_kpi_overview(): void
    {
        $this->gateCallback = fn () => false;

        $this->actingAs($this->user);

        $this->assertFalse(ExecutiveKpiOverview::canView());
    }
}
