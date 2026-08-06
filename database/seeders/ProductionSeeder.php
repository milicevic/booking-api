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
        // --- Superadmin ---

        User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'superadmin@booking.app'],
            [
                'name' => 'Super Admin',
                'role' => 'superadmin',
                'tenant_id' => null,
                'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'changeme123')),
            ]
        );

        // --- Test klijenti ---

        $clients = [
            [
                'name' => 'Marko Petrović',
                'email' => 'marko@test.com',
                'business_name' => 'Salon Petrović',
                'subdomain' => 'salon-petrovic',
            ],
            [
                'name' => 'Ana Jovanović',
                'email' => 'ana@test.com',
                'business_name' => 'Beauty by Ana',
                'subdomain' => 'beauty-by-ana',
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
