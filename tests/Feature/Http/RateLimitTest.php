<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_limited_per_account(): void
    {
        User::factory()->client()->create(['email' => 'ana@salon.com', 'password' => bcrypt('tajna1234')]);

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/auth/login', ['email' => 'ana@salon.com', 'password' => 'pogresna'])->assertUnauthorized();
        }

        $this->withHeader('Accept-Language', 'sr')
            ->postJson('/api/auth/login', ['email' => 'ana@salon.com', 'password' => 'tajna1234'])
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Previše zahteva. Sačekajte trenutak i pokušajte ponovo.');

        // Drugi nalog sa iste adrese i dalje može da pokuša
        $this->postJson('/api/auth/login', ['email' => 'drugi@salon.com', 'password' => 'x'])->assertUnauthorized();
    }

    public function test_login_is_limited_per_ip_across_accounts(): void
    {
        foreach (range(1, 20) as $attempt) {
            $this->postJson('/api/auth/login', ['email' => "user{$attempt}@salon.com", 'password' => 'x']);
        }

        $this->postJson('/api/auth/login', ['email' => 'jos-jedan@salon.com', 'password' => 'x'])->assertTooManyRequests();
    }

    public function test_registration_is_limited(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->postJson('/api/register', [])->assertUnprocessable();
        }

        $this->postJson('/api/register', [])->assertTooManyRequests();
    }

    public function test_booking_creation_is_limited(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->postJson('/api/bookings', [])->assertUnprocessable();
        }

        $this->postJson('/api/bookings', [])->assertTooManyRequests();
    }
}
