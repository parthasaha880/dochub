<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'email' => 'admin@edams.local',
                'name' => 'System Administrator',
                'username' => 'admin',
                'employee_id' => 'EMP-0001',
            ],
            [
                'email' => 'parthasaha31@gmail.com',
                'name' => 'Partha Saha',
                'username' => 'superadmin',
                'employee_id' => 'EMP-0002',
            ],
            [
                'email' => 'jahid@softcellbd.net',
                'name' => 'Jahid Hasan',
                'username' => 'jahid',
                'employee_id' => 'EMP-0003',
            ],
        ];

        foreach ($admins as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'username' => $row['username'],
                    'employee_id' => $row['employee_id'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('Password@12345'),
                    'password_changed_at' => now(),
                    'timezone' => 'UTC',
                    'locale' => 'en',
                    'theme' => 'system',
                ]
            );

            $user->assignRole('super_admin');
        }
    }
}
