<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerProfile>
 */
class WorkerProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
