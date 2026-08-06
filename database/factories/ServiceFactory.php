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
            'name' => $this->faker->randomElement(['Šišanje', 'Brijanje', 'Farbanje', 'Manikir', 'Masaža']),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90]),
            'price' => $this->faker->optional()->randomFloat(2, 5, 200),
            'is_active' => true,
        ];
    }
}
