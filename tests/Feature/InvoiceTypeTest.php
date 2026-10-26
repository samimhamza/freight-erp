<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\HouseBill;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceTypeTest extends TestCase
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

    public function test_invoice_type_enum_has_expected_values_and_labels(): void
    {
        $this->assertSame('shipping', InvoiceType::SHIPPING->value);
        $this->assertSame('clearance', InvoiceType::CLEARANCE->value);
        $this->assertSame('Shipping', InvoiceType::SHIPPING->getLabel());
        $this->assertSame('Clearance', InvoiceType::CLEARANCE->getLabel());
    }

    public function test_shipping_invoice_persists_customer_and_house_bill_and_resolves_all_containers(): void
    {
        $customer = Customer::first();
        $houseBill = HouseBill::first();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-SHP-01',
            'type' => InvoiceType::SHIPPING,
            'customer_id' => $customer->id,
            'house_bill_id' => $houseBill->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'type' => 'shipping',
            'customer_id' => $customer->id,
            'house_bill_id' => $houseBill->id,
            'house_bill' => null,
        ]);

        $this->assertSame(InvoiceType::SHIPPING, $invoice->type);
        $this->assertTrue($invoice->customer->is($customer));
        $this->assertTrue($invoice->houseBill->is($houseBill));
        $this->assertSame($houseBill->house_bill, $invoice->house_bill_number);
        $this->assertNotEmpty($invoice->all_container_numbers);
    }

    public function test_clearance_invoice_with_database_house_bill_resolves_all_containers(): void
    {
        $customer = Customer::first();
        $houseBill = HouseBill::first();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-CLR-01',
            'type' => InvoiceType::CLEARANCE,
            'customer_id' => $customer->id,
            'house_bill_id' => $houseBill->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'type' => 'clearance',
            'customer_id' => $customer->id,
            'house_bill_id' => $houseBill->id,
            'house_bill' => null,
        ]);

        $invoice->refresh();
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame($houseBill->id, $invoice->house_bill_id);
        $this->assertSame($houseBill->house_bill, $invoice->house_bill_number);
        $this->assertNotEmpty($invoice->all_container_numbers);
        $this->assertTrue($invoice->canBeSentToCustomer());
    }

    public function test_clearance_invoice_with_manual_house_bill_and_custom_containers(): void
    {
        $customer = Customer::first();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-CLR-MANUAL',
            'type' => InvoiceType::CLEARANCE,
            'customer_id' => $customer->id,
            'house_bill_id' => null,
            'house_bill' => 'CUSTOM-HBL-7788',
            'container_numbers' => ['CONT-A1', 'CONT-B2'],
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'type' => 'clearance',
            'customer_id' => $customer->id,
            'house_bill_id' => null,
            'house_bill' => 'CUSTOM-HBL-7788',
        ]);

        $invoice->refresh();
        $this->assertNull($invoice->house_bill_id);
        $this->assertSame('CUSTOM-HBL-7788', $invoice->house_bill_number);
        $this->assertSame(['CONT-A1', 'CONT-B2'], $invoice->all_container_numbers);
    }

    public function test_changing_type_to_clearance_retains_house_bill_and_customer(): void
    {
        $customer = Customer::first();
        $houseBill = HouseBill::first();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-SWITCH-01',
            'type' => InvoiceType::SHIPPING,
            'customer_id' => $customer->id,
            'house_bill_id' => $houseBill->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->assertNotNull($invoice->customer_id);
        $this->assertNotNull($invoice->house_bill_id);

        $invoice->update([
            'type' => InvoiceType::CLEARANCE,
        ]);

        $invoice->refresh();
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame($houseBill->id, $invoice->house_bill_id);
    }

    public function test_seeded_clearance_invoices_exist_for_both_database_and_manual_house_bills(): void
    {
        $dbClearanceInvoice = Invoice::where('type', InvoiceType::CLEARANCE)->whereNotNull('house_bill_id')->first();
        $this->assertNotNull($dbClearanceInvoice);
        $this->assertNotNull($dbClearanceInvoice->customer_id);
        $this->assertNotNull($dbClearanceInvoice->house_bill_id);
        $this->assertNotEmpty($dbClearanceInvoice->all_container_numbers);

        $manualClearanceInvoice = Invoice::where('type', InvoiceType::CLEARANCE)->whereNull('house_bill_id')->first();
        $this->assertNotNull($manualClearanceInvoice);
        $this->assertNotNull($manualClearanceInvoice->customer_id);
        $this->assertSame('EXT-HBL-9988', $manualClearanceInvoice->house_bill);
        $this->assertSame(['MSCU9876543', 'TCLU1234567'], $manualClearanceInvoice->all_container_numbers);
    }

    public function test_invoices_table_can_render_and_filter_by_type(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ListInvoices::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Invoice::all())
            ->filterTable('type', InvoiceType::CLEARANCE->value)
            ->assertCanSeeTableRecords(Invoice::where('type', InvoiceType::CLEARANCE)->get())
            ->assertCanNotSeeTableRecords(Invoice::where('type', InvoiceType::SHIPPING)->get());
    }

    public function test_view_invoice_page_renders_clearance_invoice_without_errors(): void
    {
        $this->actingAs($this->user);

        $clearanceInvoice = Invoice::where('type', InvoiceType::CLEARANCE)->whereNotNull('house_bill_id')->first();

        Livewire::test(ViewInvoice::class, ['record' => $clearanceInvoice->getKey()])
            ->assertSuccessful()
            ->assertSee('Clearance')
            ->assertSee($clearanceInvoice->customer->name)
            ->assertSee($clearanceInvoice->invoice_number)
            ->assertSee($clearanceInvoice->houseBill->house_bill);

        $manualClearanceInvoice = Invoice::where('type', InvoiceType::CLEARANCE)->whereNull('house_bill_id')->first();

        Livewire::test(ViewInvoice::class, ['record' => $manualClearanceInvoice->getKey()])
            ->assertSuccessful()
            ->assertSee('Clearance')
            ->assertSee($manualClearanceInvoice->customer->name)
            ->assertSee($manualClearanceInvoice->invoice_number)
            ->assertSee('EXT-HBL-9988');
    }

    public function test_create_invoice_auto_syncs_customer_when_house_bill_is_selected(): void
    {
        $this->actingAs($this->user);

        $houseBill = HouseBill::first();
        $this->assertNotNull($houseBill);

        Livewire::test(CreateInvoice::class)
            ->set('data.house_bill_id', $houseBill->id)
            ->assertSet('data.customer_id', $houseBill->customer_id)
            ->set('data.type', InvoiceType::CLEARANCE->value)
            ->assertSet('data.house_bill_id', $houseBill->id);
    }

    public function test_house_bills_are_filtered_by_selected_customer_across_invoice_types(): void
    {
        $this->actingAs($this->user);

        $customer1 = Customer::first();
        $customer2 = Customer::skip(1)->first() ?? Customer::factory()->create();

        $hb1 = HouseBill::where('customer_id', $customer1->id)->first();
        $hb2 = HouseBill::where('customer_id', $customer2->id)->first() ?? HouseBill::create([
            'master_bill_id' => $hb1->master_bill_id,
            'house_bill' => 'HBL-CUSTOMER-2-TEST',
            'consignor_id' => $hb1->consignor_id,
            'customer_id' => $customer2->id,
            'notify_party_id' => $customer2->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $test = Livewire::test(CreateInvoice::class)
            ->set('data.customer_id', $customer1->id);

        $schema = $test->instance()->form;
        $component = $schema->getComponent('house_bill_id');
        $options = $component->getOptions();

        $this->assertArrayHasKey($hb1->id, $options);
        $this->assertArrayNotHasKey($hb2->id, $options);

        // Switch to Clearance
        $test->set('data.type', InvoiceType::CLEARANCE->value);
        $optionsAfterSwitch = $test->instance()->form->getComponent('house_bill_id')->getOptions();

        $this->assertArrayHasKey($hb1->id, $optionsAfterSwitch);
        $this->assertArrayNotHasKey($hb2->id, $optionsAfterSwitch);

        // Select hb1, then change customer to customer 2
        $test->set('data.house_bill_id', $hb1->id)
            ->set('data.customer_id', $customer2->id)
            ->assertSet('data.house_bill_id', null);

        $optionsCustomer2 = $test->instance()->form->getComponent('house_bill_id')->getOptions();
        $this->assertArrayHasKey($hb2->id, $optionsCustomer2);
        $this->assertArrayNotHasKey($hb1->id, $optionsCustomer2);
    }

    public function test_create_clearance_invoice_with_manual_source_and_containers_via_form(): void
    {
        $this->actingAs($this->user);

        $customer = Customer::first();
        $charge = Charge::first();

        Livewire::test(CreateInvoice::class)
            ->set('data.invoice_number', 'INV-MANUAL-FORM-01')
            ->set('data.type', InvoiceType::CLEARANCE->value)
            ->set('data.house_bill_source', 'manual')
            ->set('data.house_bill', 'MANUAL-HBL-999')
            ->set('data.container_numbers', ['CONT-M1', 'CONT-M2'])
            ->set('data.customer_id', $customer->id)
            ->set('data.invoice_date', now()->toDateString())
            ->set('data.due_date', now()->addDays(15)->toDateString())
            ->set('data.Invoice Items', [
                'item_1' => [
                    'charge_id' => $charge->id,
                    'quantity' => 1,
                    'amount' => 500,
                    'vat' => 0,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = Invoice::where('invoice_number', 'INV-MANUAL-FORM-01')->first();
        $this->assertNotNull($invoice);
        $this->assertNull($invoice->house_bill_id);
        $this->assertSame('MANUAL-HBL-999', $invoice->house_bill);
        $this->assertSame(['CONT-M1', 'CONT-M2'], $invoice->container_numbers);
        $this->assertSame(['CONT-M1', 'CONT-M2'], $invoice->all_container_numbers);
    }

    public function test_edit_manual_clearance_invoice_hydrates_manual_source(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::where('type', InvoiceType::CLEARANCE)->whereNull('house_bill_id')->first();
        $this->assertNotNull($invoice);

        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->assertSuccessful()
            ->assertSet('data.house_bill_source', 'manual')
            ->assertSet('data.house_bill', $invoice->house_bill)
            ->assertSet('data.container_numbers', $invoice->container_numbers);
    }
}
