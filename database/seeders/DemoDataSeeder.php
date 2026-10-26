<?php

namespace Database\Seeders;

use App\Enums\ContainerType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Carrier;
use App\Models\Charge;
use App\Models\Consignor;
use App\Models\Container;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\ExportAgent;
use App\Models\HouseBill;
use App\Models\HouseBillContainer;
use App\Models\ImportAgent;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\MasterBill;
use App\Models\Payment;
use App\Models\RemittanceAdvice;
use App\Models\RemittanceAdviceItem;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vessel;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed demo data for all application tables.
     */
    public function run(): void
    {
        $userId = User::query()->value('id');

        $uae = Country::create([
            'name' => 'United Arab Emirates',
            'code' => 'AE',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $usa = Country::create([
            'name' => 'United States',
            'code' => 'US',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $japan = Country::create([
            'name' => 'Japan',
            'code' => 'JP',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $jebelAli = Location::create([
            'name' => 'Jebel Ali',
            'code' => 'AEJEA',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $sharjah = Location::create([
            'name' => 'Sharjah',
            'code' => 'AESHJ',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $losAngeles = Location::create([
            'name' => 'Los Angeles',
            'code' => 'USLAX',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $yokohama = Location::create([
            'name' => 'Yokohama',
            'code' => 'JPYOK',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Currency::create([
            'name' => 'UAE Dirham',
            'code' => 'AED',
            'exchange_rate' => 1.000,
            'default' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Currency::create([
            'name' => 'US Dollar',
            'code' => 'USD',
            'exchange_rate' => 3.673,
            'default' => false,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $freightCharge = Charge::create([
            'name' => 'Ocean Freight',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $documentationCharge = Charge::create([
            'name' => 'Documentation Fee',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $handlingCharge = Charge::create([
            'name' => 'Handling Charge',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $customer = Customer::create([
            'name' => 'Acme Trading LLC',
            'phone' => '971501234567',
            'email' => 'info@acmetrading.example.com',
            'fax' => '97143334444',
            'address' => 'Industrial Area 3, Sharjah, UAE',
            'country_id' => $uae->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $notifyParty = Customer::create([
            'name' => 'Gulf Auto Parts',
            'phone' => '971509876543',
            'email' => 'orders@gulfautoparts.ae',
            'address' => 'Al Quoz, Dubai, UAE',
            'country_id' => $uae->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $consignor = Consignor::create([
            'name' => 'Pacific Export Co.',
            'phone' => '12135551234',
            'email' => 'export@pacificexport.com',
            'address' => '1200 Harbor Blvd, Los Angeles, CA',
            'country_id' => $usa->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $vessel = Vessel::create([
            'name' => 'MSC PACIFIC',
            'voyage' => 'VOY-2026-014',
            'imo' => 'IMO9876543',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $exportAgent = ExportAgent::create([
            'name' => 'Pacific Export Co.',
            'address' => '1200 Harbor Blvd, Los Angeles, CA',
            'email' => 'export@pacificexport.com',
            'phone' => '12135551234',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $importAgent = ImportAgent::create([
            'name' => 'Acme Shipping & Logistics LLC',
            'address' => '123 Logistics Boulevard, Suite 500, Dubai, UAE',
            'email' => 'info@acmelogistics.example.com',
            'phone' => '+971 4 123 4567',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $carrier = Carrier::create([
            'name' => 'Mediterranean Shipping Company',
            'code' => 'MSC',
            'account_number' => 'AE290330000000012345678',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $masterBill = MasterBill::create([
            'date' => now()->subDays(10),
            'console' => 'CON-2026-001',
            'ocean_bill_of_lading' => 'OBL-2026-001',
            'origin_id' => $losAngeles->id,
            'etd' => now()->subDays(8)->toDateString(),
            'port_of_discharge_id' => $jebelAli->id,
            'destination_id' => $sharjah->id,
            'eta' => now()->addDays(5)->toDateString(),
            'ata' => null,
            'mode' => 'SEA',
            'vessel_id' => $vessel->id,
            'carrier_id' => $carrier->id,
            'customs_entry_number' => 'CE-2026-001',
            'master_freight' => 'Prepaid',
            'export_agent_id' => $exportAgent->id,
            'import_agent_id' => $importAgent->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $houseBill = HouseBill::create([
            'master_bill_id' => $masterBill->id,
            'house_bill' => 'HBL-2026-001',
            'consignor_id' => $consignor->id,
            'customer_id' => $customer->id,
            'notify_party_id' => $notifyParty->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $container = Container::create([
            'container_number' => 'MSCU1234567',
            'type' => ContainerType::HC40,
            'tare_weight' => 3800,
            'volume' => 67.5,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $houseBillContainer = HouseBillContainer::create([
            'container_id' => $container->id,
            'house_bill_id' => $houseBill->id,
            'packages' => 120,
            'seal' => 'SEAL-2026-001',
            'net_weight' => 18500,
            'gross_weight' => 22300,
            'descriptions_of_goods' => 'Used auto parts and one complete vehicle',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Vehicle::create([
            'house_bill_container_id' => $houseBillContainer->id,
            'vin' => '1HGCM82633A004352',
            'year' => 2020,
            'make' => 'Toyota',
            'model' => 'Camry',
            'color' => 'Silver',
            'status' => VehicleStatus::USED,
            'type' => VehicleType::CAR,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $masterBillJapan = MasterBill::create([
            'date' => now()->subDays(5),
            'console' => 'CON-2026-002',
            'ocean_bill_of_lading' => 'OBL-2026-002',
            'origin_id' => $yokohama->id,
            'etd' => now()->subDays(3)->toDateString(),
            'port_of_discharge_id' => $jebelAli->id,
            'destination_id' => $sharjah->id,
            'eta' => now()->addDays(12)->toDateString(),
            'mode' => 'SEA',
            'vessel_id' => $vessel->id,
            'carrier_id' => $carrier->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $houseBillJapan = HouseBill::create([
            'master_bill_id' => $masterBillJapan->id,
            'house_bill' => 'HBL-2026-002',
            'consignor_id' => $consignor->id,
            'customer_id' => $customer->id,
            'notify_party_id' => $customer->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $containerJapan = Container::create([
            'container_number' => 'TCLU7654321',
            'type' => ContainerType::HC45,
            'tare_weight' => 4200,
            'volume' => 76.0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        HouseBillContainer::create([
            'container_id' => $containerJapan->id,
            'house_bill_id' => $houseBillJapan->id,
            'packages' => 85,
            'seal' => 'SEAL-2026-002',
            'net_weight' => 15200,
            'gross_weight' => 19400,
            'descriptions_of_goods' => 'Used auto parts',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-2026-001',
            'type' => InvoiceType::SHIPPING,
            'customer_id' => $customer->id,
            'house_bill_id' => $houseBill->id,
            'invoice_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDays(28)->toDateString(),
            'status' => InvoiceStatus::PARTIALLY_PAID,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'charge_id' => $freightCharge->id,
            'quantity' => 1,
            'amount' => 2500.00,
            'vat' => 125.00,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'charge_id' => $documentationCharge->id,
            'quantity' => 1,
            'amount' => 150.00,
            'vat' => 7.50,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'charge_id' => $handlingCharge->id,
            'quantity' => 2,
            'amount' => 75.00,
            'vat' => 7.50,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Payment::create([
            'payment_number' => 'PAY-2026-001',
            'payment_reference' => 'REF-2026-001',
            'invoice_id' => $invoice->id,
            'date' => now()->subDay()->toDateString(),
            'amount' => 1500.00,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-2026-002',
            'type' => InvoiceType::SHIPPING,
            'customer_id' => $customer->id,
            'house_bill_id' => $houseBillJapan->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $clearanceInvoice = Invoice::create([
            'invoice_number' => 'INV-2026-003',
            'type' => InvoiceType::CLEARANCE,
            'customer_id' => $customer->id,
            'house_bill_id' => $houseBill->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        InvoiceItem::create([
            'invoice_id' => $clearanceInvoice->id,
            'charge_id' => $handlingCharge->id,
            'quantity' => 1,
            'amount' => 500.00,
            'vat' => 25.00,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $manualClearanceInvoice = Invoice::create([
            'invoice_number' => 'INV-2026-004',
            'type' => InvoiceType::CLEARANCE,
            'customer_id' => $customer->id,
            'house_bill_id' => null,
            'house_bill' => 'EXT-HBL-9988',
            'container_numbers' => ['MSCU9876543', 'TCLU1234567'],
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        InvoiceItem::create([
            'invoice_id' => $manualClearanceInvoice->id,
            'charge_id' => $documentationCharge->id,
            'quantity' => 1,
            'amount' => 300.00,
            'vat' => 15.00,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $remittanceAdvice = RemittanceAdvice::create([
            'advice_number' => 'RADV-2026-001',
            'carrier_id' => $carrier->id,
            'master_bill_id' => $masterBill->id,
            'advice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        RemittanceAdviceItem::create([
            'remittance_advice_id' => $remittanceAdvice->id,
            'charge_id' => $freightCharge->id,
            'quantity' => 1,
            'amount' => 1800.00,
            'vat' => 90.00,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->command->info('Demo data seeded successfully.');
    }
}
