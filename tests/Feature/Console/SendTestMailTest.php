<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendTestMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_a_test_email_to_the_given_address(): void
    {
        config(['mail.default' => 'array']);
        Event::fake([MessageSent::class]);

        $this->artisan('mail:test', ['email' => 'ana@salon.com'])
            ->expectsOutputToContain('Mailer: array')
            ->expectsOutputToContain('Test email sent to ana@salon.com.')
            ->assertSuccessful();

        Event::assertDispatched(MessageSent::class, fn (MessageSent $event) => $event->message->getTo()[0]->getAddress() === 'ana@salon.com');
    }

    public function test_rejects_an_invalid_address(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['email' => 'nije-email'])
            ->expectsOutputToContain('Invalid email address')
            ->assertFailed();
    }

    public function test_reports_a_sending_failure(): void
    {
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('The slotres.click domain is not verified.'));

        $this->artisan('mail:test', ['email' => 'ana@salon.com'])
            ->expectsOutputToContain('Sending failed: The slotres.click domain is not verified.')
            ->assertFailed();
    }
}
