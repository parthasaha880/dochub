<?php

namespace Tests\Unit\Modules\Users;

use App\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleHierarchyTest extends TestCase
{
    #[Test]
    public function role_model_exposes_hierarchy_fields(): void
    {
        $role = new Role([
            'name' => 'custom_role',
            'guard_name' => 'web',
            'hierarchy_level' => 42,
            'is_system' => false,
            'description' => 'Test',
        ]);

        $this->assertSame(42, $role->hierarchy_level);
        $this->assertFalse($role->is_system);
    }
}
