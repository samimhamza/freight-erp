<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\RemittanceAdvices\Pages\ViewRemittanceAdvice;
use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use App\Models\RemittanceAdvice;
use App\Models\RemittancePayment;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class RemittanceAdviceDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_delete_action_requires_confirmation_modal_only_when_payments_exist(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $adviceWithPayments = RemittanceAdvice::first();
        RemittancePayment::factory()->create(['remittance_advice_id' => $adviceWithPayments->id]);

        $deleteAction1 = RemittanceAdviceResource::configureDeleteAction(DeleteAction::make());
        $this->assertTrue($deleteAction1->record($adviceWithPayments)->isConfirmationRequired());

        $adviceWithoutPayments = RemittanceAdvice::create([
            'advice_number' => 'RADV-TEST-NOPAY-01',
            'carrier_id' => $adviceWithPayments->carrier_id,
            'master_bill_id' => $adviceWithPayments->master_bill_id,
            'status' => InvoiceStatus::DRAFT,
            'advice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $deleteAction2 = RemittanceAdviceResource::configureDeleteAction(DeleteAction::make());
        $this->assertFalse($deleteAction2->record($adviceWithoutPayments)->isConfirmationRequired());
    }

    public function test_soft_deleting_remittance_advice_sets_deleted_by(): void
    {
        $this->actingAs($this->user);

        $advice = RemittanceAdvice::first();
        $advice->delete();

        $this->assertSoftDeleted('remittance_advices', ['id' => $advice->id]);
        $this->assertEquals($this->user->id, $advice->fresh()->deleted_by);
    }

    public function test_force_deleting_remittance_advice_cascades_force_deletion_to_items_and_payments(): void
    {
        $advice = RemittanceAdvice::has('items')->first();
        $this->assertNotNull($advice);

        RemittancePayment::factory()->create(['remittance_advice_id' => $advice->id]);

        $itemIds = $advice->items()->pluck('id')->all();
        $paymentIds = $advice->payments()->pluck('id')->all();

        $this->assertNotEmpty($itemIds);
        $this->assertNotEmpty($paymentIds);

        $advice->forceDelete();

        $this->assertDatabaseMissing('remittance_advices', ['id' => $advice->id]);

        foreach ($itemIds as $itemId) {
            $this->assertDatabaseMissing('remittance_advice_items', ['id' => $itemId]);
        }

        foreach ($paymentIds as $paymentId) {
            $this->assertDatabaseMissing('remittance_payments', ['id' => $paymentId]);
        }
    }

    public function test_delete_action_is_visible_on_view_page_for_partially_paid_remittance_advice(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $advice = RemittanceAdvice::first();
        $advice->update(['status' => InvoiceStatus::PARTIALLY_PAID]);

        Livewire::test(ViewRemittanceAdvice::class, ['record' => $advice->getKey()])
            ->assertActionVisible('delete');
    }

    public function test_delete_action_hidden_on_view_page_when_user_lacks_permission(): void
    {
        Gate::before(function ($user, $ability) {
            if ($ability === 'delete' || str_starts_with($ability, 'Delete')) {
                return false;
            }

            return true;
        });

        $this->actingAs($this->user);

        $advice = RemittanceAdvice::first();
        $advice->update(['status' => InvoiceStatus::PARTIALLY_PAID]);

        Livewire::test(ViewRemittanceAdvice::class, ['record' => $advice->getKey()])
            ->assertActionHidden('delete');
    }
}
