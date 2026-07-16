<?php

namespace App\Livewire\Companies;

use App\Domains\Company\Models\Company;
use App\Traits\HasPermissionGuard;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyIndex extends Component
{
    use WithPagination, HasPermissionGuard;

    public string $search = '';
    public string $typeFilter = '';

    public bool $showCreateModal    = false;
    public bool $showEditModal      = false;
    public bool $showArchiveModal   = false;

    public ?int $editingCompanyId  = null;
    public ?int $archivingCompanyId = null;
    public string $archiveReason   = '';

    public array $form = [
        'parent_id'               => null,
        'type'                    => 'main',
        'code'                    => '',
        'name'                    => '',
        'commercial_registration' => '',
        'phone'                   => '',
        'email'                   => '',
        'address'                 => '',
        'iban'                    => '',
        'bank_name'               => '',
        'status'                  => 'active',
        'notes'                   => '',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // ─── إنشاء ────────────────────────────────────────

    public function openCreateModal(?int $parentId = null): void
    {
        $this->resetValidation();
        $this->editingCompanyId = null;
        $this->form = [
            'parent_id'               => $parentId,
            'type'                    => $parentId ? 'subsidiary' : 'main',
            'code'                    => $this->nextCode(),
            'name'                    => '',
            'commercial_registration' => '',
            'phone'                   => '',
            'email'                   => '',
            'address'                 => '',
            'iban'                    => '',
            'bank_name'               => '',
            'status'                  => 'active',
            'notes'                   => '',
        ];
        $this->showCreateModal = true;
    }

    public function createCompany(): void
    {
        if (! $this->requirePermission('companies.create')) return;
        $data = $this->validate($this->rules())['form'];
        $data = $this->normalizeData($data);

        Company::create($data);

        $this->showCreateModal = false;
        $this->dispatch('notify', message: 'تم إنشاء الشركة بنجاح');
    }

    // ─── تعديل ────────────────────────────────────────

    public function openEditModal(int $companyId): void
    {
        $company = Company::findOrFail($companyId);

        $this->resetValidation();
        $this->editingCompanyId = $company->id;

        $this->form = [
            'parent_id'               => $company->parent_id,
            'type'                    => $company->type,
            'code'                    => $company->code,
            'name'                    => $company->name,
            'commercial_registration' => $company->commercial_registration ?? '',
            'phone'                   => $company->phone ?? '',
            'email'                   => $company->email ?? '',
            'address'                 => $company->address ?? '',
            'iban'                    => $company->iban ?? '',
            'bank_name'               => $company->bank_name ?? '',
            'status'                  => $company->status,
            'notes'                   => $company->notes ?? '',
        ];

        $this->showEditModal = true;
    }

    public function updateCompany(): void
    {
        if (! $this->requirePermission('companies.edit')) return;
        $company = Company::findOrFail($this->editingCompanyId);
        $data    = $this->validate($this->rules($company->id))['form'];
        $data    = $this->normalizeData($data);

        $company->update($data);

        $this->showEditModal = false;
        $this->dispatch('notify', message: 'تم تحديث بيانات الشركة بنجاح');
    }

    // ─── أرشفة ────────────────────────────────────────

    public function openArchiveModal(int $companyId): void
    {
        $this->archivingCompanyId = $companyId;
        $this->archiveReason      = '';
        $this->showArchiveModal   = true;
    }

    public function archiveCompany(): void
    {
        if (! $this->requirePermission('companies.archive')) return;
        $this->validate([
            'archiveReason' => ['required', 'string', 'max:100'],
        ], [
            'archiveReason.required' => 'يرجى كتابة سبب الأرشفة',
        ]);

        $company = Company::with('subsidiaries', 'properties')->findOrFail($this->archivingCompanyId);

        // أرشفة الشركات الفرعية
        $company->subsidiaries->each(function (Company $sub) {
            $sub->update([
                'archived_at'     => now(),
                'archived_reason' => 'parent_company_archived',
            ]);
        });

        // أرشفة العقارات التابعة
        $company->properties()->update([
            'archived_at'     => now(),
            'archived_reason' => 'company_archived',
        ]);

        $company->update([
            'archived_at'     => now(),
            'archived_reason' => $this->archiveReason,
        ]);

        $this->showArchiveModal = false;
        $this->dispatch('notify', message: 'تم نقل الشركة إلى الأرشيف');
    }

    // ─── مساعدات ──────────────────────────────────────

    protected function rules(?int $ignoreId = null): array
    {
        $uniqueCode = 'unique:companies,code' . ($ignoreId ? ',' . $ignoreId : '');

        return [
            'form.parent_id'               => ['nullable', 'integer', 'exists:companies,id'],
            'form.type'                    => ['required', 'in:main,subsidiary'],
            'form.code'                    => ['required', 'string', 'max:50', $uniqueCode],
            'form.name'                    => ['required', 'string', 'max:255'],
            'form.commercial_registration' => ['nullable', 'string', 'max:100'],
            'form.phone'                   => ['nullable', 'string', 'max:50'],
            'form.email'                   => ['nullable', 'email', 'max:255'],
            'form.address'                 => ['nullable', 'string', 'max:500'],
            'form.iban'                    => ['nullable', 'string', 'max:50'],
            'form.bank_name'               => ['nullable', 'string', 'max:150'],
            'form.status'                  => ['required', 'in:active,inactive'],
            'form.notes'                   => ['nullable', 'string'],
        ];
    }

    protected function normalizeData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && trim($value) === '') {
                $data[$key] = null;
            }
        }
        return $data;
    }

    protected function nextCode(): string
    {
        $next = (Company::max('id') ?? 0) + 1;
        return 'COMP-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    // ─── Render ───────────────────────────────────────

    public function render()
    {
        $companies = Company::query()
            ->notArchived()
            ->roots()
            ->with([
                'subsidiaries' => fn ($q) => $q->notArchived()->withCount([
                    'properties as properties_count' => fn ($q) => $q->notArchived(),
                ]),
            ])
            ->withCount([
                'properties as properties_count'    => fn ($q) => $q->notArchived(),
                'subsidiaries as subsidiaries_count' => fn ($q) => $q->notArchived(),
            ])
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%')
                      ->orWhere('commercial_registration', 'like', '%' . $this->search . '%')
                      ->orWhereHas('subsidiaries', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->latest()
            ->paginate(10);

        $mainCompanies = Company::notArchived()->roots()->get(['id', 'name', 'code']);

        return view('livewire.companies.company-index', compact('companies', 'mainCompanies'))
            ->layout('layouts.app', ['title' => 'الشركات']);
    }
}
