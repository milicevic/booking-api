<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'subdomain' => fake()->unique()->slug(2),
            'custom_domain' => null,
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'logo_url' => null,
            'app_name' => fake()->company(),
            'theme' => 'minimal',
            'trial_ends_at' => now()->addDays(7),
            'subscription_status' => 'trialing',
            'subscription_ends_at' => null,
            'deploy_status' => 'pending',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'active',
            'trial_ends_at' => null,
        ]);
    }

    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addDays(7),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'expired',
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'canceled',
        ]);
    }
}
