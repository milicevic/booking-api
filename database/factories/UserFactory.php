<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'client',
            'client_id' => null,
            'can_edit_slots' => false,
        ];
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'client',
            'client_id' => null,
        ]);
    }

    public function worker(int $clientId): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'worker',
            'client_id' => $clientId,
            'password' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'client_id' => null,
        ]);
    }

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'superadmin',
            'client_id' => null,
            'tenant_id' => null,
        ]);
    }
}
