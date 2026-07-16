<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * جميع الصلاحيات مجمّعة حسب المجال
     */
    private array $permissions = [
        // ── العقارات ──────────────────────────────────
        'properties.view', 'properties.create', 'properties.edit', 'properties.archive',

        // ── الوحدات ───────────────────────────────────
        'units.view', 'units.create', 'units.edit', 'units.archive',

        // ── العقود ────────────────────────────────────
        'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.terminate', 'contracts.archive',

        // ── الدفعات ───────────────────────────────────
        'payments.view', 'payments.create', 'payments.edit',

        // ── المستأجرون ────────────────────────────────
        'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.archive',

        // ── التقارير ──────────────────────────────────
        'reports.view',

        // ── الصيانة ───────────────────────────────────
        'maintenance.view', 'maintenance.create', 'maintenance.edit', 'maintenance.delete',

        // ── الشركات ───────────────────────────────────
        'companies.view', 'companies.create', 'companies.edit', 'companies.archive',

        // ── المستخدمون والأدوار ───────────────────────
        'users.view', 'users.create', 'users.edit', 'users.deactivate',
        'roles.view', 'roles.edit',

        // ── سجل الحركات ──────────────────────────────
        'activity.view',

        // ── التنبيهات والأرشيف ───────────────────────
        'notifications.view', 'archive.view',
    ];

    /**
     * صلاحيات كل دور
     */
    private array $roles = [

        'super_admin' => [
            'description' => 'مدير النظام — صلاحيات كاملة',
            'permissions' => '*', // جميع الصلاحيات
        ],

        'accountant' => [
            'description' => 'محاسب — دفعات وعقود وتقارير مالية',
            'permissions' => [
                'properties.view',
                'units.view',
                'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.terminate', 'contracts.archive',
                'payments.view',  'payments.create',  'payments.edit',
                'tenants.view',   'tenants.create',   'tenants.edit',
                'reports.view',
                'companies.view',
                'activity.view',
                'notifications.view', 'archive.view',
            ],
        ],

        'property_manager' => [
            'description' => 'مدير العقارات — إدارة عقارات ووحدات وصيانة',
            'permissions' => [
                'properties.view', 'properties.create', 'properties.edit', 'properties.archive',
                'units.view',      'units.create',      'units.edit',      'units.archive',
                'contracts.view',
                'payments.view',
                'tenants.view',
                'reports.view',
                'maintenance.view', 'maintenance.create', 'maintenance.edit', 'maintenance.delete',
                'companies.view',
                'activity.view',
                'notifications.view', 'archive.view',
            ],
        ],

        'maintenance_supervisor' => [
            'description' => 'مشرف الصيانة — يتابع الصيانة ويطلع على العقارات',
            'permissions' => [
                'properties.view',
                'units.view',
                'maintenance.view', 'maintenance.create', 'maintenance.edit',
                'tenants.view',
                'notifications.view',
            ],
        ],

        'viewer' => [
            'description' => 'عارض — قراءة فقط',
            'permissions' => [
                'properties.view',
                'units.view',
                'contracts.view',
                'payments.view',
                'tenants.view',
                'reports.view',
                'companies.view',
                'maintenance.view',
                'notifications.view', 'archive.view',
            ],
        ],
    ];

    public function run(): void
    {
        // إنشاء جميع الصلاحيات
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        // إنشاء الأدوار ومنحها الصلاحيات
        foreach ($this->roles as $roleName => $config) {
            /** @var Role $role */
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ], [
                'description' => $config['description'],
            ]);

            if ($config['permissions'] === '*') {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($config['permissions']);
            }
        }

        $this->command?->info('✅ تم إنشاء ' . count($this->permissions) . ' صلاحية و ' . count($this->roles) . ' دور');
    }
}
