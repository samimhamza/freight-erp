<?php

namespace Tests\Feature;

use App\Filament\Resources\Vessels\Pages\CreateVessel;
use App\Filament\Resources\Vessels\Pages\EditVessel;
use App\Filament\Resources\Vessels\Pages\ListVessels;
use App\Filament\Resources\Vessels\Pages\ViewVessel;
use App\Models\User;
use App\Models\Vessel;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class VesselTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->user = User::factory()->create();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_can_create_vessel_without_imo(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateVessel::class)
            ->fillForm([
                'name' => 'NEW TEST VESSEL',
                'voyage' => 'VOY-NEW-01',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('vessels', [
            'name' => 'NEW TEST VESSEL',
            'voyage' => 'VOY-NEW-01',
        ]);
    }

    public function test_vessel_form_components_do_not_contain_imo(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateVessel::class)
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('voyage')
            ->assertFormFieldDoesNotExist('imo');
    }

    public function test_vessel_table_columns_do_not_contain_imo(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ListVessels::class)
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('voyage')
            ->assertTableColumnDoesNotExist('imo');
    }

    public function test_can_view_vessel(): void
    {
        $this->actingAs($this->user);

        $vessel = Vessel::first();

        Livewire::test(ViewVessel::class, ['record' => $vessel->getKey()])
            ->assertSuccessful()
            ->assertSee($vessel->name)
            ->assertSee($vessel->voyage);
    }

    public function test_can_edit_vessel(): void
    {
        $this->actingAs($this->user);

        $vessel = Vessel::first();

        Livewire::test(EditVessel::class, ['record' => $vessel->getKey()])
            ->fillForm([
                'name' => 'UPDATED VESSEL NAME',
                'voyage' => 'VOY-UPDATED-01',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $vessel->refresh();
        $this->assertSame('UPDATED VESSEL NAME', $vessel->name);
        $this->assertSame('VOY-UPDATED-01', $vessel->voyage);
    }
}
