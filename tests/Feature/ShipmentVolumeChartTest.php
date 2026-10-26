<?php

namespace Tests\Feature;

use App\Filament\Widgets\ShipmentVolumeChart;
use App\Models\Carrier;
use App\Models\Consignor;
use App\Models\Customer;
use App\Models\HouseBill;
use App\Models\MasterBill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentVolumeChartTest extends TestCase
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

    public function test_shipment_volume_chart_renders_successfully(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ShipmentVolumeChart::class)
            ->assertSuccessful()
            ->assertSee('Shipment & Operations Volume');
    }

    public function test_chart_defaults_to_monthly_filter_with_twelve_months_data(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ShipmentVolumeChart::class);

        $this->assertSame('monthly', $component->get('filter'));

        $data = $component->instance()->getCachedData();

        $this->assertCount(3, $data['datasets']);
        $this->assertSame('Master Bills (MBL)', $data['datasets'][0]['label']);
        $this->assertSame('House Bills (HBL)', $data['datasets'][1]['label']);
        $this->assertSame('Total Volume (MBL + HBL)', $data['datasets'][2]['label']);
        $this->assertCount(12, $data['labels']);
        $this->assertSame(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], $data['labels']);
    }

    public function test_chart_switches_to_quarterly_filter(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ShipmentVolumeChart::class)
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

        $component = Livewire::test(ShipmentVolumeChart::class)
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

    public function test_chart_accurately_counts_monthly_and_quarterly_volume(): void
    {
        $this->actingAs($this->user);

        $currentYear = now()->year;
        $carrier = Carrier::factory()->create();
        $customer = Customer::create([
            'name' => 'Global Logistics Client',
            'email' => 'client@globallogistics.com',
            'phone' => '1122334455',
        ]);
        $consignor = Consignor::create([
            'name' => 'Tokyo Auto Export',
            'email' => 'export@tokyoauto.jp',
            'phone' => '8899001122',
        ]);

        // Create 1 Master Bill in April (month 4)
        $masterBill = MasterBill::create([
            'date' => Carbon::create($currentYear, 4, 15, 10, 0, 0),
            'console' => 'CNS-APRIL-001',
            'ocean_bill_of_lading' => 'OBL-APRIL-001',
            'carrier_id' => $carrier->id,
            'etd' => Carbon::create($currentYear, 4, 16)->toDateString(),
            'eta' => Carbon::create($currentYear, 4, 30)->toDateString(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // Create 2 House Bills in April (month 4)
        HouseBill::create([
            'master_bill_id' => $masterBill->id,
            'house_bill' => 'HBL-APRIL-001',
            'consignor_id' => $consignor->id,
            'customer_id' => $customer->id,
            'notify_party_id' => $customer->id,
            'created_at' => Carbon::create($currentYear, 4, 18, 12, 0, 0),
            'updated_at' => Carbon::create($currentYear, 4, 18, 12, 0, 0),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        HouseBill::create([
            'master_bill_id' => $masterBill->id,
            'house_bill' => 'HBL-APRIL-002',
            'consignor_id' => $consignor->id,
            'customer_id' => $customer->id,
            'notify_party_id' => $customer->id,
            'created_at' => Carbon::create($currentYear, 4, 20, 14, 0, 0),
            'updated_at' => Carbon::create($currentYear, 4, 20, 14, 0, 0),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $component = Livewire::test(ShipmentVolumeChart::class);
        $data = $component->instance()->getCachedData();

        // Index 3 corresponds to April (month 4, 0-indexed)
        $this->assertSame(1, $data['datasets'][0]['data'][3]); // MBL
        $this->assertSame(2, $data['datasets'][1]['data'][3]); // HBL
        $this->assertSame(3, $data['datasets'][2]['data'][3]); // Total

        // Test Quarterly view (April is Q2, index 1)
        $quarterlyComponent = Livewire::test(ShipmentVolumeChart::class)
            ->set('filter', 'quarterly');
        $quarterlyData = $quarterlyComponent->instance()->getCachedData();

        $this->assertSame(1, $quarterlyData['datasets'][0]['data'][1]); // MBL in Q2
        $this->assertSame(2, $quarterlyData['datasets'][1]['data'][1]); // HBL in Q2
        $this->assertSame(3, $quarterlyData['datasets'][2]['data'][1]); // Total in Q2
    }

    public function test_chart_excludes_soft_deleted_bills(): void
    {
        $this->actingAs($this->user);

        $currentYear = now()->year;
        $carrier = Carrier::factory()->create();
        $customer = Customer::create([
            'name' => 'Deleted Client',
            'email' => 'deleted@client.com',
            'phone' => '9988776655',
        ]);
        $consignor = Consignor::create([
            'name' => 'Deleted Consignor',
            'email' => 'deleted@consignor.com',
            'phone' => '5566778899',
        ]);

        $masterBill = MasterBill::create([
            'date' => Carbon::create($currentYear, 6, 1, 10, 0, 0),
            'console' => 'CNS-DEL-001',
            'ocean_bill_of_lading' => 'OBL-DEL-001',
            'carrier_id' => $carrier->id,
            'etd' => Carbon::create($currentYear, 6, 2)->toDateString(),
            'eta' => Carbon::create($currentYear, 6, 15)->toDateString(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $houseBill = HouseBill::create([
            'master_bill_id' => $masterBill->id,
            'house_bill' => 'HBL-DEL-001',
            'consignor_id' => $consignor->id,
            'customer_id' => $customer->id,
            'notify_party_id' => $customer->id,
            'created_at' => Carbon::create($currentYear, 6, 2, 12, 0, 0),
            'updated_at' => Carbon::create($currentYear, 6, 2, 12, 0, 0),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // Soft delete both bills
        $masterBill->delete();
        $houseBill->delete();

        $component = Livewire::test(ShipmentVolumeChart::class);
        $data = $component->instance()->getCachedData();

        // Index 5 corresponds to June (month 6, 0-indexed)
        $this->assertSame(0, $data['datasets'][0]['data'][5]); // MBL = 0
        $this->assertSame(0, $data['datasets'][1]['data'][5]); // HBL = 0
        $this->assertSame(0, $data['datasets'][2]['data'][5]); // Total = 0
    }

    public function test_description_includes_aggregate_metrics(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ShipmentVolumeChart::class);
        $description = $component->instance()->getDescription();

        $this->assertStringContainsString('This Year (Monthly)', $description);
        $this->assertStringContainsString('Master Bills (MBL):', $description);
        $this->assertStringContainsString('House Bills (HBL):', $description);
        $this->assertStringContainsString('Total Volume:', $description);
    }

    public function test_user_with_only_master_bill_permission_sees_only_mbl_dataset(): void
    {
        $this->gateCallback = fn ($user, $ability, $args) => ($args[0] ?? null) === MasterBill::class;

        $this->actingAs($this->user);

        $this->assertTrue(ShipmentVolumeChart::canView());

        $component = Livewire::test(ShipmentVolumeChart::class);
        $data = $component->instance()->getCachedData();

        $this->assertCount(1, $data['datasets']);
        $this->assertSame('Master Bills (MBL)', $data['datasets'][0]['label']);

        $description = $component->instance()->getDescription();
        $this->assertStringContainsString('Master Bills (MBL):', $description);
        $this->assertStringNotContainsString('House Bills (HBL):', $description);
        $this->assertStringNotContainsString('Total Volume:', $description);
    }

    public function test_user_with_only_house_bill_permission_sees_only_hbl_dataset(): void
    {
        $this->gateCallback = fn ($user, $ability, $args) => ($args[0] ?? null) === HouseBill::class;

        $this->actingAs($this->user);

        $this->assertTrue(ShipmentVolumeChart::canView());

        $component = Livewire::test(ShipmentVolumeChart::class);
        $data = $component->instance()->getCachedData();

        $this->assertCount(1, $data['datasets']);
        $this->assertSame('House Bills (HBL)', $data['datasets'][0]['label']);

        $description = $component->instance()->getDescription();
        $this->assertStringContainsString('House Bills (HBL):', $description);
        $this->assertStringNotContainsString('Master Bills (MBL):', $description);
        $this->assertStringNotContainsString('Total Volume:', $description);
    }

    public function test_user_with_no_shipment_permissions_cannot_view_shipment_volume_chart(): void
    {
        $this->gateCallback = fn () => false;

        $this->actingAs($this->user);

        $this->assertFalse(ShipmentVolumeChart::canView());
    }
}
