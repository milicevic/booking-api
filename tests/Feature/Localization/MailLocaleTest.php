<?php

namespace Tests\Feature\Localization;

use App\Models\Booking;
use App\Models\ClientProfile;
use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingPendingClient;
use App\Notifications\BookingPendingCustomer;
use App\Notifications\WelcomeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

class MailLocaleTest extends TestCase
{
    use RefreshDatabase;

    private function bookSlot(string $acceptLanguage): User
    {
        $client = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        ClientProfile::factory()->create(['user_id' => $client->id]);
        $worker = User::factory()->worker($client->id)->create(['tenant_id' => $this->tenant->id]);
        $slot = Slot::factory()->create(['worker_id' => $worker->id, 'is_available' => true]);

        $this->withHeader('Accept-Language', $acceptLanguage)
            ->postJson('/api/bookings', [
                'slot_id' => $slot->id,
                'customer_name' => 'Ana Anić',
                'customer_email' => 'ana@example.com',
            ])
            ->assertCreated();

        return $client;
    }

    /**
     * @return list<string>
     */
    private function sentSubjects(): array
    {
        /** @var ArrayTransport $transport */
        $transport = app('mailer')->getSymfonyTransport();

        return $transport->messages()
            ->map(fn (SentMessage $message) => $message->getOriginalMessage()->getSubject())
            ->values()
            ->all();
    }

    public function test_client_gets_mail_in_tenant_language_even_when_customer_books_in_english(): void
    {
        Notification::fake();
        $this->tenant->update(['locale' => 'sr']);

        $client = $this->bookSlot('en-US,en;q=0.9');

        Notification::assertSentTo($client, BookingPendingClient::class, fn ($notification, $channels, $notifiable, $locale) => $locale === 'sr');
    }

    public function test_customer_gets_mail_in_language_they_booked_in(): void
    {
        Notification::fake();
        $this->tenant->update(['locale' => 'sr']);

        $this->bookSlot('en-US,en;q=0.9');

        Notification::assertSentTo(
            Booking::firstOrFail(),
            BookingPendingCustomer::class,
            fn ($notification, $channels, $notifiable, $locale) => $locale === 'en'
        );
    }

    public function test_tenant_in_english_sends_client_mail_in_english(): void
    {
        Notification::fake();
        $this->tenant->update(['locale' => 'en']);

        $client = $this->bookSlot('sr');

        Notification::assertSentTo($client, BookingPendingClient::class, fn ($notification, $channels, $notifiable, $locale) => $locale === 'en');
    }

    public function test_rendered_mails_are_in_serbian_for_serbian_tenant(): void
    {
        $this->tenant->update(['locale' => 'sr']);

        $this->bookSlot('en-US,en;q=0.9');

        $subjects = $this->sentSubjects();
        $this->assertContains('Nova rezervacija čeka potvrdu — Ana Anić', $subjects);
        $this->assertContains('Booking request received ⏳', $subjects);
    }

    public function test_tenant_language_is_taken_from_registration(): void
    {
        Notification::fake();

        $this->withHeader('Accept-Language', 'sr')->postJson('/api/register', [
            'name' => 'Ana Anić',
            'email' => 'ana@salon.com',
            'password' => 'tajna1234',
            'app_name' => 'Salon Ana',
        ])->assertCreated()->assertJsonPath('tenant.locale', 'sr');

        $client = User::withoutGlobalScopes()->where('email', 'ana@salon.com')->firstOrFail();
        Notification::assertSentTo($client, WelcomeClient::class, fn ($notification, $channels, $notifiable, $locale) => $locale === 'sr');
    }
}
