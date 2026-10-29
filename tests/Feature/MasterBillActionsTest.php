<?php

namespace Tests\Feature;

use App\Filament\Resources\MasterBills\Pages\ListMasterBills;
use App\Models\Carrier;
use App\Models\MasterBill;
use App\Models\User;
use App\Models\Vessel;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class MasterBillActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_master_bill_force_delete_restrictions(): void
    {
        $masterBill = MasterBill::has('houseBills')->first();
        $this->assertNotNull($masterBill);

        $restrictions = $masterBill->getForceDeleteRestrictions();
        $this->assertNotEmpty($restrictions);

        $hasHouseBills = false;
        foreach ($restrictions as $restriction) {
            if (str_contains($restriction, 'House Bill')) {
                $hasHouseBills = true;
            }
        }
        $this->assertTrue($hasHouseBills);
    }

    public function test_master_bill_cannot_be_force_deleted_with_restrictions_via_action(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $masterBill = MasterBill::has('houseBills')->first();
        $masterBill->delete();

        Livewire::test(ListMasterBills::class)
            ->filterTable('trashed', true)
            ->callTableAction('forceDelete', $masterBill)
            ->assertNotified('Cannot Permanently Delete');

        $this->assertDatabaseHas('master_bills', ['id' => $masterBill->id]);
    }

    public function test_master_bill_can_be_force_deleted_when_no_restrictions_exist(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $vessel = Vessel::first();
        $carrier = Carrier::first();

        $standaloneMbl = MasterBill::create([
            'vessel_id' => $vessel->id,
            'carrier_id' => $carrier->id,
            'console' => 'CNS-SOLO-999',
            'ocean_bill_of_lading' => 'OBL-SOLO-999',
            'date' => now(),
            'etd' => now(),
            'eta' => now()->addDays(5),
            'status' => 'PENDING',
        ]);

        $this->assertEmpty($standaloneMbl->getForceDeleteRestrictions());

        $standaloneMbl->delete();

        Livewire::test(ListMasterBills::class)
            ->filterTable('trashed', true)
            ->callTableAction('forceDelete', $standaloneMbl);

        $this->assertDatabaseMissing('master_bills', ['id' => $standaloneMbl->id]);
    }

    public function test_computed_accessors_use_in_memory_when_relation_loaded(): void
    {
        $masterBill = MasterBill::with([
            'houseBills.containers.container',
            'houseBills.containers.vehicles',
        ])->has('houseBills.containers')->first();

        $this->assertNotNull($masterBill);
        $this->assertTrue($masterBill->relationLoaded('houseBills'));

        $this->assertGreaterThanOrEqual(0, $masterBill->total_containers);
        $this->assertGreaterThanOrEqual(0, $masterBill->total_vehicles);
        $this->assertGreaterThanOrEqual(0, $masterBill->total_weight);
        $this->assertGreaterThanOrEqual(0, $masterBill->total_volume);
        $this->assertGreaterThanOrEqual(0, $masterBill->total_packages);
    }

    public function test_computed_accessors_fallback_to_database_when_relation_not_loaded(): void
    {
        $masterBill = MasterBill::has('houseBills.containers')->first();
        $this->assertNotNull($masterBill);
        $this->assertFalse($masterBill->relationLoaded('houseBills'));

        $this->assertGreaterThanOrEqual(0, $masterBill->total_containers);
        $this->assertGreaterThanOrEqual(0, $masterBill->total_vehicles);
        $this->assertGreaterThanOrEqual(0, $masterBill->total_weight);
        $this->assertGreaterThanOrEqual(0, $masterBill->total_volume);
        $this->assertGreaterThanOrEqual(0, $masterBill->total_packages);
    }
}
