<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\RemittanceAdvices\Pages\CreateRemittanceAdvice;
use App\Filament\Resources\RemittanceAdvices\RemittanceAdviceResource;
use App\Mail\RemittanceAdviceMail;
use App\Models\Carrier;
use App\Models\Charge;
use App\Models\MasterBill;
use App\Models\RemittanceAdvice;
use App\Models\RemittanceAdviceItem;
use App\Models\RemittancePayment;
use App\Models\User;
use App\Services\SendRemittanceAdvice;
use App\Support\Pdf\PdfFilename;
use App\Support\Pdf\RemittanceAdvicePdfData;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RemittanceAdviceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);
    }

    public function test_can_create_remittance_advice_and_calculate_totals(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Maersk Line']);
        $masterBill = MasterBill::factory()->create([
            'carrier_id' => $carrier->id,
            'console' => 'CNS-1001',
            'ocean_bill_of_lading' => 'MAEU123456789',
        ]);

        $advice = RemittanceAdvice::factory()->create([
            'carrier_id' => $carrier->id,
            'master_bill_id' => $masterBill->id,
            'advice_number' => 'RADV-001',
            'status' => InvoiceStatus::DRAFT,
        ]);

        $charge1 = Charge::factory()->create(['name' => 'Freight Charge']);
        $charge2 = Charge::factory()->create(['name' => 'Port Handling']);

        RemittanceAdviceItem::factory()->create([
            'remittance_advice_id' => $advice->id,
            'charge_id' => $charge1->id,
            'quantity' => 2,
            'amount' => 500.00,
            'vat' => 50.00,
        ]);

        RemittanceAdviceItem::factory()->create([
            'remittance_advice_id' => $advice->id,
            'charge_id' => $charge2->id,
            'quantity' => 1,
            'amount' => 200.00,
            'vat' => 10.00,
        ]);

        // Total = (2 * 500 + 50) + (1 * 200 + 10) = 1050 + 210 = 1260
        $this->assertEquals(1260.00, (float) $advice->total);
        $this->assertEquals(0.00, (float) $advice->total_paid);
        $this->assertEquals(1260.00, (float) $advice->total_remaining);

        // Record a partial payment
        $payment = RemittancePayment::factory()->create([
            'remittance_advice_id' => $advice->id,
            'payment_number' => '1',
            'payment_reference' => 'PAY-001',
            'amount' => 600.00,
        ]);

        RemittanceAdvice::updateStatusOnSaveChild($payment);

        $advice->refresh();
        $this->assertEquals(600.00, (float) $advice->total_paid);
        $this->assertEquals(660.00, (float) $advice->total_remaining);
        $this->assertEquals(InvoiceStatus::PARTIALLY_PAID, $advice->status);

        // Record the remaining payment
        $payment2 = RemittancePayment::factory()->create([
            'remittance_advice_id' => $advice->id,
            'payment_number' => '2',
            'payment_reference' => 'PAY-002',
            'amount' => 660.00,
        ]);

        RemittanceAdvice::updateStatusOnSaveChild($payment2);

        $advice->refresh();
        $this->assertEquals(1260.00, (float) $advice->total_paid);
        $this->assertEquals(0.00, (float) $advice->total_remaining);
        $this->assertEquals(InvoiceStatus::PAID, $advice->status);

        // Verify relationships
        $this->assertTrue($masterBill->remittanceAdvices->contains($advice));
        $this->assertTrue($carrier->remittanceAdvices->contains($advice));
        $this->assertTrue($advice->carrier->is($carrier));
    }

    public function test_send_remittance_advice_sends_email_to_configured_recipient(): void
    {
        Mail::fake();

        config(['company.remittance_advice_email' => 'accounts@acmelogistics.example.com']);

        $carrier = Carrier::factory()->create(['name' => 'CMA CGM']);
        $masterBill = MasterBill::factory()->create([
            'carrier_id' => $carrier->id,
            'console' => 'CNS-2002',
            'ocean_bill_of_lading' => 'CMAU987654321',
        ]);

        $advice = RemittanceAdvice::factory()->create([
            'carrier_id' => $carrier->id,
            'master_bill_id' => $masterBill->id,
            'advice_number' => 'RADV-002',
            'status' => InvoiceStatus::DRAFT,
            'sent_at' => null,
        ]);

        RemittanceAdviceItem::factory()->create([
            'remittance_advice_id' => $advice->id,
            'quantity' => 1,
            'amount' => 1000.00,
            'vat' => 50.00,
        ]);

        $service = app(SendRemittanceAdvice::class);
        $wasDraft = $service->send($advice);

        $this->assertTrue($wasDraft);
        $this->assertEquals(InvoiceStatus::SENT, $advice->fresh()->status);
        $this->assertNotNull($advice->fresh()->sent_at);

        Mail::assertSent(RemittanceAdviceMail::class, function (RemittanceAdviceMail $mail) use ($advice) {
            return $mail->hasTo('accounts@acmelogistics.example.com')
                && str_contains($mail->envelope()->subject, $advice->advice_number)
                && str_contains($mail->envelope()->subject, 'CMA CGM');
        });
    }

    public function test_send_remittance_advice_fails_when_destination_email_is_blank(): void
    {
        config(['company.remittance_advice_email' => '']);
        config(['company.remittance_invoice_email' => '']);
        config(['company.payable_invoice_email' => '']);

        $carrier = Carrier::factory()->create();
        $masterBill = MasterBill::factory()->create(['carrier_id' => $carrier->id]);
        $advice = RemittanceAdvice::factory()->create([
            'carrier_id' => $carrier->id,
            'master_bill_id' => $masterBill->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $service = app(SendRemittanceAdvice::class);

        $this->expectException(ValidationException::class);
        $service->send($advice);
    }

    public function test_remittance_advice_pdf_data_and_filename_structure(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Hapag-Lloyd']);
        $masterBill = MasterBill::factory()->create([
            'carrier_id' => $carrier->id,
            'console' => 'CNS-3003',
            'ocean_bill_of_lading' => 'HLCU11223344',
        ]);

        $advice = RemittanceAdvice::factory()->create([
            'carrier_id' => $carrier->id,
            'master_bill_id' => $masterBill->id,
            'advice_number' => 'RADV-003',
        ]);

        $charge = Charge::factory()->create(['name' => 'Documentation Fee']);
        RemittanceAdviceItem::factory()->create([
            'remittance_advice_id' => $advice->id,
            'charge_id' => $charge->id,
            'quantity' => 2,
            'amount' => 150.00,
            'vat' => 15.00,
        ]);

        $pdfData = RemittanceAdvicePdfData::for($advice);

        $this->assertEquals('Remittance Advice RADV-003', $pdfData['title']);
        $this->assertEquals(300.00, $pdfData['subtotal']);
        $this->assertEquals(15.00, $pdfData['vat_total']);
        $this->assertEquals(315.00, $pdfData['grand_total']);
        $this->assertCount(1, $pdfData['lines']);
        $this->assertEquals('Documentation Fee', $pdfData['lines'][0]['description']);

        $filename = PdfFilename::forRemittanceAdvice($advice);
        $this->assertEquals('RADV-003_Hapag-Lloyd', $filename);
    }

    public function test_authenticated_user_can_access_remittance_advices_print_route(): void
    {
        $user = User::factory()->create();

        $carrier = Carrier::factory()->create();
        $masterBill = MasterBill::factory()->create(['carrier_id' => $carrier->id]);
        $advice = RemittanceAdvice::factory()->create([
            'carrier_id' => $carrier->id,
            'master_bill_id' => $masterBill->id,
        ]);

        RemittanceAdviceItem::factory()->create([
            'remittance_advice_id' => $advice->id,
            'quantity' => 1,
            'amount' => 100.00,
            'vat' => 0.00,
        ]);

        $response = $this->actingAs($user)->get(route('remittance-advices.print', $advice));

        $response->assertOk();

        // Check legacy route aliases still work
        $responseLegacy = $this->actingAs($user)->get(route('remittance-invoices.print', $advice));
        $responseLegacy->assertOk();

        $responsePayable = $this->actingAs($user)->get(route('payable-invoices.print', $advice));
        $responsePayable->assertOk();
    }

    public function test_authenticated_user_can_view_remittance_advices_pages(): void
    {
        $user = User::factory()->create();

        $carrier = Carrier::factory()->create(['name' => 'ONE Line']);
        $masterBill = MasterBill::factory()->create(['carrier_id' => $carrier->id]);
        $advice = RemittanceAdvice::factory()->create([
            'carrier_id' => $carrier->id,
            'master_bill_id' => $masterBill->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->actingAs($user)
            ->get(RemittanceAdviceResource::getUrl('index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(RemittanceAdviceResource::getUrl('view', ['record' => $advice]))
            ->assertOk();
    }

    public function test_selecting_master_bill_automatically_sets_and_filters_carrier(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $carrier = Carrier::factory()->create(['name' => 'Maersk Line']);
        $masterBill = MasterBill::factory()->create([
            'carrier_id' => $carrier->id,
            'ocean_bill_of_lading' => 'MAEU-AUTO-01',
        ]);

        Livewire::test(CreateRemittanceAdvice::class)
            ->set('data.master_bill_id', $masterBill->id)
            ->assertSet('data.carrier_id', $carrier->id);
    }

    public function test_changing_carrier_resets_mismatched_master_bill(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $carrier1 = Carrier::factory()->create(['name' => 'Carrier One']);
        $carrier2 = Carrier::factory()->create(['name' => 'Carrier Two']);

        $masterBill1 = MasterBill::factory()->create(['carrier_id' => $carrier1->id]);

        Livewire::test(CreateRemittanceAdvice::class)
            ->set('data.carrier_id', $carrier1->id)
            ->set('data.master_bill_id', $masterBill1->id)
            ->assertSet('data.master_bill_id', $masterBill1->id)
            ->set('data.carrier_id', $carrier2->id)
            ->assertSet('data.master_bill_id', null);
    }

    public function test_mismatched_carrier_and_master_bill_validation_fails(): void
    {
        $carrier1 = Carrier::factory()->create(['name' => 'Carrier A']);
        $carrier2 = Carrier::factory()->create(['name' => 'Carrier B']);

        $masterBill2 = MasterBill::factory()->create(['carrier_id' => $carrier2->id]);

        $validator = Validator::make([
            'carrier_id' => $carrier1->id,
            'master_bill_id' => $masterBill2->id,
        ], [
            'master_bill_id' => [
                function (string $attribute, $value, \Closure $fail) use ($carrier1) {
                    $masterBill = MasterBill::find($value);
                    if ($masterBill && (int) $masterBill->carrier_id !== (int) $carrier1->id) {
                        $fail('The selected master bill does not belong to the selected carrier.');
                    }
                },
            ],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The selected master bill does not belong to the selected carrier.',
            $validator->errors()->first('master_bill_id')
        );
    }

    public function test_saving_remittance_advice_auto_syncs_carrier_id_if_omitted(): void
    {
        $carrier = Carrier::factory()->create(['name' => 'Evergreen']);
        $masterBill = MasterBill::factory()->create(['carrier_id' => $carrier->id]);

        $advice = RemittanceAdvice::create([
            'advice_number' => 'RADV-AUTO-01',
            'master_bill_id' => $masterBill->id,
            'advice_date' => now()->toDateString(),
            'status' => InvoiceStatus::DRAFT,
        ]);

        $advice->refresh();
        $this->assertSame($carrier->id, $advice->carrier_id);
    }

    public function test_carrier_can_persist_account_number(): void
    {
        $carrier = Carrier::factory()->create([
            'name' => 'Hapag-Lloyd',
            'account_number' => 'ACC-BANK-9988',
        ]);

        $this->assertDatabaseHas('carriers', [
            'id' => $carrier->id,
            'account_number' => 'ACC-BANK-9988',
        ]);
    }

    public function test_remittance_advice_pdf_view_renders_carrier_account_number_and_omits_console_agent_and_customs_entry(): void
    {
        $carrier = Carrier::factory()->create([
            'name' => 'Hapag-Lloyd',
            'account_number' => 'ACC-BANK-9988',
        ]);
        $masterBill = MasterBill::factory()->create([
            'carrier_id' => $carrier->id,
            'console' => 'CON-SECRET-01',
            'customs_entry_number' => 'CE-SECRET-01',
        ]);

        $advice = RemittanceAdvice::factory()->create([
            'carrier_id' => $carrier->id,
            'master_bill_id' => $masterBill->id,
            'advice_number' => 'RADV-REMIT-01',
        ]);

        $viewData = array_merge(RemittanceAdvicePdfData::for($advice), [
            'company' => config('company'),
        ]);

        $rendered = view('pdf.remittance_advices.show', $viewData)->render();

        $this->assertStringContainsString('REMITTANCE ADVICE', $rendered);
        $this->assertStringContainsString('ACC-BANK-9988', $rendered);

        $payeeDetailsSection = (string) str($rendered)->before('Shipment &amp; Terms')->after('Payee / Carrier Details');
        $this->assertStringContainsString('Account Number (Bank Code)', $payeeDetailsSection);
        $this->assertStringContainsString('ACC-BANK-9988', $payeeDetailsSection);
        $this->assertStringNotContainsString('CON-SECRET-01', $payeeDetailsSection);
        $this->assertStringNotContainsString('CE-SECRET-01', $payeeDetailsSection);
    }
}
