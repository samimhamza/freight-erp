<?php

namespace Tests\Feature;

use App\Enums\ContainerType;
use App\Filament\Resources\HouseBills\Pages\ListHouseBills;
use App\Filament\Resources\HouseBills\Pages\ViewHouseBill;
use App\Filament\Resources\MasterBills\Pages\ViewMasterBill;
use App\Mail\ArrivalNoticeMail;
use App\Models\Carrier;
use App\Models\Consignor;
use App\Models\Container;
use App\Models\Country;
use App\Models\Customer;
use App\Models\HouseBill;
use App\Models\HouseBillContainer;
use App\Models\Location;
use App\Models\MasterBill;
use App\Models\User;
use App\Models\Vessel;
use App\Services\SendArrivalNotice;
use App\Support\Pdf\ArrivalNoticePdfData;
use App\Support\Pdf\PdfFilename;
use Carbon\Carbon;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ArrivalNoticeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    protected function grantAllPermissions(): void
    {
        Gate::before(fn () => true);
    }

    public function test_arrival_notice_pdf_data_and_view_render_successfully(): void
    {
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();
        $this->assertNotNull($houseBill);

        $data = ArrivalNoticePdfData::for($houseBill);

        $this->assertArrayHasKey('houseBill', $data);
        $this->assertArrayHasKey('masterBill', $data);
        $this->assertArrayHasKey('customer', $data);
        $this->assertArrayHasKey('consignor', $data);
        $this->assertArrayHasKey('containers', $data);
        $this->assertArrayHasKey('totals', $data);

        $view = view('pdf.arrival_notices.show', array_merge($data, [
            'company' => config('company'),
        ]))->render();

        $this->assertStringContainsString('ARRIVAL NOTICE', $view);
        $this->assertStringContainsString($houseBill->house_bill, $view);
        $this->assertStringContainsString($houseBill->masterBill->ocean_bill_of_lading, $view);
        $this->assertStringContainsString($houseBill->customer->name, $view);
        $this->assertStringContainsString($houseBill->consignor->name, $view);

        $firstContainer = $houseBill->containers->first();
        $this->assertStringContainsString($firstContainer->container->container_number, $view);
    }

    public function test_pdf_filename_for_arrival_notice(): void
    {
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();
        $filename = PdfFilename::forArrivalNotice($houseBill);

        $this->assertStringStartsWith('Arrival_Notice_', $filename);
        $this->assertStringContainsString($houseBill->house_bill, $filename);
    }

    public function test_arrival_notice_print_route_requires_authentication(): void
    {
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();

        $response = $this->getJson(route('house-bills.arrival-notice', $houseBill));
        $response->assertUnauthorized();
    }

    public function test_arrival_notice_print_route_renders_pdf_for_authenticated_user(): void
    {
        $this->grantAllPermissions();
        Pdf::fake();
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => now()->addDays(2)->toDateString()]);

        $response = $this->actingAs($this->user)
            ->get(route('house-bills.arrival-notice', $houseBill));

        $response->assertSuccessful();
        Pdf::assertRespondedWithPdf(fn () => true);
    }

    public function test_arrival_notice_mail_envelope_and_content(): void
    {
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();
        $mail = new ArrivalNoticeMail($houseBill);

        $envelope = $mail->envelope();
        $this->assertStringContainsString("Arrival Notice - {$houseBill->house_bill}", $envelope->subject);

        $content = $mail->content();
        $this->assertSame('emails.arrival_notices.sent', $content->markdown);
        $this->assertSame($houseBill->id, $content->with['houseBill']->id);
        $this->assertSame($houseBill->customer->id, $content->with['customer']->id);

        $attachments = $mail->attachments();
        $this->assertCount(1, $attachments);
    }

    public function test_send_arrival_notice_service_sends_email_and_marks_timestamp(): void
    {
        Mail::fake();
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => now()->addDays(2)->toDateString()]);
        $this->assertNull($houseBill->arrival_notice_sent_at);

        $service = app(SendArrivalNotice::class);
        $service->send($houseBill);

        Mail::assertSent(ArrivalNoticeMail::class, function (ArrivalNoticeMail $mail) use ($houseBill) {
            return $mail->hasTo($houseBill->customer->email)
                && $mail->houseBill->is($houseBill);
        });

        $houseBill->refresh();
        $this->assertNotNull($houseBill->arrival_notice_sent_at);
        $this->assertInstanceOf(Carbon::class, $houseBill->arrival_notice_sent_at);
    }

    public function test_send_arrival_notice_fails_when_master_bill_has_no_ata(): void
    {
        Mail::fake();
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => null]);

        $service = app(SendArrivalNotice::class);

        $this->expectException(ValidationException::class);
        $service->send($houseBill);
    }

    public function test_send_arrival_notice_fails_when_customer_has_no_email(): void
    {
        Mail::fake();
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => now()->addDays(2)->toDateString()]);
        $houseBill->customer->update(['email' => null]);

        $service = app(SendArrivalNotice::class);

        $this->expectException(ValidationException::class);
        $service->send($houseBill);
    }

    public function test_customer_isolation_each_customer_only_receives_their_own_containers_and_house_bill(): void
    {
        $country = Country::create(['name' => 'United Arab Emirates', 'code' => 'AE']);
        $origin = Location::create(['name' => 'Fremantle', 'code' => 'AUFRE']);
        $destination = Location::create(['name' => 'Jebel Ali', 'code' => 'AEJEA']);
        $vessel = Vessel::create(['name' => 'MSC TOKYO', 'voyage' => 'MA547R', 'imo' => '9318046']);
        $carrier = Carrier::create(['name' => 'MSC', 'code' => 'MSC']);

        $masterBill = MasterBill::create([
            'date' => now()->subDays(15),
            'console' => 'CON-TEST-ISOLATION',
            'ocean_bill_of_lading' => 'MEDUGT696143',
            'origin_id' => $origin->id,
            'port_of_discharge_id' => $destination->id,
            'destination_id' => $destination->id,
            'etd' => now()->subDays(10)->toDateString(),
            'eta' => now()->addDays(2)->toDateString(),
            'ata' => now()->addDays(2)->toDateString(),
            'vessel_id' => $vessel->id,
            'carrier_id' => $carrier->id,
        ]);

        $consignor = Consignor::create([
            'name' => 'WESTWIDE MOTOR WRECKERS',
            'address' => '39 Austin Avenue',
            'country_id' => $country->id,
        ]);

        // Customer A with HBL A and Container A
        $customerA = Customer::create([
            'name' => 'Customer A Auto Parts',
            'email' => 'customer_a@example.com',
            'country_id' => $country->id,
        ]);
        $houseBillA = HouseBill::create([
            'master_bill_id' => $masterBill->id,
            'house_bill' => 'HBL-CUST-A-001',
            'consignor_id' => $consignor->id,
            'customer_id' => $customerA->id,
            'notify_party_id' => $customerA->id,
        ]);
        $containerA = Container::create([
            'container_number' => 'MSCU1111111',
            'type' => ContainerType::HC40,
            'tare_weight' => 3800,
            'volume' => 67.5,
        ]);
        HouseBillContainer::create([
            'container_id' => $containerA->id,
            'house_bill_id' => $houseBillA->id,
            'packages' => 50,
            'seal' => 'SEAL-A-001',
            'net_weight' => 10000,
            'gross_weight' => 12000,
            'descriptions_of_goods' => 'Specific Goods for Customer A Only',
        ]);

        // Customer B with HBL B and Container B on the SAME master bill
        $customerB = Customer::create([
            'name' => 'Customer B Trading LLC',
            'email' => 'customer_b@example.com',
            'country_id' => $country->id,
        ]);
        $houseBillB = HouseBill::create([
            'master_bill_id' => $masterBill->id,
            'house_bill' => 'HBL-CUST-B-002',
            'consignor_id' => $consignor->id,
            'customer_id' => $customerB->id,
            'notify_party_id' => $customerB->id,
        ]);
        $containerB = Container::create([
            'container_number' => 'MSCU2222222',
            'type' => ContainerType::HC45,
            'tare_weight' => 2200,
            'volume' => 33.0,
        ]);
        HouseBillContainer::create([
            'container_id' => $containerB->id,
            'house_bill_id' => $houseBillB->id,
            'packages' => 80,
            'seal' => 'SEAL-B-002',
            'net_weight' => 7000,
            'gross_weight' => 8500,
            'descriptions_of_goods' => 'Secret Goods for Customer B Only',
        ]);

        // Verify Customer A's PDF data and rendered view contains ONLY A's details
        $dataA = ArrivalNoticePdfData::for($houseBillA);
        $htmlA = view('pdf.arrival_notices.show', array_merge($dataA, ['company' => config('company')]))->render();

        $this->assertStringContainsString('Customer A Auto Parts', $htmlA);
        $this->assertStringContainsString('HBL-CUST-A-001', $htmlA);
        $this->assertStringContainsString('MSCU1111111', $htmlA);
        $this->assertStringContainsString('Specific Goods for Customer A Only', $htmlA);

        // STRICT ISOLATION ASSERTIONS: Customer B details must NOT be in Customer A's PDF
        $this->assertStringNotContainsString('Customer B Trading LLC', $htmlA);
        $this->assertStringNotContainsString('HBL-CUST-B-002', $htmlA);
        $this->assertStringNotContainsString('MSCU2222222', $htmlA);
        $this->assertStringNotContainsString('Secret Goods for Customer B Only', $htmlA);

        // Verify Customer B's PDF data and rendered view contains ONLY B's details
        $dataB = ArrivalNoticePdfData::for($houseBillB);
        $htmlB = view('pdf.arrival_notices.show', array_merge($dataB, ['company' => config('company')]))->render();

        $this->assertStringContainsString('Customer B Trading LLC', $htmlB);
        $this->assertStringContainsString('HBL-CUST-B-002', $htmlB);
        $this->assertStringContainsString('MSCU2222222', $htmlB);
        $this->assertStringContainsString('Secret Goods for Customer B Only', $htmlB);

        // STRICT ISOLATION ASSERTIONS: Customer A details must NOT be in Customer B's PDF
        $this->assertStringNotContainsString('Customer A Auto Parts', $htmlB);
        $this->assertStringNotContainsString('HBL-CUST-A-001', $htmlB);
        $this->assertStringNotContainsString('MSCU1111111', $htmlB);
        $this->assertStringNotContainsString('Specific Goods for Customer A Only', $htmlB);
    }

    public function test_scheduled_command_sends_arrival_notices_2_days_before_ata(): void
    {
        Mail::fake();

        $country = Country::create(['name' => 'United Arab Emirates', 'code' => 'AE']);
        $origin = Location::create(['name' => 'Origin Port', 'code' => 'ORG']);
        $destination = Location::create(['name' => 'Destination Port', 'code' => 'DST']);
        $consignor = Consignor::create(['name' => 'Consignor A', 'country_id' => $country->id]);

        // 1. Eligible Master Bill: ATA is exactly 2 days away, House Bill unsent
        $masterBillEligible = MasterBill::create([
            'date' => now()->subDays(10),
            'console' => 'CNS-ELIGIBLE',
            'ocean_bill_of_lading' => 'OBL-ELIGIBLE',
            'origin_id' => $origin->id,
            'port_of_discharge_id' => $destination->id,
            'destination_id' => $destination->id,
            'etd' => now()->subDays(8)->toDateString(),
            'eta' => now()->addDays(2)->toDateString(),
            'ata' => now()->addDays(2)->toDateString(), // 2 days away!
        ]);
        $customerEligible = Customer::create([
            'name' => 'Eligible Customer',
            'email' => 'eligible@example.com',
            'country_id' => $country->id,
        ]);
        $hblEligible = HouseBill::create([
            'master_bill_id' => $masterBillEligible->id,
            'house_bill' => 'HBL-ELIGIBLE',
            'consignor_id' => $consignor->id,
            'customer_id' => $customerEligible->id,
            'notify_party_id' => $customerEligible->id,
            'arrival_notice_sent_at' => null,
        ]);
        $container1 = Container::create(['container_number' => 'CONT-ELIG-01', 'type' => ContainerType::HC40, 'tare_weight' => 3000, 'volume' => 60]);
        HouseBillContainer::create([
            'container_id' => $container1->id,
            'house_bill_id' => $hblEligible->id,
            'packages' => 10,
            'seal' => 'SEAL-01',
            'gross_weight' => 5000,
        ]);

        // 2. Ineligible Master Bill: ATA is 10 days away (too far)
        $masterBillTooFar = MasterBill::create([
            'date' => now()->subDays(5),
            'console' => 'CNS-TOOFAR',
            'ocean_bill_of_lading' => 'OBL-TOOFAR',
            'origin_id' => $origin->id,
            'destination_id' => $destination->id,
            'etd' => now()->subDays(2)->toDateString(),
            'eta' => now()->addDays(10)->toDateString(),
            'ata' => now()->addDays(10)->toDateString(), // 10 days away
        ]);
        $customerTooFar = Customer::create([
            'name' => 'Future Customer',
            'email' => 'future@example.com',
            'country_id' => $country->id,
        ]);
        $hblTooFar = HouseBill::create([
            'master_bill_id' => $masterBillTooFar->id,
            'house_bill' => 'HBL-TOOFAR',
            'consignor_id' => $consignor->id,
            'customer_id' => $customerTooFar->id,
            'notify_party_id' => $customerTooFar->id,
            'arrival_notice_sent_at' => null,
        ]);
        $container2 = Container::create(['container_number' => 'CONT-TOOFAR-01', 'type' => ContainerType::HC40, 'tare_weight' => 3000, 'volume' => 60]);
        HouseBillContainer::create([
            'container_id' => $container2->id,
            'house_bill_id' => $hblTooFar->id,
            'packages' => 10,
            'seal' => 'SEAL-02',
            'gross_weight' => 5000,
        ]);

        // 3. Already Sent Master Bill: ATA is 2 days away, but notice was already sent
        $masterBillAlreadySent = MasterBill::create([
            'date' => now()->subDays(10),
            'console' => 'CNS-ALREADY',
            'ocean_bill_of_lading' => 'OBL-ALREADY',
            'origin_id' => $origin->id,
            'destination_id' => $destination->id,
            'etd' => now()->subDays(8)->toDateString(),
            'eta' => now()->addDays(2)->toDateString(),
            'ata' => now()->addDays(2)->toDateString(),
        ]);
        $customerAlreadySent = Customer::create([
            'name' => 'Already Customer',
            'email' => 'already@example.com',
            'country_id' => $country->id,
        ]);
        $hblAlreadySent = HouseBill::create([
            'master_bill_id' => $masterBillAlreadySent->id,
            'house_bill' => 'HBL-ALREADY',
            'consignor_id' => $consignor->id,
            'customer_id' => $customerAlreadySent->id,
            'notify_party_id' => $customerAlreadySent->id,
            'arrival_notice_sent_at' => now()->subDay(), // already sent yesterday
        ]);
        $container3 = Container::create(['container_number' => 'CONT-ALREADY-01', 'type' => ContainerType::HC40, 'tare_weight' => 3000, 'volume' => 60]);
        HouseBillContainer::create([
            'container_id' => $container3->id,
            'house_bill_id' => $hblAlreadySent->id,
            'packages' => 10,
            'seal' => 'SEAL-03',
            'gross_weight' => 5000,
        ]);

        // Run the scheduled command
        $this->artisan('arrival-notices:send --days=2')
            ->assertSuccessful()
            ->expectsOutputToContain('Successfully sent: 1');

        // Verify: Eligible customer received email
        Mail::assertSent(ArrivalNoticeMail::class, function (ArrivalNoticeMail $mail) use ($customerEligible) {
            return $mail->hasTo($customerEligible->email);
        });

        // Verify: Ineligible and already-sent customers did NOT receive email
        Mail::assertNotSent(ArrivalNoticeMail::class, function (ArrivalNoticeMail $mail) use ($customerTooFar) {
            return $mail->hasTo($customerTooFar->email);
        });
        Mail::assertNotSent(ArrivalNoticeMail::class, function (ArrivalNoticeMail $mail) use ($customerAlreadySent) {
            return $mail->hasTo($customerAlreadySent->email);
        });

        // Verify: Timestamp is now set on the eligible house bill
        $hblEligible->refresh();
        $this->assertNotNull($hblEligible->arrival_notice_sent_at);
    }

    public function test_filament_view_house_bill_actions_render_and_can_send_notice(): void
    {
        $this->grantAllPermissions();
        Mail::fake();
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();
        $this->actingAs($this->user);

        // When ATA is null, both print and send arrival notice actions are hidden
        $houseBill->masterBill->update(['ata' => null]);
        Livewire::test(ViewHouseBill::class, ['record' => $houseBill->getKey()])
            ->assertSuccessful()
            ->assertActionExists('print')
            ->assertActionHidden('arrivalNotice')
            ->assertActionHidden('sendArrivalNotice');

        // When ATA is set, both arrivalNotice and sendArrivalNotice actions become visible
        $houseBill->masterBill->update(['ata' => now()->addDays(2)->toDateString()]);
        Livewire::test(ViewHouseBill::class, ['record' => $houseBill->getKey()])
            ->assertSuccessful()
            ->assertSee('Total Containers')
            ->assertActionVisible('arrivalNotice')
            ->assertActionVisible('sendArrivalNotice')
            ->callAction('sendArrivalNotice');

        Mail::assertSent(ArrivalNoticeMail::class, function (ArrivalNoticeMail $mail) use ($houseBill) {
            return $mail->hasTo($houseBill->customer->email);
        });

        $houseBill->refresh();
        $this->assertNotNull($houseBill->arrival_notice_sent_at);
    }

    public function test_filament_view_master_bill_can_send_arrival_notices(): void
    {
        $this->grantAllPermissions();
        Mail::fake();
        $this->seed(DemoDataSeeder::class);

        $masterBill = MasterBill::first();
        $this->actingAs($this->user);

        // When ATA is null, sendArrivalNotices is hidden
        $masterBill->update(['ata' => null]);
        Livewire::test(ViewMasterBill::class, ['record' => $masterBill->getKey()])
            ->assertSuccessful()
            ->assertActionHidden('sendArrivalNotices');

        // When ATA is set, sendArrivalNotices is visible and callable
        $masterBill->update(['ata' => now()->addDays(2)->toDateString()]);
        Livewire::test(ViewMasterBill::class, ['record' => $masterBill->getKey()])
            ->assertSuccessful()
            ->assertActionVisible('sendArrivalNotices')
            ->callAction('sendArrivalNotices');

        Mail::assertSent(ArrivalNoticeMail::class);
    }

    public function test_unauthorized_user_cannot_print_arrival_notice_and_receives_403(): void
    {
        $this->seed(DemoDataSeeder::class);
        $houseBill = HouseBill::first();

        $plainUser = User::factory()->create();

        $response = $this->actingAs($plainUser)
            ->get(route('house-bills.arrival-notice', $houseBill));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_print_arrival_notice(): void
    {
        Pdf::fake();
        $this->seed(DemoDataSeeder::class);
        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => now()->addDays(2)->toDateString()]);

        Permission::firstOrCreate(['name' => 'ArrivalNotice:HouseBill', 'guard_name' => 'web']);
        $authorizedUser = User::factory()->create();
        $authorizedUser->givePermissionTo('ArrivalNotice:HouseBill');

        $response = $this->actingAs($authorizedUser)
            ->get(route('house-bills.arrival-notice', $houseBill));

        $response->assertSuccessful();
    }

    public function test_cannot_print_arrival_notice_when_ata_is_null_and_receives_404(): void
    {
        $this->seed(DemoDataSeeder::class);
        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => null]);

        Permission::firstOrCreate(['name' => 'ArrivalNotice:HouseBill', 'guard_name' => 'web']);
        $authorizedUser = User::factory()->create();
        $authorizedUser->givePermissionTo('ArrivalNotice:HouseBill');

        $response = $this->actingAs($authorizedUser)
            ->get(route('house-bills.arrival-notice', $houseBill));

        $response->assertNotFound();
    }

    public function test_unauthorized_user_cannot_see_arrival_notice_actions_on_view_house_bill(): void
    {
        $this->seed(DemoDataSeeder::class);
        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => now()->addDays(2)->toDateString()]);

        Permission::firstOrCreate(['name' => 'ViewAny:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:HouseBill', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo(['ViewAny:HouseBill', 'View:HouseBill']);

        $this->actingAs($user);

        Livewire::test(ViewHouseBill::class, ['record' => $houseBill->getKey()])
            ->assertSuccessful()
            ->assertActionHidden('arrivalNotice')
            ->assertActionHidden('sendArrivalNotice');
    }

    public function test_authorized_user_can_see_arrival_notice_actions_on_view_house_bill(): void
    {
        $this->seed(DemoDataSeeder::class);
        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => now()->addDays(2)->toDateString()]);

        Permission::firstOrCreate(['name' => 'ViewAny:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ArrivalNotice:HouseBill', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo(['ViewAny:HouseBill', 'View:HouseBill', 'ArrivalNotice:HouseBill']);

        $this->actingAs($user);

        Livewire::test(ViewHouseBill::class, ['record' => $houseBill->getKey()])
            ->assertSuccessful()
            ->assertActionVisible('arrivalNotice')
            ->assertActionVisible('sendArrivalNotice');
    }

    public function test_arrival_notice_table_actions_visibility_based_on_permission(): void
    {
        $this->seed(DemoDataSeeder::class);
        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => now()->addDays(2)->toDateString()]);

        Permission::firstOrCreate(['name' => 'ViewAny:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ArrivalNotice:HouseBill', 'guard_name' => 'web']);

        $unauthorizedUser = User::factory()->create();
        $unauthorizedUser->givePermissionTo('ViewAny:HouseBill');

        $this->actingAs($unauthorizedUser);
        Livewire::test(ListHouseBills::class)
            ->assertSuccessful()
            ->assertTableActionHidden('arrivalNotice', $houseBill)
            ->assertTableActionHidden('sendArrivalNotice', $houseBill);

        $authorizedUser = User::factory()->create();
        $authorizedUser->givePermissionTo(['ViewAny:HouseBill', 'ArrivalNotice:HouseBill']);

        $this->actingAs($authorizedUser);
        Livewire::test(ListHouseBills::class)
            ->assertSuccessful()
            ->assertTableActionVisible('arrivalNotice', $houseBill)
            ->assertTableActionVisible('sendArrivalNotice', $houseBill);
    }

    public function test_send_arrival_notices_on_master_bill_requires_arrival_notice_permission(): void
    {
        $this->seed(DemoDataSeeder::class);
        $masterBill = MasterBill::first();
        $masterBill->update(['ata' => now()->addDays(2)->toDateString()]);

        Permission::firstOrCreate(['name' => 'ViewAny:MasterBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:MasterBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ArrivalNotice:HouseBill', 'guard_name' => 'web']);

        $unauthorizedUser = User::factory()->create();
        $unauthorizedUser->givePermissionTo(['ViewAny:MasterBill', 'View:MasterBill']);

        $this->actingAs($unauthorizedUser);
        Livewire::test(ViewMasterBill::class, ['record' => $masterBill->getKey()])
            ->assertSuccessful()
            ->assertActionHidden('sendArrivalNotices');

        $authorizedUser = User::factory()->create();
        $authorizedUser->givePermissionTo(['ViewAny:MasterBill', 'View:MasterBill', 'ArrivalNotice:HouseBill']);

        $this->actingAs($authorizedUser);
        Livewire::test(ViewMasterBill::class, ['record' => $masterBill->getKey()])
            ->assertSuccessful()
            ->assertActionVisible('sendArrivalNotices');
    }

    public function test_arrival_notice_actions_hidden_when_ata_is_null_even_for_authorized_user(): void
    {
        $this->seed(DemoDataSeeder::class);
        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => null]);

        Permission::firstOrCreate(['name' => 'ViewAny:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ArrivalNotice:HouseBill', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo(['ViewAny:HouseBill', 'View:HouseBill', 'ArrivalNotice:HouseBill']);

        $this->actingAs($user);

        // View page: both arrival notice actions hidden when ATA is null
        Livewire::test(ViewHouseBill::class, ['record' => $houseBill->getKey()])
            ->assertSuccessful()
            ->assertActionHidden('arrivalNotice')
            ->assertActionHidden('sendArrivalNotice');

        // Table page: both arrival notice actions hidden when ATA is null
        Livewire::test(ListHouseBills::class)
            ->assertSuccessful()
            ->assertTableActionHidden('arrivalNotice', $houseBill)
            ->assertTableActionHidden('sendArrivalNotice', $houseBill);
    }

    public function test_send_arrival_notice_modal_shows_warning_when_already_sent(): void
    {
        $this->seed(DemoDataSeeder::class);
        $houseBill = HouseBill::first();
        $houseBill->masterBill->update(['ata' => now()->addDays(2)->toDateString()]);
        $houseBill->update(['arrival_notice_sent_at' => now()->subDay()]);

        Permission::firstOrCreate(['name' => 'ViewAny:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ArrivalNotice:HouseBill', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo(['ViewAny:HouseBill', 'View:HouseBill', 'ArrivalNotice:HouseBill']);

        $this->actingAs($user);

        $component = Livewire::test(ViewHouseBill::class, ['record' => $houseBill->getKey()])
            ->assertSuccessful()
            ->assertActionVisible('sendArrivalNotice');

        $action = $component->instance()->getAction('sendArrivalNotice');
        $heading = value($action->getModalHeading(), $houseBill);
        $description = value($action->getModalDescription(), $houseBill);

        $this->assertSame('Send Arrival Notice Again?', $heading);
        $this->assertStringContainsString('Customer has already received the arrival notice', (string) $description);
        $this->assertStringContainsString('Do you want to send the arrival notice', (string) $description);
    }

    public function test_send_arrival_notice_service_fails_when_more_than_14_days_after_ata(): void
    {
        Mail::fake();
        $this->seed(DemoDataSeeder::class);

        $houseBill = HouseBill::first();
        // Set ATA to 15 days ago (past the 14-day cutoff)
        $houseBill->masterBill->update(['ata' => now()->subDays(15)->toDateString()]);

        $service = app(SendArrivalNotice::class);

        $this->expectException(ValidationException::class);
        $service->send($houseBill);
    }

    public function test_send_arrival_notice_actions_hidden_after_14_days_but_print_remains_visible(): void
    {
        $this->seed(DemoDataSeeder::class);
        $houseBill = HouseBill::first();
        // 15 days after ATA (expired for sending)
        $houseBill->masterBill->update(['ata' => now()->subDays(15)->toDateString()]);

        Permission::firstOrCreate(['name' => 'ViewAny:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:HouseBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ArrivalNotice:HouseBill', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo(['ViewAny:HouseBill', 'View:HouseBill', 'ArrivalNotice:HouseBill']);

        $this->actingAs($user);

        // On ViewHouseBill: sendArrivalNotice is hidden, but arrivalNotice (Print) is STILL VISIBLE!
        Livewire::test(ViewHouseBill::class, ['record' => $houseBill->getKey()])
            ->assertSuccessful()
            ->assertActionVisible('arrivalNotice')
            ->assertActionHidden('sendArrivalNotice');

        // On ListHouseBills: sendArrivalNotice is hidden, but arrivalNotice (Print) is STILL VISIBLE!
        Livewire::test(ListHouseBills::class)
            ->assertSuccessful()
            ->assertTableActionVisible('arrivalNotice', $houseBill)
            ->assertTableActionHidden('sendArrivalNotice', $houseBill);
    }

    public function test_master_bill_send_arrival_notices_hidden_after_14_days_from_ata(): void
    {
        $this->seed(DemoDataSeeder::class);
        $masterBill = MasterBill::first();
        $masterBill->update(['ata' => now()->subDays(15)->toDateString()]);

        Permission::firstOrCreate(['name' => 'ViewAny:MasterBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:MasterBill', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ArrivalNotice:HouseBill', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo(['ViewAny:MasterBill', 'View:MasterBill', 'ArrivalNotice:HouseBill']);

        $this->actingAs($user);

        // sendArrivalNotices on Master Bill is hidden when ATA > 14 days ago
        Livewire::test(ViewMasterBill::class, ['record' => $masterBill->getKey()])
            ->assertSuccessful()
            ->assertActionHidden('sendArrivalNotices');

        // But when within 14 days (e.g. 10 days ago), it is visible
        $masterBill->update(['ata' => now()->subDays(10)->toDateString()]);
        Livewire::test(ViewMasterBill::class, ['record' => $masterBill->getKey()])
            ->assertSuccessful()
            ->assertActionVisible('sendArrivalNotices');
    }
}
