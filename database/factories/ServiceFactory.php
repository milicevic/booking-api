<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Šišanje', 'Brijanje', 'Farbanje', 'Manikir', 'Masaža']),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'price' => fake()->optional()->randomFloat(2, 5, 200),
            'is_active' => true,
        ];
    }
}
