<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Invoice;
use App\Models\User;
use App\Policies\InvoicePolicy;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceCancelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_invoice_policy_authorizes_cancel_with_update_permission(): void
    {
        $policy = new InvoicePolicy;
        $invoice = Invoice::first();

        Permission::findOrCreate('Update:Invoice');

        $this->assertFalse($policy->cancel($this->user, $invoice));

        $this->user->givePermissionTo('Update:Invoice');
        $this->assertTrue($policy->cancel($this->user, $invoice));
    }

    public function test_draft_invoice_can_be_cancelled_via_table_action(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $invoice = Invoice::where('status', InvoiceStatus::DRAFT)->first();
        $this->assertNotNull($invoice);

        Livewire::test(ListInvoices::class)
            ->assertTableActionVisible('cancel', $invoice)
            ->callTableAction('cancel', $invoice)
            ->assertNotified('Cancelled successfully');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::CANCELLED, $invoice->status);
    }

    public function test_sent_invoice_can_be_cancelled(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $invoice = Invoice::where('status', InvoiceStatus::DRAFT)->first();
        $invoice->update(['status' => InvoiceStatus::SENT]);

        Livewire::test(ListInvoices::class)
            ->assertTableActionVisible('cancel', $invoice)
            ->callTableAction('cancel', $invoice)
            ->assertNotified('Cancelled successfully');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::CANCELLED, $invoice->status);
    }

    public function test_cancel_action_hidden_for_paid_or_cancelled_or_trashed_invoices(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->user);

        $invoice = Invoice::first();

        $invoice->update(['status' => InvoiceStatus::PAID]);
        Livewire::test(ListInvoices::class)
            ->assertTableActionHidden('cancel', $invoice);

        $invoice->update(['status' => InvoiceStatus::CANCELLED]);
        Livewire::test(ListInvoices::class)
            ->assertTableActionHidden('cancel', $invoice);

        $invoice->update(['status' => InvoiceStatus::DRAFT]);
        $invoice->delete();
        Livewire::test(ListInvoices::class)
            ->filterTable('trashed', true)
            ->assertTableActionHidden('cancel', $invoice);
    }
}
