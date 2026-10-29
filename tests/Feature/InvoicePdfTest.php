<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Pdf\InvoicePdfData;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create();
        $this->seed(DemoDataSeeder::class);
    }

    public function test_company_config_has_correct_details_and_bank_info(): void
    {
        $this->assertEquals('ACME SHIPPING & LOGISTICS L.L.C', config('company.name'));
        $this->assertStringContainsString('123 Logistics Boulevard, Suite 500', config('company.address'));
        $this->assertEquals('+971 4 123 4567', config('company.phone'));
        $this->assertEquals('info@acmelogistics.example.com', config('company.email'));
        $this->assertEquals('Dubai', config('company.city'));
        $this->assertEquals('UAE', config('company.country'));
        $this->assertEquals('Demo Commercial Bank', config('company.bank.name'));
        $this->assertEquals('ACME SHIPPING & LOGISTICS L.L.C', config('company.bank.account_title'));
        $this->assertEquals('12345678901234', config('company.bank.account_number'));
        $this->assertEquals('AE120000001234567890123', config('company.bank.iban'));
        $this->assertEquals('DEMOAEAD', config('company.bank.swift'));
    }

    public function test_invoice_view_renders_company_and_bank_details(): void
    {
        $invoice = Invoice::where('type', InvoiceType::SHIPPING)->first();
        $this->assertNotNull($invoice);

        $viewData = array_merge(InvoicePdfData::for($invoice), [
            'company' => config('company'),
        ]);

        $rendered = view('pdf.invoices.show', $viewData)->render();

        $this->assertStringContainsString(e('ACME SHIPPING & LOGISTICS L.L.C'), $rendered);
        $this->assertStringContainsString('123 Logistics Boulevard, Suite 500', $rendered);
        $this->assertStringContainsString('+971 4 123 4567', $rendered);
        $this->assertStringContainsString('info@acmelogistics.example.com', $rendered);
        $this->assertStringContainsString('Bank Details', $rendered);
        $this->assertStringContainsString('DEMOAEAD', $rendered);
        $this->assertStringContainsString('AE120000001234567890123', $rendered);
        $this->assertStringContainsString('12345678901234', $rendered);
        $this->assertStringContainsString(e('ACME SHIPPING & LOGISTICS L.L.C'), $rendered);
        $this->assertStringContainsString('Demo Commercial Bank', $rendered);
        $this->assertStringContainsString($invoice->houseBill->house_bill, $rendered);
        $this->assertNotEmpty($invoice->all_container_numbers);
        foreach ($invoice->all_container_numbers as $containerNumber) {
            $this->assertStringContainsString($containerNumber, $rendered);
        }
    }

    public function test_clearance_invoice_view_renders_company_customer_house_bill_and_container(): void
    {
        $invoice = Invoice::where('type', InvoiceType::CLEARANCE)->whereNotNull('house_bill_id')->first();
        $this->assertNotNull($invoice);

        $viewData = array_merge(InvoicePdfData::for($invoice), [
            'company' => config('company'),
        ]);

        $rendered = view('pdf.invoices.show', $viewData)->render();

        $this->assertStringContainsString(e('ACME SHIPPING & LOGISTICS L.L.C'), $rendered);
        $this->assertStringContainsString('info@acmelogistics.example.com', $rendered);
        $this->assertStringContainsString($invoice->customer->name, $rendered);
        $this->assertStringContainsString('House B/L', $rendered);
        $this->assertStringContainsString($invoice->houseBill->house_bill, $rendered);
        $this->assertStringContainsString('Container', $rendered);
        $this->assertNotEmpty($invoice->all_container_numbers);
        foreach ($invoice->all_container_numbers as $containerNumber) {
            $this->assertStringContainsString($containerNumber, $rendered);
        }
    }

    public function test_manual_clearance_invoice_view_renders_custom_house_bill_and_multiple_containers(): void
    {
        $manualInvoice = Invoice::where('type', InvoiceType::CLEARANCE)->whereNull('house_bill_id')->first();
        $this->assertNotNull($manualInvoice);

        $viewData = array_merge(InvoicePdfData::for($manualInvoice), [
            'company' => config('company'),
        ]);

        $rendered = view('pdf.invoices.show', $viewData)->render();

        $this->assertStringContainsString('House B/L', $rendered);
        $this->assertStringContainsString('EXT-HBL-9988', $rendered);
        $this->assertStringContainsString('Containers', $rendered);
        $this->assertStringContainsString('MSCU9876543', $rendered);
        $this->assertStringContainsString('TCLU1234567', $rendered);
    }
}
