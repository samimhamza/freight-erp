<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\ExportAgent;
use App\Models\ImportAgent;
use App\Models\MasterBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_can_create_export_agent(): void
    {
        $exportAgent = ExportAgent::factory()->create([
            'name' => 'Global Freight Forwarding',
            'email' => 'contact@globalfreight.com',
            'phone' => '+1234567890',
            'fax' => '+1234567891',
            'address' => '100 Ocean Ave, Miami, FL',
        ]);

        $this->assertDatabaseHas('export_agents', [
            'id' => $exportAgent->id,
            'name' => 'Global Freight Forwarding',
            'email' => 'contact@globalfreight.com',
        ]);

        $this->assertEquals('Global Freight Forwarding', $exportAgent->company);
    }

    public function test_can_create_import_agent(): void
    {
        $importAgent = ImportAgent::factory()->create([
            'name' => 'Emirates Delivery Logistics',
            'email' => 'info@emirateslogistics.ae',
            'phone' => '+971501234567',
            'fax' => '+971501234568',
            'address' => 'Sharjah Industrial Area, UAE',
        ]);

        $this->assertDatabaseHas('import_agents', [
            'id' => $importAgent->id,
            'name' => 'Emirates Delivery Logistics',
            'email' => 'info@emirateslogistics.ae',
        ]);

        $this->assertEquals('Emirates Delivery Logistics', $importAgent->company);
    }

    public function test_master_bill_belongs_to_export_and_import_agents(): void
    {
        $exportAgent = ExportAgent::factory()->create([
            'name' => 'USA Forwarder',
            'email' => 'usa@forwarder.com',
            'phone' => '123456',
            'address' => 'USA',
        ]);

        $importAgent = ImportAgent::factory()->create([
            'name' => 'UAE Forwarder',
            'email' => 'uae@forwarder.com',
            'phone' => '654321',
            'address' => 'UAE',
        ]);

        $masterBill = MasterBill::create([
            'date' => now(),
            'console' => 'CON-TEST-001',
            'ocean_bill_of_lading' => 'OBL-TEST-001',
            'etd' => now()->toDateString(),
            'eta' => now()->addDays(5)->toDateString(),
            'export_agent_id' => $exportAgent->id,
            'import_agent_id' => $importAgent->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->assertTrue($masterBill->exportAgent->is($exportAgent));
        $this->assertTrue($masterBill->importAgent->is($importAgent));

        // Test backward compatibility accessors
        $this->assertEquals('USA Forwarder', $masterBill->export_agent_company);
        $this->assertEquals('usa@forwarder.com', $masterBill->export_agent_email);
        $this->assertEquals('UAE Forwarder', $masterBill->import_agent_company);
        $this->assertEquals('uae@forwarder.com', $masterBill->import_agent_email);
    }

    public function test_export_and_import_agents_have_master_bills_relationship(): void
    {
        $exportAgent = ExportAgent::factory()->create();
        $importAgent = ImportAgent::factory()->create();

        $masterBill = MasterBill::create([
            'date' => now(),
            'console' => 'CON-TEST-002',
            'ocean_bill_of_lading' => 'OBL-TEST-002',
            'etd' => now()->toDateString(),
            'eta' => now()->addDays(5)->toDateString(),
            'export_agent_id' => $exportAgent->id,
            'import_agent_id' => $importAgent->id,
        ]);

        $this->assertTrue($exportAgent->masterBills->contains($masterBill));
        $this->assertTrue($importAgent->masterBills->contains($masterBill));
    }

    public function test_agents_support_soft_deletes(): void
    {
        $exportAgent = ExportAgent::factory()->create();
        $importAgent = ImportAgent::factory()->create();

        $exportAgent->delete();
        $importAgent->delete();

        $this->assertSoftDeleted('export_agents', ['id' => $exportAgent->id]);
        $this->assertSoftDeleted('import_agents', ['id' => $importAgent->id]);
    }

    public function test_can_create_carrier(): void
    {
        $carrier = Carrier::factory()->create([
            'name' => 'Maersk Line',
            'code' => 'MAEU',
        ]);

        $this->assertDatabaseHas('carriers', [
            'id' => $carrier->id,
            'name' => 'Maersk Line',
            'code' => 'MAEU',
        ]);

        $this->assertEquals('Maersk Line', (string) $carrier);
    }

    public function test_master_bill_belongs_to_carrier(): void
    {
        $carrier = Carrier::factory()->create([
            'name' => 'MSC',
            'code' => 'MSC',
        ]);

        $masterBill = MasterBill::create([
            'date' => now(),
            'console' => 'CON-TEST-003',
            'ocean_bill_of_lading' => 'OBL-TEST-003',
            'etd' => now()->toDateString(),
            'eta' => now()->addDays(5)->toDateString(),
            'carrier_id' => $carrier->id,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->assertTrue($masterBill->carrier->is($carrier));
        $this->assertTrue($carrier->masterBills->contains($masterBill));
    }

    public function test_carrier_supports_soft_deletes(): void
    {
        $carrier = Carrier::factory()->create();
        $carrier->delete();

        $this->assertSoftDeleted('carriers', ['id' => $carrier->id]);
    }
}
