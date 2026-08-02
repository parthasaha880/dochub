<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'parthasaha31@gmail.com'],
            [
                'name' => 'System Administrator',
                'username' => 'superadmin',
                'employee_id' => 'EMP-0001',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('Password@12345'),
                'password_changed_at' => now(),
                'timezone' => 'UTC',
                'locale' => 'en',
                'theme' => 'system',
            ]
        );

        $admin->assignRole('super_admin');
    }
}
