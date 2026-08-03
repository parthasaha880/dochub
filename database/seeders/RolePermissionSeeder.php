<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'auth.sessions.view', 'group' => 'authentication', 'description' => 'View own sessions'],
            ['name' => 'auth.sessions.revoke', 'group' => 'authentication', 'description' => 'Revoke own sessions'],
            ['name' => 'auth.devices.view', 'group' => 'authentication', 'description' => 'View own devices'],
            ['name' => 'auth.devices.revoke', 'group' => 'authentication', 'description' => 'Revoke own devices'],
            ['name' => 'auth.login_activities.view', 'group' => 'authentication', 'description' => 'View own login activities'],
            ['name' => 'users.view', 'group' => 'users', 'description' => 'View users'],
            ['name' => 'users.manage', 'group' => 'users', 'description' => 'Manage users'],
            ['name' => 'roles.view', 'group' => 'users', 'description' => 'View roles'],
            ['name' => 'roles.manage', 'group' => 'users', 'description' => 'Manage roles'],
            ['name' => 'permissions.view', 'group' => 'users', 'description' => 'View permissions'],
            ['name' => 'permissions.manage', 'group' => 'users', 'description' => 'Manage permissions'],
            ['name' => 'organization.view', 'group' => 'organization', 'description' => 'View organization structure'],
            ['name' => 'organization.manage', 'group' => 'organization', 'description' => 'Manage organization structure'],
            ['name' => 'employees.view', 'group' => 'organization', 'description' => 'View employees'],
            ['name' => 'employees.manage', 'group' => 'organization', 'description' => 'Manage employees'],
            ['name' => 'documents.view', 'group' => 'documents', 'description' => 'View documents'],
            ['name' => 'documents.upload', 'group' => 'documents', 'description' => 'Upload documents'],
            ['name' => 'documents.download', 'group' => 'documents', 'description' => 'Download documents'],
            ['name' => 'documents.manage', 'group' => 'documents', 'description' => 'Manage documents'],
            ['name' => 'documents.delete', 'group' => 'documents', 'description' => 'Delete documents'],
            ['name' => 'folders.manage', 'group' => 'documents', 'description' => 'Manage folders'],
            ['name' => 'archive.view', 'group' => 'archive', 'description' => 'View archive and records'],
            ['name' => 'archive.manage', 'group' => 'archive', 'description' => 'Manage archive locations and records'],
            ['name' => 'workflow.view', 'group' => 'workflow', 'description' => 'View workflows and approval history'],
            ['name' => 'workflow.manage', 'group' => 'workflow', 'description' => 'Manage workflow definitions'],
            ['name' => 'workflow.submit', 'group' => 'workflow', 'description' => 'Submit documents for approval'],
            ['name' => 'workflow.approve', 'group' => 'workflow', 'description' => 'Approve, reject, or return documents'],
            ['name' => 'dashboard.view', 'group' => 'dashboard', 'description' => 'View dashboard KPIs and charts'],
            ['name' => 'search.view', 'group' => 'search', 'description' => 'Search documents'],
            ['name' => 'search.saved.manage', 'group' => 'search', 'description' => 'Manage shared/all saved searches'],
            ['name' => 'audit.view', 'group' => 'audit', 'description' => 'View audit trail'],
            ['name' => 'notifications.view', 'group' => 'notifications', 'description' => 'View in-app notifications'],
            ['name' => 'sharing.view', 'group' => 'sharing', 'description' => 'View document shares'],
            ['name' => 'sharing.manage', 'group' => 'sharing', 'description' => 'Create and revoke share links'],
            ['name' => 'retention.view', 'group' => 'retention', 'description' => 'View retention policies'],
            ['name' => 'retention.manage', 'group' => 'retention', 'description' => 'Manage retention policies and runs'],
            ['name' => 'reports.view', 'group' => 'reports', 'description' => 'View reports'],
            ['name' => 'reports.export', 'group' => 'reports', 'description' => 'Export reports as CSV'],
            ['name' => 'otp.view', 'group' => 'security', 'description' => 'View OTP book (email change codes)'],
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission['name'], 'web');
            Permission::query()
                ->where('name', $permission['name'])
                ->update([
                    'group' => $permission['group'],
                    'description' => $permission['description'],
                ]);
        }

        $roles = [
            ['name' => 'super_admin', 'description' => 'Full system access', 'hierarchy_level' => 1, 'is_system' => true],
            ['name' => 'organization_admin', 'description' => 'Organization administrator', 'hierarchy_level' => 10, 'is_system' => true],
            ['name' => 'department_admin', 'description' => 'Department administrator', 'hierarchy_level' => 20, 'is_system' => true],
            ['name' => 'manager', 'description' => 'Manager', 'hierarchy_level' => 30, 'is_system' => true],
            ['name' => 'officer', 'description' => 'Officer', 'hierarchy_level' => 40, 'is_system' => true],
            ['name' => 'data_entry_operator', 'description' => 'Data entry operator', 'hierarchy_level' => 50, 'is_system' => true],
            ['name' => 'viewer', 'description' => 'Read-only viewer', 'hierarchy_level' => 60, 'is_system' => true],
            ['name' => 'auditor', 'description' => 'Auditor', 'hierarchy_level' => 70, 'is_system' => true],
        ];

        foreach ($roles as $roleData) {
            $role = Role::findOrCreate($roleData['name'], 'web');
            $role->update([
                'description' => $roleData['description'],
                'hierarchy_level' => $roleData['hierarchy_level'],
                'is_system' => $roleData['is_system'],
            ]);
        }

        Role::findByName('super_admin')->syncPermissions(Permission::all());

        $viewPerms = Permission::query()
            ->whereIn('name', [
                'organization.view', 'employees.view', 'users.view', 'roles.view', 'permissions.view',
                'documents.view', 'documents.download', 'workflow.view', 'dashboard.view', 'search.view',
                'audit.view', 'notifications.view', 'sharing.view', 'retention.view', 'reports.view',
                'archive.view',
            ])
            ->get();

        foreach (['organization_admin', 'department_admin', 'manager', 'auditor', 'viewer', 'officer'] as $role) {
            Role::findByName($role)->givePermissionTo($viewPerms);
        }

        Role::findByName('organization_admin')->givePermissionTo([
            'organization.manage',
            'employees.manage',
            'users.manage',
            'roles.manage',
            'documents.manage',
            'documents.upload',
            'documents.delete',
            'folders.manage',
            'archive.manage',
            'workflow.manage',
            'workflow.submit',
            'workflow.approve',
            'search.saved.manage',
            'sharing.manage',
            'retention.manage',
            'reports.export',
        ]);

        Role::findByName('manager')->givePermissionTo([
            'workflow.approve',
            'workflow.submit',
        ]);

        Role::findByName('department_admin')->givePermissionTo([
            'workflow.approve',
            'workflow.submit',
        ]);

        Role::findByName('officer')->givePermissionTo([
            'documents.upload',
            'workflow.submit',
        ]);

        Role::findByName('data_entry_operator')->givePermissionTo([
            'documents.view',
            'documents.upload',
            'documents.download',
            'workflow.view',
            'workflow.submit',
            'dashboard.view',
            'search.view',
        ]);
    }
}
