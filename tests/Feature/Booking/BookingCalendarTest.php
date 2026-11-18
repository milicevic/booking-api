<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\ClientProfile;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingCancelledCustomer;
use App\Notifications\BookingConfirmedCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Poziv u kalendar: .ics sa podsetnikom, Google link, otkazivanje događaja i prekidač vlasnika.
 */
class BookingCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-12-01 08:00', 'Europe/Belgrade'));
        config(['app.platform_host' => 'slotres.click', 'app.tenant_site_port' => null]);
        $this->tenant->update(['subdomain' => 'salon-ana', 'app_name' => 'Salon Ana']);

        $this->client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $this->client->id]);
        $worker = User::factory()->worker($this->client->id)->create(['name' => 'Marija']);
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'date' => '2026-12-02', 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        $service = Service::factory()->create(['name' => 'Šišanje, pranje', 'duration_minutes' => 45]);
        $this->booking = Booking::factory()->confirmed()->create([
            'slot_id' => $slot->id,
            'service_id' => $service->id,
            'start_time' => '10:00:00',
            'end_time' => '10:45:00',
            'customer_email' => 'ana@example.com',
        ]);
    }

    private function ics(): string
    {
        return $this->get("/api/bookings/{$this->booking->token}/calendar.ics")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->getContent();
    }

    public function test_ics_contains_the_event_in_utc_with_a_reminder(): void
    {
        $ics = $this->ics();
        $unfolded = str_replace("\r\n ", '', $ics);

        $this->assertStringContainsString("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString('METHOD:PUBLISH', $ics);
        $this->assertStringContainsString("UID:booking-{$this->booking->token}@slotres.click", $unfolded);
        // 10:00 po beogradskom (zimsko vreme, UTC+1) je 09:00 UTC
        $this->assertStringContainsString('DTSTART:20261202T090000Z', $ics);
        $this->assertStringContainsString('DTEND:20261202T094500Z', $ics);
        $this->assertStringContainsString('SUMMARY:Šišanje\, pranje — Salon Ana', $unfolded);
        $this->assertStringContainsString('TRIGGER:-PT60M', $ics);
        $this->assertStringContainsString('STATUS:CONFIRMED', $ics);
        $this->assertStringContainsString("URL:https://salon-ana.slotres.click/manage/{$this->booking->token}", $unfolded);

        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }
    }

    public function test_cancelled_booking_gives_a_cancellation_of_the_same_event(): void
    {
        $this->booking->update(['status' => 'cancelled']);
        $ics = $this->ics();

        $this->assertStringContainsString('METHOD:CANCEL', $ics);
        $this->assertStringContainsString('STATUS:CANCELLED', $ics);
        $this->assertStringContainsString("UID:booking-{$this->booking->token}@slotres.click", str_replace("\r\n ", '', $ics));
        $this->assertStringNotContainsString('VALARM', $ics);
    }

    public function test_public_booking_has_google_and_ics_links(): void
    {
        $this->withHeader('X-Tenant-Subdomain', 'salon-ana')
            ->getJson("/api/bookings/{$this->booking->token}")
            ->assertJsonPath('calendar.ics_url', route('bookings.calendar', ['token' => $this->booking->token]))
            ->assertJsonPath('calendar.google_url', fn (string $url) => str_starts_with($url, 'https://calendar.google.com/calendar/render?')
                && str_contains($url, 'dates=20261202T090000Z%2F20261202T094500Z'));
    }

    public function test_owner_can_turn_calendar_invites_off(): void
    {
        $this->actingAs($this->client)
            ->patchJson('/api/client/settings', ['calendar_invites' => false])
            ->assertJsonPath('client_profile.calendar_invites', false);

        $this->get("/api/bookings/{$this->booking->token}/calendar.ics")->assertNotFound();
        $this->withHeader('X-Tenant-Subdomain', 'salon-ana')
            ->getJson("/api/bookings/{$this->booking->token}")
            ->assertJsonPath('calendar', null);
        $this->withHeader('X-Tenant-Subdomain', 'salon-ana')->getJson('/api/tenant/config')
            ->assertJsonPath('calendar_invites', false);

        $mail = (new BookingConfirmedCustomer($this->booking->fresh()))->toMail($this->booking);
        $this->assertSame([], $mail->rawAttachments);
    }

    public function test_confirmation_mail_has_ics_attachment_and_calendar_buttons(): void
    {
        $mail = (new BookingConfirmedCustomer($this->booking))->toMail($this->booking);

        $this->assertSame('termin.ics', $mail->rawAttachments[0]['name']);
        $this->assertStringContainsString('method=PUBLISH', $mail->rawAttachments[0]['options']['mime']);
        $this->assertStringContainsString('TRIGGER:-PT60M', $mail->rawAttachments[0]['data']);

        $html = (string) $mail->render();
        $this->assertStringContainsString('calendar.google.com', $html);
        $this->assertStringContainsString('calendar.ics', $html);
    }

    public function test_cancellation_mail_removes_the_event(): void
    {
        $this->booking->update(['status' => 'cancelled']);
        $mail = (new BookingCancelledCustomer($this->booking))->toMail($this->booking);

        $this->assertStringContainsString('method=CANCEL', $mail->rawAttachments[0]['options']['mime']);
        $this->assertStringContainsString('STATUS:CANCELLED', $mail->rawAttachments[0]['data']);
        $this->assertStringContainsString('termin.ics', (string) $mail->render());
    }

    public function test_customer_cancelling_on_the_site_gets_the_cancellation_mail(): void
    {
        Notification::fake();

        $this->withHeader('X-Tenant-Subdomain', 'salon-ana')
            ->patchJson("/api/bookings/{$this->booking->token}/cancel")
            ->assertOk();

        Notification::assertSentTo($this->booking, BookingCancelledCustomer::class);
    }
}
