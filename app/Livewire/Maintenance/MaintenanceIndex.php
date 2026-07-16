<?php

namespace App\Livewire\Maintenance;

use App\Traits\HasPermissionGuard;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use App\Domains\Maintenance\Models\MaintenanceRequest;
use App\Domains\Property\Models\Property;
use App\Domains\Unit\Models\Unit;
use App\Enums\MaintenanceStatus;

class MaintenanceIndex extends Component
{
    use WithPagination, HasPermissionGuard;

    public string $search   = '';
    public string $status   = '';
    public string $priority = '';

    public bool $showCreateModal = false;
    public bool $showEditModal   = false;
    public ?int $editingId       = null;

    public array $form = [
        'property_id'   => null,
        'unit_id'       => null,
        'type'          => 'corrective',
        'title'         => '',
        'description'   => '',
        'priority'      => 'medium',
        'status'        => 'new',
        'request_date'  => '',
        'completed_date'=> '',
        'cost'          => '',
        'unit_impact'   => 'none',
    ];

    public function mount(): void
    {
        $this->form['request_date'] = now()->toDateString();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function updatedFormPropertyId(): void
    {
        $this->form['unit_id'] = null;
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = [
            'property_id'    => null,
            'unit_id'        => null,
            'type'           => 'corrective',
            'title'          => '',
            'description'    => '',
            'priority'       => 'medium',
            'status'         => 'new',
            'request_date'   => now()->toDateString(),
            'completed_date' => '',
            'cost'           => '',
            'unit_impact'    => 'none',
        ];
        $this->showCreateModal = true;
    }

    public function createRequest(): void
    {
        if (! $this->requirePermission('maintenance.create')) return;
        $data = $this->validate($this->rules())['form'];
        $data['code'] = $this->nextCode();
        $data['unit_id'] = $data['unit_id'] ?: null;
        $data['cost'] = $data['cost'] !== '' && $data['cost'] !== null ? $data['cost'] : 0;
        $data['completed_date'] = $data['completed_date'] ?: null;

        MaintenanceRequest::create($data);

        $this->showCreateModal = false;
        $this->dispatch('notify', message: 'تم إضافة طلب الصيانة بنجاح');
    }

    public function openEditModal(int $id): void
    {
        $request = MaintenanceRequest::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $request->id;
        $this->form = [
            'property_id'    => $request->property_id,
            'unit_id'        => $request->unit_id,
            'type'           => $request->type,
            'title'          => $request->title,
            'description'    => $request->description ?? '',
            'priority'       => $request->priority,
            'status'         => is_object($request->status) ? $request->status->value : $request->status,
            'request_date'   => $request->request_date?->format('Y-m-d') ?? '',
            'completed_date' => $request->completed_date?->format('Y-m-d') ?? '',
            'cost'           => $request->cost ?? '',
            'unit_impact'    => $request->unit_impact ?? 'none',
        ];
        $this->showEditModal = true;
    }

    public function updateRequest(): void
    {
        if (! $this->requirePermission('maintenance.edit')) return;
        $request = MaintenanceRequest::findOrFail($this->editingId);
        $data = $this->validate($this->rules())['form'];
        $data['unit_id'] = $data['unit_id'] ?: null;
        $data['cost'] = $data['cost'] !== '' && $data['cost'] !== null ? $data['cost'] : 0;
        $data['completed_date'] = $data['completed_date'] ?: null;

        $request->update($data);

        $this->showEditModal = false;
        $this->dispatch('notify', message: 'تم تحديث طلب الصيانة');
    }

    protected function rules(): array
    {
        return [
            'form.property_id'    => ['required', 'exists:properties,id'],
            'form.unit_id'        => ['nullable', 'exists:units,id'],
            'form.type'           => ['required', 'in:corrective,preventive,emergency'],
            'form.title'          => ['required', 'string', 'max:255'],
            'form.description'    => ['nullable', 'string'],
            'form.priority'       => ['required', 'in:low,medium,high,urgent'],
            'form.status'         => ['required', 'in:new,in_progress,completed,cancelled'],
            'form.request_date'   => ['required', 'date'],
            'form.completed_date' => ['nullable', 'date'],
            'form.cost'           => ['nullable', 'numeric', 'min:0'],
            'form.unit_impact'    => ['required', 'in:none,maintenance,unavailable'],
        ];
    }

    protected function nextCode(): string
    {
        $next = (MaintenanceRequest::max('id') ?? 0) + 1;
        return 'MNT-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    #[Computed]
    public function properties()
    {
        return Property::notArchived()->orderBy('name')->get();
    }

    #[Computed]
    public function units()
    {
        if (! $this->form['property_id']) return collect();
        return Unit::notArchived()
            ->where('property_id', $this->form['property_id'])
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        $requests = MaintenanceRequest::query()
            ->with(['property', 'unit'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('code', 'like', '%'.$this->search.'%'))
            ->when($this->status,   fn ($q) => $q->where('status',   $this->status))
            ->when($this->priority, fn ($q) => $q->where('priority', $this->priority))
            ->latest()
            ->paginate(15);

        return view('livewire.maintenance.maintenance-index', compact('requests'))
            ->layout('layouts.app', ['title' => 'الصيانة']);
    }
}
