<?php

namespace Tests\Feature;

use App\Filament\Resources\HouseBills\Pages\ListHouseBills;
use App\Models\Customer;
use App\Models\HouseBill;
use App\Models\MasterBill;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HouseBillActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_can_create_house_bill_with_null_consignor_and_notify_party(): void
    {
        $customer = Customer::first();
        $masterBill = MasterBill::first();

        $houseBill = HouseBill::create([
            'house_bill' => 'HBL-NULL-TEST-01',
            'master_bill_id' => $masterBill->id,
            'customer_id' => $customer->id,
            'consignor_id' => null,
            'notify_party_id' => null,
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('house_bills', [
            'id' => $houseBill->id,
            'consignor_id' => null,
            'notify_party_id' => null,
        ]);
    }

    public function test_house_bill_force_delete_restrictions(): void
    {
        $houseBill = HouseBill::has('containers')->first();
        $this->assertNotNull($houseBill);

        $restrictions = $houseBill->getForceDeleteRestrictions();
        $this->assertNotEmpty($restrictions);

        $hasContainers = false;
        foreach ($restrictions as $restriction) {
            if (str_contains($restriction, 'Container')) {
                $hasContainers = true;
            }
        }
        $this->assertTrue($hasContainers);
    }

    public function test_house_bill_cannot_be_force_deleted_with_restrictions_via_action(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $houseBill = HouseBill::has('containers')->first();
        $houseBill->delete();

        Livewire::test(ListHouseBills::class)
            ->filterTable('trashed', true)
            ->callTableAction('forceDelete', $houseBill)
            ->assertNotified('Cannot Permanently Delete');

        $this->assertDatabaseHas('house_bills', ['id' => $houseBill->id]);
    }

    public function test_house_bill_can_be_force_deleted_when_no_restrictions_exist(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $customer = Customer::first();
        $masterBill = MasterBill::first();

        $standaloneHbl = HouseBill::create([
            'house_bill' => 'HBL-SOLO-999',
            'master_bill_id' => $masterBill->id,
            'customer_id' => $customer->id,
            'status' => 'PENDING',
        ]);

        $this->assertEmpty($standaloneHbl->getForceDeleteRestrictions());

        $standaloneHbl->delete();

        Livewire::test(ListHouseBills::class)
            ->filterTable('trashed', true)
            ->callTableAction('forceDelete', $standaloneHbl);

        $this->assertDatabaseMissing('house_bills', ['id' => $standaloneHbl->id]);
    }

    public function test_computed_accessors_use_in_memory_when_relation_loaded(): void
    {
        $houseBill = HouseBill::with(['containers.container', 'containers.vehicles'])
            ->has('containers')
            ->first();

        $this->assertNotNull($houseBill);
        $this->assertTrue($houseBill->relationLoaded('containers'));

        $this->assertGreaterThan(0, $houseBill->total_containers);
        $this->assertGreaterThanOrEqual(0, $houseBill->total_vehicles);
        $this->assertGreaterThanOrEqual(0, $houseBill->total_weight);
        $this->assertGreaterThanOrEqual(0, $houseBill->total_volume);
        $this->assertGreaterThanOrEqual(0, $houseBill->total_packages);
    }

    public function test_computed_accessors_fallback_to_database_when_relation_not_loaded(): void
    {
        $houseBill = HouseBill::has('containers')->first();
        $this->assertNotNull($houseBill);
        $this->assertFalse($houseBill->relationLoaded('containers'));

        $this->assertGreaterThan(0, $houseBill->total_containers);
        $this->assertGreaterThanOrEqual(0, $houseBill->total_vehicles);
        $this->assertGreaterThanOrEqual(0, $houseBill->total_weight);
        $this->assertGreaterThanOrEqual(0, $houseBill->total_volume);
        $this->assertGreaterThanOrEqual(0, $houseBill->total_packages);
    }
}
