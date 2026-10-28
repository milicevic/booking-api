<?php

namespace Tests\Feature\Notification;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingConfirmedCustomer;
use App\Notifications\BookingPendingCustomer;
use App\Notifications\WelcomeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.platform_host' => 'slotres.click', 'app.tenant_site_port' => null]);
        $this->tenant->update(['subdomain' => 'salon-ana']);
    }

    private function booking(): Booking
    {
        $client = User::factory()->client()->create();
        $worker = User::factory()->worker($client->id)->create();
        $slot = Slot::factory()->create(['worker_id' => $worker->id]);

        return Booking::factory()->create(['slot_id' => $slot->id]);
    }

    public function test_customer_mails_link_to_manage_page_on_tenant_site(): void
    {
        $booking = $this->booking();
        $expected = "https://salon-ana.slotres.click/manage/{$booking->token}";

        $this->assertSame($expected, (new BookingPendingCustomer($booking))->toMail($booking)->actionUrl);
        $this->assertSame($expected, (new BookingConfirmedCustomer($booking))->toMail($booking)->actionUrl);
    }

    public function test_custom_domain_is_used_when_set(): void
    {
        $this->tenant->update(['custom_domain' => 'rezervacije.salon-ana.rs']);
        $booking = $this->booking();

        $this->assertSame(
            "https://rezervacije.salon-ana.rs/manage/{$booking->token}",
            (new BookingPendingCustomer($booking))->toMail($booking)->actionUrl
        );
    }

    public function test_local_port_is_added_to_tenant_links(): void
    {
        config(['app.tenant_site_port' => '5173']);
        $client = User::factory()->client()->create();

        $this->assertSame(
            'https://salon-ana.slotres.click:5173/admin/login',
            (new WelcomeClient($this->tenant))->toMail($client)->actionUrl
        );
    }
}
