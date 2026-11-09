<?php

namespace Tests\Feature\Booking;

use App\Enums\CustomerContact;
use App\Models\Booking;
use App\Models\ClientProfile;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerContactTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Slot $slot;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        ClientProfile::factory()->create(['user_id' => $this->client->id, 'tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($this->client->id)->create(['tenant_id' => $this->tenant->id]);
        $this->slot = Slot::factory()->create(['worker_id' => $worker->id, 'start_time' => '09:00:00', 'end_time' => '10:00:00']);
        $this->service = Service::factory()->create(['is_active' => true, 'duration_minutes' => 60]);
        $worker->services()->attach($this->service->id);
    }

    private function require(CustomerContact $contact): void
    {
        $this->client->clientProfile->update(['customer_contact' => $contact]);
    }

    private function book(?string $email, ?string $phone): TestResponse
    {
        return $this->postJson('/api/bookings', array_filter([
            'slot_id' => $this->slot->id,
            'service_id' => $this->service->id,
            'customer_name' => 'Ana Anić',
            'customer_email' => $email,
            'customer_phone' => $phone,
        ]));
    }

    /**
     * @return array<string, array{CustomerContact, ?string, ?string, bool}>
     */
    public static function cases(): array
    {
        return [
            'email ili telefon: ništa' => [CustomerContact::EmailOrPhone, null, null, false],
            'email ili telefon: samo telefon' => [CustomerContact::EmailOrPhone, null, '0601234567', true],
            'email ili telefon: samo email' => [CustomerContact::EmailOrPhone, 'ana@example.com', null, true],
            'oba: samo email' => [CustomerContact::Both, 'ana@example.com', null, false],
            'oba: oba' => [CustomerContact::Both, 'ana@example.com', '0601234567', true],
            'telefon: samo email' => [CustomerContact::Phone, 'ana@example.com', null, false],
            'telefon: samo telefon' => [CustomerContact::Phone, null, '0601234567', true],
            'email: samo telefon' => [CustomerContact::Email, null, '0601234567', false],
            'email: samo email' => [CustomerContact::Email, 'ana@example.com', null, true],
        ];
    }

    #[DataProvider('cases')]
    public function test_booking_follows_the_contact_rule(CustomerContact $contact, ?string $email, ?string $phone, bool $allowed): void
    {
        $this->require($contact);

        $response = $this->book($email, $phone);

        $allowed ? $response->assertCreated() : $response->assertUnprocessable();
        $this->assertSame($allowed ? 1 : 0, Booking::where('slot_id', $this->slot->id)->count());
    }

    public function test_error_message_says_what_is_missing(): void
    {
        $this->require(CustomerContact::Phone);

        $this->withHeader('Accept-Language', 'sr')->book('ana@example.com', null)
            ->assertJsonPath('message', 'Unesite broj telefona.');
    }

    public function test_client_can_change_the_rule(): void
    {
        $this->actingAs($this->client)->patchJson('/api/client/settings', ['customer_contact' => 'both'])
            ->assertOk()
            ->assertJsonPath('client_profile.customer_contact', 'both');

        $this->actingAs($this->client)->patchJson('/api/client/settings', ['customer_contact' => 'nesto'])
            ->assertUnprocessable();
    }

    public function test_tenant_config_tells_the_booking_page_the_rule(): void
    {
        $this->getJson('/api/tenant/config')->assertJsonPath('customer_contact', 'email_or_phone');

        $this->require(CustomerContact::Phone);

        $this->getJson('/api/tenant/config')->assertJsonPath('customer_contact', 'phone');
    }
}
