<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\Slot;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // --- Test korisnici ---

        User::firstOrCreate(['email' => 'admin@test.com'], [
            'name' => 'Admin',
            'role' => 'admin',
            'password' => Hash::make('sifra'),
        ]);

        $testClient = User::firstOrCreate(['email' => 'test1@test.com'], [
            'name' => 'Test Klijent',
            'role' => 'client',
            'password' => Hash::make('sifra'),
        ]);

        ClientProfile::firstOrCreate(['user_id' => $testClient->id], [
            'business_name' => 'Test Salon',
        ]);

        $worker1 = User::firstOrCreate(['email' => 'wor1@test.com'], [
            'name' => 'Radnik Jedan',
            'role' => 'worker',
            'client_id' => $testClient->id,
            'password' => Hash::make('sifra'),
        ]);

        $worker2 = User::firstOrCreate(['email' => 'work2@test.com'], [
            'name' => 'Radnik Dva',
            'role' => 'worker',
            'client_id' => $testClient->id,
            'password' => Hash::make('sifra'),
        ]);

        foreach ([$worker1, $worker2] as $worker) {
            WorkerProfile::firstOrCreate(['user_id' => $worker->id]);
            $this->createSlotsForWorker($worker->id);
        }

        // --- Demo klijenti ---

        $clients = [
            ['name' => 'Marko Petrović', 'email' => 'marko@test.com', 'business_name' => 'Salon Petrović'],
            ['name' => 'Ana Jovanović', 'email' => 'ana@test.com', 'business_name' => 'Beauty by Ana'],
        ];

        // --- Kraj test korisnika ---

        foreach ($clients as $clientData) {
            $client = User::firstOrCreate(['email' => $clientData['email']], [
                'name' => $clientData['name'],
                'role' => 'client',
                'password' => Hash::make('password'),
            ]);

            ClientProfile::firstOrCreate(['user_id' => $client->id], [
                'business_name' => $clientData['business_name'],
            ]);

            if ($client->workers()->count() === 0) {
                $workers = User::factory(3)->worker($client->id)->create();

                foreach ($workers as $worker) {
                    WorkerProfile::create([
                        'user_id' => $worker->id,
                        'phone' => fake()->phoneNumber(),
                    ]);

                    $this->createSlotsForWorker($worker->id);
                }
            }
        }
    }

    private function createSlotsForWorker(int $workerId): void
    {
        $slots = [];

        for ($day = 1; $day <= 14; $day++) {
            $date = Carbon::today()->addDays($day)->toDateString();

            for ($hour = 8; $hour <= 16; $hour++) {
                $slots[] = [
                    'worker_id' => $workerId,
                    'date' => $date,
                    'start_time' => sprintf('%02d:00:00', $hour),
                    'end_time' => sprintf('%02d:00:00', $hour + 1),
                    'is_available' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Slot::insert($slots);
    }
}
