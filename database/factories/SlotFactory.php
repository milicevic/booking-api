<?php

namespace Database\Factories;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Slot>
 */
class SlotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hour = fake()->numberBetween(8, 17);
        $date = Carbon::today()->addDays(fake()->numberBetween(1, 30));

        return [
            'worker_id' => User::factory(),
            'date' => $date->toDateString(),
            'start_time' => sprintf('%02d:00:00', $hour),
            'end_time' => sprintf('%02d:00:00', $hour + 1),
            'is_available' => true,
        ];
    }
}
