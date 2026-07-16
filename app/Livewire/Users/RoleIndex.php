<?php

namespace App\Livewire\Users;

use App\Traits\HasPermissionGuard;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleIndex extends Component
{
    use HasPermissionGuard;

    public ?int   $editingRoleId = null;
    public string $editingRoleName = '';
    public array  $selectedPermissions = [];
    public bool   $showModal = false;

    // الأدوار المحمية التي لا يمكن تعديل اسمها
    private array $protectedRoles = ['super_admin'];

    // ─── تجميع الصلاحيات حسب المجال ─────────────────
    public function getGroupedPermissions(): array
    {
        $labels = [
            'properties' => 'العقارات',
            'units'       => 'الوحدات',
            'contracts'   => 'العقود',
            'payments'    => 'الدفعات',
            'tenants'     => 'المستأجرون',
            'maintenance' => 'الصيانة',
            'companies'   => 'الشركات',
            'reports'     => 'التقارير',
            'users'       => 'المستخدمون',
            'roles'       => 'الأدوار',
            'activity'    => 'سجل الحركات',
        ];

        $actionLabels = [
            'view'       => 'عرض',
            'create'     => 'إنشاء',
            'edit'       => 'تعديل',
            'delete'     => 'حذف',
            'archive'    => 'أرشفة',
            'terminate'  => 'إنهاء',
            'deactivate' => 'تعطيل',
        ];

        $grouped = [];
        foreach (Permission::orderBy('name')->get() as $permission) {
            [$domain, $action] = explode('.', $permission->name);
            $grouped[$domain]['label'] = $labels[$domain] ?? $domain;
            $grouped[$domain]['permissions'][] = [
                'id'     => $permission->id,
                'name'   => $permission->name,
                'action' => $actionLabels[$action] ?? $action,
            ];
        }
        return $grouped;
    }

    // ─── فتح مودال التعديل ───────────────────────────
    public function openEditModal(int $roleId): void
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->editingRoleId       = $roleId;
        $this->editingRoleName     = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showModal           = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['editingRoleId', 'editingRoleName', 'selectedPermissions']);
    }

    public function toggleAll(string $domain): void
    {
        $domainPermissions = Permission::where('name', 'like', "$domain.%")->pluck('name')->toArray();
        $allSelected = count(array_intersect($domainPermissions, $this->selectedPermissions)) === count($domainPermissions);

        if ($allSelected) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $domainPermissions));
        } else {
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $domainPermissions)));
        }
    }

    // ─── حفظ الصلاحيات ───────────────────────────────
    public function saveRole(): void
    {
        if (! $this->requirePermission('roles.edit')) return;
        $role = Role::findOrFail($this->editingRoleId);

        // super_admin يحتفظ دائماً بجميع الصلاحيات
        if ($role->name === 'super_admin') {
            $role->syncPermissions(Permission::all());
        } else {
            $role->syncPermissions($this->selectedPermissions);
        }

        $this->showModal = false;
        $this->dispatch('notify', message: "تم حفظ صلاحيات دور «{$role->description}» ✓");
    }

    public function render()
    {
        $roles = Role::withCount(['permissions', 'users'])->orderBy('id')->get();

        return view('livewire.users.role-index', [
            'roles'            => $roles,
            'groupedPerms'     => $this->getGroupedPermissions(),
        ]);
    }
}
