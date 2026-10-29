<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\RemittanceAdvices\Pages\ListRemittanceAdvices;
use App\Models\RemittanceAdvice;
use App\Models\User;
use App\Policies\RemittanceAdvicePolicy;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RemittanceAdviceCancelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_remittance_advice_policy_authorizes_cancel(): void
    {
        $policy = new RemittanceAdvicePolicy;
        $advice = RemittanceAdvice::first();

        Permission::findOrCreate('Update:RemittanceAdvice');

        $this->assertFalse($policy->cancel($this->user, $advice));

        $this->user->givePermissionTo('Update:RemittanceAdvice');
        $this->assertTrue($policy->cancel($this->user, $advice));
    }

    public function test_draft_remittance_advice_can_be_cancelled_via_table_action(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $advice = RemittanceAdvice::where('status', InvoiceStatus::DRAFT)->first();
        $this->assertNotNull($advice);

        Livewire::test(ListRemittanceAdvices::class)
            ->assertTableActionVisible('cancel', $advice)
            ->callTableAction('cancel', $advice)
            ->assertNotified('Cancelled successfully');

        $advice->refresh();
        $this->assertSame(InvoiceStatus::CANCELLED, $advice->status);
    }

    public function test_sent_remittance_advice_can_be_cancelled(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $advice = RemittanceAdvice::where('status', InvoiceStatus::DRAFT)->first();
        $advice->update(['status' => InvoiceStatus::SENT]);

        Livewire::test(ListRemittanceAdvices::class)
            ->assertTableActionVisible('cancel', $advice)
            ->callTableAction('cancel', $advice)
            ->assertNotified('Cancelled successfully');

        $advice->refresh();
        $this->assertSame(InvoiceStatus::CANCELLED, $advice->status);
    }

    public function test_cancel_action_hidden_for_paid_or_cancelled_or_trashed_remittance_advices(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $advice = RemittanceAdvice::first();

        $advice->update(['status' => InvoiceStatus::PAID]);
        Livewire::test(ListRemittanceAdvices::class)
            ->assertTableActionHidden('cancel', $advice);

        $advice->update(['status' => InvoiceStatus::CANCELLED]);
        Livewire::test(ListRemittanceAdvices::class)
            ->assertTableActionHidden('cancel', $advice);

        $advice->update(['status' => InvoiceStatus::DRAFT]);
        $advice->delete();
        Livewire::test(ListRemittanceAdvices::class)
            ->filterTable('trashed', true)
            ->assertTableActionHidden('cancel', $advice);
    }
}
