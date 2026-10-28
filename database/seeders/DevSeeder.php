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

class DevSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::withoutGlobalScopes()->updateOrCreate(['email' => 'admin@test.com'], [
            'name' => 'Admin',
            'role' => 'admin',
            'tenant_id' => null,
            'password' => Hash::make('password'),
        ]);

        $clients = [
            [
                'name' => 'Admin A',
                'email' => 'a@test.com',
                'password' => Hash::make('password'),
                'business_name' => 'Salon A',
                'subdomain' => 'salon-a',
                'workers' => [],
            ],
            [
                'name' => 'Admin B',
                'email' => 'b@test.com',
                'password' => Hash::make('password'),
                'business_name' => 'Salon B',
                'subdomain' => 'salon-b',
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

            $existingWorkers = User::withoutGlobalScopes()
                ->where('client_id', $client->id)
                ->where('role', 'worker')
                ->get();

            if ($existingWorkers->isEmpty()) {
                $existingWorkers = User::factory(2)->worker($client->id)->create([
                    'tenant_id' => $tenant->id,
                ]);

                foreach ($existingWorkers as $worker) {
                    WorkerProfile::create([
                        'user_id' => $worker->id,
                        'tenant_id' => $tenant->id,
                    ]);
                }
            }

            foreach ($existingWorkers as $worker) {
                $this->createSlotsForWorker($worker->id, $tenant->id);
            }
        }
    }

    private function createSlotsForWorker(int $workerId, int $tenantId): void
    {
        $hasUpcomingSlots = Slot::withoutGlobalScopes()
            ->where('worker_id', $workerId)
            ->where('date', '>', Carbon::today())
            ->exists();

        if ($hasUpcomingSlots) {
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
