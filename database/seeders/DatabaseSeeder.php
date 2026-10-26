<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Admin',
            'username' => 'admin',
            'phone' => '0777222333',
            'email' => 'admin@admin.com',
            'password' => 'Admin$123',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $this->call(ShieldSeeder::class);

        Artisan::call('shield:generate', [
            '--all' => true,
            '--option' => 'policies_and_permissions',
            '--ignore-existing-policies' => true,
            '--panel' => 'admin',
            '--no-interaction' => true,
        ]);

        Artisan::call('shield:super-admin', [
            '--user' => $user->id,
            '--panel' => 'admin',
            '--no-interaction' => true,
        ]);

        $this->call(DemoDataSeeder::class);
    }
}
