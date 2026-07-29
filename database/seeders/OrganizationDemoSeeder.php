<?php

namespace Database\Seeders;

use App\Modules\Organization\Enums\EmploymentStatus;
use App\Modules\Organization\Models\Branch;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Designation;
use App\Modules\Organization\Models\Employee;
use App\Modules\Organization\Models\Office;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Models\Section;
use App\Modules\Organization\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->updateOrCreate(
            ['code' => 'EDAMS'],
            [
                'name' => 'EDAMS Corporation',
                'legal_name' => 'EDAMS Enterprise Document Systems Ltd',
                'email' => 'info@edams.local',
                'phone' => '+10000000000',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
                'timezone' => 'Asia/Dhaka',
                'locale' => 'en',
                'currency' => 'BDT',
                'is_active' => true,
                'description' => 'Demo organization for EDAMS',
            ]
        );

        $branch = Branch::query()->updateOrCreate(
            ['organization_id' => $org->id, 'code' => 'HQ'],
            [
                'name' => 'Head Office',
                'type' => 'head_office',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
                'is_head_office' => true,
                'is_active' => true,
            ]
        );

        $department = Department::query()->updateOrCreate(
            ['organization_id' => $org->id, 'code' => 'IT'],
            [
                'branch_id' => $branch->id,
                'name' => 'Information Technology',
                'description' => 'IT & Records Systems',
                'is_active' => true,
            ]
        );

        $section = Section::query()->updateOrCreate(
            ['department_id' => $department->id, 'code' => 'ARCH'],
            [
                'organization_id' => $org->id,
                'name' => 'Archives',
                'is_active' => true,
            ]
        );

        Unit::query()->updateOrCreate(
            ['department_id' => $department->id, 'code' => 'DIG'],
            [
                'organization_id' => $org->id,
                'section_id' => $section->id,
                'name' => 'Digitization Unit',
                'is_active' => true,
            ]
        );

        $office = Office::query()->updateOrCreate(
            ['organization_id' => $org->id, 'code' => 'HO-01'],
            [
                'branch_id' => $branch->id,
                'name' => 'Main Records Office',
                'city' => 'Dhaka',
                'is_active' => true,
            ]
        );

        $designation = Designation::query()->updateOrCreate(
            ['organization_id' => $org->id, 'code' => 'SA'],
            [
                'name' => 'System Administrator',
                'grade' => 'G1',
                'level' => 1,
                'is_active' => true,
            ]
        );

        $admin = User::query()->where('email', 'admin@edams.local')->first();

        if ($admin) {
            Employee::query()->updateOrCreate(
                ['organization_id' => $org->id, 'employee_code' => 'EMP-0001'],
                [
                    'user_id' => $admin->id,
                    'first_name' => 'System',
                    'last_name' => 'Administrator',
                    'email' => $admin->email,
                    'department_id' => $department->id,
                    'section_id' => $section->id,
                    'branch_id' => $branch->id,
                    'office_id' => $office->id,
                    'designation_id' => $designation->id,
                    'joining_date' => now()->subYear()->toDateString(),
                    'employment_status' => EmploymentStatus::Active,
                    'is_active' => true,
                ]
            );
        }
    }
}
