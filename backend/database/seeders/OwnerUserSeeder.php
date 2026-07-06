<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Dapatkan Tenant pertama atau buat jika belum ada
        $tenant = Tenant::first();

        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Aronica Companion',
                'npwp' => '01.234.567.8-901.000',
                'email' => 'arunika.companion@gmail.com',
                'telepon' => '0819024286270',
                'alamat' => 'Jl Sumpah Pemuda Klaten'
            ]);
        }

        // 2. Buat atau update User dengan role Owner
        User::updateOrCreate(
            ['email' => 'arunika.companion@gmail.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Maria Theresia Ratna Dewi',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'telepon' => '0819024286270',
                'is_active' => true
            ]
        );
    }
}
