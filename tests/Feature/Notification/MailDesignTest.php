<?php

namespace Tests\Feature\Notification;

use App\Enums\TenantTheme;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingCancelledCustomer;
use App\Notifications\BookingConfirmedClient;
use App\Notifications\BookingConfirmedCustomer;
use App\Notifications\BookingPendingClient;
use App\Notifications\BookingPendingCustomer;
use App\Notifications\BookingRejectedCustomer;
use App\Notifications\ResetPasswordLink;
use App\Notifications\VerifyEmailAddress;
use App\Notifications\WelcomeClient;
use App\Notifications\WorkerInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class MailDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant->update([
            'app_name' => 'Salon Ana',
            'primary_color' => '#e11d48',
            'logo_url' => '/storage/logos/ana.webp',
            'theme' => TenantTheme::Classic,
        ]);
    }

    private function booking(): Booking
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id, 'name' => 'Marija']);
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'date' => '2026-12-24']);
        $service = Service::factory()->create(['name' => 'Šišanje']);

        return Booking::factory()->create([
            'slot_id' => $slot->id,
            'service_id' => $service->id,
            'customer_name' => 'Jovana <b>',
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
        ]);
    }

    private function render(MailMessage $mail): string
    {
        return (string) $mail->render();
    }

    public function test_every_mail_uses_tenant_branding(): void
    {
        $booking = $this->booking();
        $user = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);

        $mails = [
            (new BookingPendingCustomer($booking))->toMail($booking),
            (new BookingConfirmedCustomer($booking))->toMail($booking),
            (new BookingRejectedCustomer($booking))->toMail($booking),
            (new BookingCancelledCustomer($booking))->toMail($booking),
            (new BookingPendingClient($booking))->toMail($user),
            (new BookingConfirmedClient($booking))->toMail($user),
            (new ResetPasswordLink('token'))->toMail($user),
            (new VerifyEmailAddress($this->tenant))->toMail($user),
            (new WelcomeClient($this->tenant))->toMail($user),
            (new WorkerInvite($this->tenant, 'invite'))->toMail($user),
        ];

        foreach ($mails as $mail) {
            $html = $this->render($mail);

            $this->assertStringContainsString('Salon Ana', $html);
            $this->assertStringContainsString(config('app.url').'/storage/logos/ana.webp', $html);
            $this->assertStringNotContainsString('laravel.com', $html);
        }
    }

    public function test_booking_mail_shows_details_card_status_and_brand_button(): void
    {
        $booking = $this->booking();
        $html = $this->render((new BookingConfirmedCustomer($booking))->toMail($booking));

        $this->assertStringContainsString('class="details"', $html);
        $this->assertStringContainsString('Marija', $html);
        $this->assertStringContainsString('Šišanje', $html);
        $this->assertStringContainsString('10:00 – 10:30', $html);
        $this->assertStringContainsString(__('manage.status.confirmed'), $html);
        $this->assertStringContainsString('background-color: #e11d48', $html);
        // Klasična tema: oštra zaobljenja
        $this->assertStringContainsString('border-radius: 4px', $html);
        // Ime kupca se ne tumači kao HTML
        $this->assertStringNotContainsString('Jovana <b>', $html);
    }

    public function test_owner_mail_has_confirm_and_reject_buttons(): void
    {
        $booking = $this->booking();
        $user = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $html = $this->render((new BookingPendingClient($booking))->toMail($user));

        $this->assertStringContainsString('button-primary', $html);
        $this->assertStringContainsString('button-destructive', $html);
        $this->assertStringContainsString(__('notifications.booking_pending_client.action_reject'), $html);
    }

    public function test_light_brand_color_gets_dark_button_text(): void
    {
        $this->tenant->update(['primary_color' => '#fde047']);
        $html = $this->render((new WelcomeClient($this->tenant))->toMail(User::factory()->client()->create(['tenant_id' => $this->tenant->id])));

        $this->assertStringContainsString('color: #111827', $html);
    }

    public function test_mail_without_tenant_falls_back_to_platform_branding(): void
    {
        // BelongsToTenant postavlja tenant_id iz current_tenant, pa platformskog korisnika simuliramo relacijom
        $admin = User::factory()->create(['name' => 'Admin'])->setRelation('tenant', null);
        $html = $this->render((new ResetPasswordLink('token'))->toMail($admin));

        $this->assertStringContainsString(config('app.name'), $html);
        $this->assertStringContainsString('background-color: #4f46e5', $html);
    }

    public function test_absolute_logo_url_from_s3_is_used_as_is(): void
    {
        $this->tenant->update(['logo_url' => 'https://media.example.com/logos/ana.webp']);
        $html = $this->render((new WelcomeClient($this->tenant))->toMail(User::factory()->client()->create(['tenant_id' => $this->tenant->id])));

        $this->assertStringContainsString('src="https://media.example.com/logos/ana.webp"', $html);
        $this->assertStringNotContainsString(config('app.url').'https://', $html);
    }
}
