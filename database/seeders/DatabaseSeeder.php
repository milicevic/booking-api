<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\Slot;
use App\Models\Tenant;
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
        // --- Admin — nema tenant ---

        User::withoutGlobalScopes()->updateOrCreate(['email' => 'admin@test.com'], [
            'name' => 'Admin',
            'role' => 'admin',
            'tenant_id' => null,
            'password' => Hash::make('password'),
        ]);

        // --- Klijenti sa tenantima ---

        $clients = [
            [
                'name' => 'Test Klijent',
                'email' => 'test1@test.com',
                'password' => Hash::make('password'),
                'business_name' => 'Test Salon',
                'subdomain' => 'test-salon',
                'workers' => [
                    ['name' => 'Radnik Jedan', 'email' => 'wor1@test.com'],
                    ['name' => 'Radnik Dva', 'email' => 'work2@test.com'],
                ],
            ],
            [
                'name' => 'Marko Petrović',
                'email' => 'marko@test.com',
                'password' => Hash::make('password'),
                'business_name' => 'Salon Petrović',
                'subdomain' => 'salon-petrovic',
                'workers' => [],
            ],
            [
                'name' => 'Ana Jovanović',
                'email' => 'ana@test.com',
                'password' => Hash::make('password'),
                'business_name' => 'Beauty by Ana',
                'subdomain' => 'beauty-by-ana',
                'workers' => [],
            ],
        ];

        foreach ($clients as $clientData) {
            $tenant = Tenant::updateOrCreate(['subdomain' => $clientData['subdomain']], [
                'name' => $clientData['business_name'],
                'app_name' => $clientData['business_name'],
                'primary_color' => '#6366f1',
                'secondary_color' => '#a5b4fc',
                'theme' => 'minimal',
                'subscription_status' => 'active',
            ]);

            $client = User::withoutGlobalScopes()->updateOrCreate(['email' => $clientData['email']], [
                'name' => $clientData['name'],
                'role' => 'client',
                'tenant_id' => $tenant->id,
                'password' => $clientData['password'],
            ]);

            ClientProfile::withoutGlobalScopes()->updateOrCreate(['user_id' => $client->id], [
                'tenant_id' => $tenant->id,
                'business_name' => $clientData['business_name'],
            ]);

            // Eksplicitni workeri iz konfiga
            foreach ($clientData['workers'] as $workerData) {
                $worker = User::withoutGlobalScopes()->updateOrCreate(['email' => $workerData['email']], [
                    'name' => $workerData['name'],
                    'role' => 'worker',
                    'tenant_id' => $tenant->id,
                    'client_id' => $client->id,
                    'password' => Hash::make('password'),
                ]);

                WorkerProfile::withoutGlobalScopes()->firstOrCreate(['user_id' => $worker->id], [
                    'tenant_id' => $tenant->id,
                ]);

                $this->createSlotsForWorker($worker->id, $tenant->id);
            }

            // Factory workeri za klijente bez eksplicitnih
            if (empty($clientData['workers'])) {
                $existingWorkers = User::withoutGlobalScopes()
                    ->where('client_id', $client->id)
                    ->where('role', 'worker')
                    ->get();

                if ($existingWorkers->isEmpty()) {
                    $workers = User::factory(3)->worker($client->id)->create([
                        'tenant_id' => $tenant->id,
                    ]);

                    foreach ($workers as $worker) {
                        WorkerProfile::create([
                            'user_id' => $worker->id,
                            'tenant_id' => $tenant->id,
                            'phone' => fake()->phoneNumber(),
                        ]);

                        $this->createSlotsForWorker($worker->id, $tenant->id);
                    }
                }
            }
        }
    }

    private function createSlotsForWorker(int $workerId, int $tenantId): void
    {
        $alreadyExists = Slot::withoutGlobalScopes()
            ->where('worker_id', $workerId)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $slots = [];

        for ($day = 1; $day <= 14; $day++) {
            $date = Carbon::today()->addDays($day)->toDateString();

            for ($hour = 8; $hour <= 16; $hour++) {
                $slots[] = [
                    'tenant_id' => $tenantId,
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
