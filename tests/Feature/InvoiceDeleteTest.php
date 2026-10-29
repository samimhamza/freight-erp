<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class InvoiceDeleteTest extends TestCase
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

        $invoiceWithPayments = Invoice::has('payments')->first();
        $this->assertNotNull($invoiceWithPayments);

        $deleteAction1 = InvoiceResource::configureDeleteAction(DeleteAction::make());
        $this->assertTrue($deleteAction1->record($invoiceWithPayments)->isConfirmationRequired());

        $invoiceWithoutPayments = Invoice::create([
            'invoice_number' => 'INV-TEST-NOPAY-01',
            'type' => InvoiceType::SHIPPING,
            'customer_id' => $invoiceWithPayments->customer_id,
            'status' => InvoiceStatus::DRAFT,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $deleteAction2 = InvoiceResource::configureDeleteAction(DeleteAction::make());
        $this->assertFalse($deleteAction2->record($invoiceWithoutPayments)->isConfirmationRequired());
    }

    public function test_soft_deleting_invoice_sets_deleted_by(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::first();
        $invoice->delete();

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertEquals($this->user->id, $invoice->fresh()->deleted_by);
    }

    public function test_force_deleting_invoice_cascades_force_deletion_to_items_and_payments(): void
    {
        $invoice = Invoice::has('payments')->has('items')->first();
        $this->assertNotNull($invoice);

        $itemIds = $invoice->items()->pluck('id')->all();
        $paymentIds = $invoice->payments()->pluck('id')->all();

        $this->assertNotEmpty($itemIds);
        $this->assertNotEmpty($paymentIds);

        $invoice->forceDelete();

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);

        foreach ($itemIds as $itemId) {
            $this->assertDatabaseMissing('invoice_items', ['id' => $itemId]);
        }

        foreach ($paymentIds as $paymentId) {
            $this->assertDatabaseMissing('payments', ['id' => $paymentId]);
        }
    }
}
