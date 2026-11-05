<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Šalje probni mejl preko podrazumevanog mailera, da se na serveru proveri podešavanje (npr. Resend).
 */
class SendTestMail extends Command
{
    protected $signature = 'mail:test {email : Adresa na koju se šalje probni mejl}';

    protected $description = 'Send a test email using the configured mailer';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->error("Invalid email address: {$email}");

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');

        $this->line("Mailer: {$mailer}, from: {$from}");

        try {
            Mail::raw('Test email from '.config('app.name')." ({$mailer}). If you received it, email sending works.", function ($message) use ($email): void {
                $message->to($email)->subject(config('app.name').' — test email');
            });
        } catch (Throwable $e) {
            $this->error("Sending failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Test email sent to {$email}.");

        return self::SUCCESS;
    }
}
