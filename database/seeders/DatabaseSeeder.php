<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AuthDemoSeeder::class,
            OrganizationDemoSeeder::class,
            WorkflowDemoSeeder::class,
            DemoDataSeeder::class,
            ArchiveDemoSeeder::class,
        ]);
    }
}
