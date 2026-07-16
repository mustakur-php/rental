<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create (or find) a super_admin user and log in as them.
     * This user has all permissions, so permission checks pass in tests.
     */
    protected function loginAsSuperAdmin(): User
    {
        // Ensure all permissions exist (needed for fresh DB in tests)
        $this->ensurePermissionsExist();

        $role = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['description' => 'مدير النظام']
        );

        $role->syncPermissions(Permission::all());

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    /**
     * Create the minimum set of permissions required for tests.
     */
    protected function ensurePermissionsExist(): void
    {
        $permissions = [
            'properties.view', 'properties.create', 'properties.edit', 'properties.archive',
            'units.view', 'units.create', 'units.edit', 'units.archive',
            'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.archive',
            'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.terminate', 'contracts.archive',
            'payments.view', 'payments.create',
            'maintenance.view', 'maintenance.create', 'maintenance.edit',
            'companies.view', 'companies.create', 'companies.edit', 'companies.archive',
            'reports.view',
            'users.view', 'users.create', 'users.edit', 'users.deactivate',
            'roles.view', 'roles.edit',
            'activity.view',
            'notifications.view', 'archive.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
