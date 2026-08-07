<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // --- Test klijenti ---

        $clients = [
            [
                'name' => 'Admin A',
                'email' => 'a@test.com',
                'business_name' => 'Salon A',
                'subdomain' => 'salon-a',
            ],
            [
                'name' => 'Admin B',
                'email' => 'b@test.com',
                'business_name' => 'Salon B',
                'subdomain' => 'salon-b',
            ],
        ];

        foreach ($clients as $data) {
            $tenant = Tenant::updateOrCreate(
                ['subdomain' => $data['subdomain']],
                [
                    'name' => $data['business_name'],
                    'app_name' => $data['business_name'],
                    'primary_color' => '#6366f1',
                    'secondary_color' => '#a5b4fc',
                    'theme' => 'minimal',
                    'subscription_status' => 'active',
                ]
            );

            $client = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => 'client',
                    'tenant_id' => $tenant->id,
                    'password' => Hash::make(env('TEST_CLIENT_PASSWORD', 'password')),
                ]
            );

            ClientProfile::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => $client->id],
                [
                    'tenant_id' => $tenant->id,
                    'business_name' => $data['business_name'],
                ]
            );
        }
    }
}
