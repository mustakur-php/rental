<?php

namespace App\Livewire\Tenants;

use App\Traits\HasPermissionGuard;
use Livewire\Component;
use Livewire\WithPagination;
use App\Domains\Tenant\Models\Tenant;

class TenantIndex extends Component
{
    use HasPermissionGuard;
    use WithPagination;

    public string $search = '';
    public string $type   = '';
    public string $status = '';

    public bool $showCreateModal = false;
    public bool $showEditModal   = false;
    public ?int $editingTenantId = null;

    public array $form = [
        'type'                    => 'individual',
        'name'                    => '',
        'mobile'                  => '',
        'email'                   => '',
        'address'                 => '',
        'national_id'             => '',
        'nationality'             => '',
        'birth_date'              => '',
        'company_name'            => '',
        'commercial_registration' => '',
        'contact_person_name'     => '',
        'contact_person_mobile'   => '',
        'notes'                   => '',
        'status'                  => 'active',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingTenantId = null;
        $this->form = [
            'type'                    => 'individual',
            'name'                    => '',
            'mobile'                  => '',
            'email'                   => '',
            'address'                 => '',
            'national_id'             => '',
            'nationality'             => '',
            'birth_date'              => '',
            'company_name'            => '',
            'commercial_registration' => '',
            'contact_person_name'     => '',
            'contact_person_mobile'   => '',
            'notes'                   => '',
            'status'                  => 'active',
        ];
        $this->showCreateModal = true;
    }

    public function createTenant(): void
    {
        if (! $this->requirePermission('tenants.create')) return;
        $data = $this->validate($this->rules())['form'];
        $data = $this->normalizeTenantData($data);
        $data['code'] = $this->nextCode();

        Tenant::create($data);

        $this->showCreateModal = false;
        $this->dispatch('notify', message: 'تم إضافة المستأجر بنجاح');
    }

    public function openEditModal(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);

        $this->resetValidation();
        $this->editingTenantId = $tenant->id;
        $this->form = $tenant->only([
            'type', 'name', 'mobile', 'email', 'address',
            'national_id', 'nationality',
            'company_name', 'commercial_registration',
            'contact_person_name', 'contact_person_mobile',
            'notes', 'status',
        ]);
        $this->form['birth_date'] = $tenant->birth_date?->format('Y-m-d') ?? '';
        $this->showEditModal = true;
    }

    public function updateTenant(): void
    {
        if (! $this->requirePermission('tenants.edit')) return;
        $tenant = Tenant::findOrFail($this->editingTenantId);
        $data   = $this->validate($this->rules($tenant->id))['form'];
        $data = $this->normalizeTenantData($data);

        $tenant->update($data);

        $this->showEditModal = false;
        $this->dispatch('notify', message: 'تم تحديث بيانات المستأجر بنجاح');
    }

    public function archiveTenant(int $tenantId): void
    {
        if (! $this->requirePermission('tenants.archive')) return;
        Tenant::findOrFail($tenantId)->update([
            'archived_at' => now(),
            'archived_reason' => 'archived_from_tenants',
        ]);

        $this->dispatch('notify', message: 'تم نقل المستأجر إلى الأرشيف');
    }

    protected function rules(?int $ignoreId = null): array
    {
        $uniqueNid = 'unique:tenants,national_id' . ($ignoreId ? ',' . $ignoreId : '');
        $uniqueCr  = 'unique:tenants,commercial_registration' . ($ignoreId ? ',' . $ignoreId : '');

        return [
            'form.type'                    => ['required', 'in:individual,company'],
            'form.name'                    => ['required', 'string', 'max:255'],
            'form.mobile'                  => ['nullable', 'string', 'max:50'],
            'form.email'                   => ['nullable', 'email', 'max:255'],
            'form.address'                 => ['nullable', 'string', 'max:255'],
            'form.status'                  => ['required', 'in:active,inactive,suspended'],
            'form.notes'                   => ['nullable', 'string'],
            'form.national_id'             => ['nullable', 'string', 'max:80', $uniqueNid],
            'form.nationality'             => ['nullable', 'string', 'max:80'],
            'form.birth_date'              => ['nullable', 'date'],
            'form.company_name'            => ['nullable', 'string', 'max:255'],
            'form.commercial_registration' => ['nullable', 'string', 'max:120', $uniqueCr],
            'form.contact_person_name'     => ['nullable', 'string', 'max:255'],
            'form.contact_person_mobile'   => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function normalizeTenantData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $data[$key] = $value === '' ? null : $value;
            }
        }

        if (($data['type'] ?? 'individual') === 'individual') {
            $data['company_name'] = null;
            $data['commercial_registration'] = null;
            $data['contact_person_name'] = null;
            $data['contact_person_mobile'] = null;
        } else {
            $data['national_id'] = null;
            $data['nationality'] = null;
            $data['birth_date'] = null;
        }

        return $data;
    }

    protected function nextCode(): string
    {
        $next = (Tenant::max('id') ?? 0) + 1;
        return 'TEN-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $tenants = Tenant::query()
            ->notArchived()
            ->withCount(['contracts', 'activeContracts'])
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name',        'like', '%' . $this->search . '%')
                      ->orWhere('mobile',     'like', '%' . $this->search . '%')
                      ->orWhere('national_id','like', '%' . $this->search . '%')
                      ->orWhere('code',       'like', '%' . $this->search . '%');
                });
            })
            ->when($this->type,   fn ($q) => $q->where('type',   $this->type))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(15);

        return view('livewire.tenants.tenant-index', compact('tenants'))
            ->layout('layouts.app', ['title' => 'المستأجرون']);
    }
}
