<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Tenant / Organization
        $tenant = Tenant::create([
            'name' => 'Royal Event Organizer',
            'npwp' => '01.234.567.8-901.000',
            'email' => 'info@royalevent.co.id',
            'telepon' => '021-88887777',
            'alamat' => 'Kuningan Tower Lt. 12, Sudirman, Jakarta Selatan'
        ]);

        // 2. Seed Users for each RBAC Role
        $defaultPassword = Hash::make('password');

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Alex Owner',
            'email' => 'owner@eventbooks.com',
            'password' => $defaultPassword,
            'role' => 'owner',
            'telepon' => '081211112222',
            'is_active' => true
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Shinta Finance',
            'email' => 'finance@eventbooks.com',
            'password' => $defaultPassword,
            'role' => 'finance_manager',
            'telepon' => '081233334444',
            'is_active' => true
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Boni Admin',
            'email' => 'admin@eventbooks.com',
            'password' => $defaultPassword,
            'role' => 'admin',
            'telepon' => '081255556666',
            'is_active' => true
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Rudi Staff',
            'email' => 'staff@eventbooks.com',
            'password' => $defaultPassword,
            'role' => 'staff',
            'telepon' => '081277778888',
            'is_active' => true
        ]);
    }
}

