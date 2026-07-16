<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Traits\HasPermissionGuard;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserIndex extends Component
{
    use WithPagination, HasPermissionGuard;

    // ─── Filters ─────────────────────────────────────
    public string  $search = '';
    public string  $roleFilter  = '';
    public string  $statusFilter = '';

    // ─── Modal state ─────────────────────────────────
    public bool   $showModal     = false;
    public bool   $showDeleteConfirm = false;
    public ?int   $editingId     = null;
    public ?int   $deletingId    = null;

    // ─── Form fields ─────────────────────────────────
    public string  $name     = '';
    public string  $email    = '';
    public string  $phone    = '';
    public string  $password = '';
    public string  $passwordConfirm = '';
    public string  $role     = '';
    public string  $status   = 'active';

    // ─── Lifecycle ───────────────────────────────────
    public function updatingSearch(): void   { $this->resetPage(); }
    public function updatingRoleFilter(): void  { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    // ─── Computed ────────────────────────────────────
    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('name')->get();
    }

    // ─── Modal: New User ─────────────────────────────
    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'password', 'passwordConfirm', 'role', 'status']);
        $this->status = 'active';
        $this->resetValidation();
        $this->showModal = true;
    }

    // ─── Modal: Edit User ────────────────────────────
    public function openEditModal(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId       = $id;
        $this->name            = $user->name;
        $this->email           = $user->email;
        $this->phone           = $user->phone ?? '';
        $this->password        = '';
        $this->passwordConfirm = '';
        $this->role            = $user->roles->first()?->name ?? '';
        $this->status          = $user->status;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    // ─── Create ──────────────────────────────────────
    public function createUser(): void
    {
        if (! $this->requirePermission('users.create')) return;
        $data = $this->validate([
            'name'            => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'unique:users,email'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'password'        => ['required', Password::min(8)],
            'passwordConfirm' => ['required', 'same:password'],
            'role'            => ['required', 'exists:roles,name'],
            'status'          => ['required', 'in:active,inactive'],
        ], [
            'name.required'            => 'الاسم مطلوب',
            'email.required'           => 'البريد الإلكتروني مطلوب',
            'email.unique'             => 'البريد الإلكتروني مستخدم مسبقاً',
            'password.required'        => 'كلمة المرور مطلوبة',
            'passwordConfirm.same'     => 'كلمة المرور غير متطابقة',
            'role.required'            => 'الدور مطلوب',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'password' => Hash::make($data['password']),
            'status'   => $data['status'],
        ]);

        $user->assignRole($data['role']);

        $this->showModal = false;
        $this->dispatch('notify', message: 'تم إنشاء المستخدم بنجاح ✓');
    }

    // ─── Update ──────────────────────────────────────
    public function updateUser(): void
    {
        if (! $this->requirePermission('users.edit')) return;
        $rules = [
            'name'   => ['required', 'string', 'max:100'],
            'email'  => ['required', 'email', "unique:users,email,{$this->editingId}"],
            'phone'  => ['nullable', 'string', 'max:20'],
            'role'   => ['required', 'exists:roles,name'],
            'status' => ['required', 'in:active,inactive'],
        ];

        if ($this->password !== '') {
            $rules['password']        = ['required', Password::min(8)];
            $rules['passwordConfirm'] = ['required', 'same:password'];
        }

        $data = $this->validate($rules, [
            'name.required'        => 'الاسم مطلوب',
            'email.unique'         => 'البريد الإلكتروني مستخدم مسبقاً',
            'passwordConfirm.same' => 'كلمة المرور غير متطابقة',
            'role.required'        => 'الدور مطلوب',
        ]);

        $user = User::findOrFail($this->editingId);

        $updateData = [
            'name'   => $data['name'],
            'email'  => $data['email'],
            'phone'  => $data['phone'],
            'status' => $data['status'],
        ];

        if ($this->password !== '') {
            $updateData['password'] = Hash::make($this->password);
        }

        $user->update($updateData);
        $user->syncRoles([$data['role']]);

        $this->showModal = false;
        $this->dispatch('notify', message: 'تم تحديث المستخدم ✓');
    }

    // ─── Delete ──────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notify', message: 'لا يمكنك حذف حسابك الحالي');
            return;
        }
        $this->deletingId        = $id;
        $this->showDeleteConfirm = true;
    }

    public function deleteUser(): void
    {
        if (! $this->requirePermission('users.deactivate')) return;
        $user = User::findOrFail($this->deletingId);

        if ($user->id === auth()->id()) {
            $this->dispatch('notify', message: 'لا يمكنك حذف حسابك الحالي');
            return;
        }

        $user->delete();

        $this->showDeleteConfirm = false;
        $this->deletingId        = null;
        $this->dispatch('notify', message: 'تم حذف المستخدم نهائياً');
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deletingId        = null;
    }

    // ─── Toggle status ───────────────────────────────
    public function toggleStatus(int $id): void
    {
        if (! $this->requirePermission('users.deactivate')) return;
        $user = User::findOrFail($id);

        // لا يمكن إلغاء تفعيل المستخدم الحالي
        if ($user->id === auth()->id()) {
            $this->dispatch('notify', message: 'لا يمكنك تعطيل حسابك الحالي');
            return;
        }

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        $this->dispatch('notify', message: $user->fresh()->status === 'active'
            ? 'تم تفعيل المستخدم ✓'
            : 'تم تعطيل المستخدم');
    }

    // ─── Render ──────────────────────────────────────
    public function render()
    {
        $users = User::with('roles')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name',  'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->roleFilter, fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('name', $this->roleFilter)))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.users.user-index', [
            'users' => $users,
            'roles' => $this->roles,
        ]);
    }
}
