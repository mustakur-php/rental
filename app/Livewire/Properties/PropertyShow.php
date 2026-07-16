<?php

namespace App\Livewire\Properties;

use App\Enums\UnitStatus;
use App\Traits\HasPermissionGuard;
use App\Domains\Property\Models\Property;
use App\Domains\Unit\Models\Unit;
use Livewire\Component;

class PropertyShow extends Component
{
    use HasPermissionGuard;
    public Property $property;

    public string $viewMode = 'cards';
    public string $search = '';
    public string $status = '';

    public bool $showCreateUnitModal = false;
    public bool $showEditUnitModal = false;
    public ?int $editingUnitId = null;

    public array $unitForm = [
        'property_id'      => null,
        'code'             => '',
        'name'             => '',
        'type'             => 'shop',
        'internal_number'  => '',
        'area'             => null,
        'floor'            => '',
        'electricity_meter'=> '',
        'water_meter'      => '',
        'description'      => '',
        'status'           => 'vacant',
    ];

    public function mount(Property $property): void
    {
        $this->property = $property;
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['cards', 'table', 'map'], true)) {
            return;
        }
        $this->viewMode = $mode;
    }

    public function openCreateUnitModal(): void
    {
        $this->resetValidation();
        $this->editingUnitId = null;
        $this->unitForm = [
            'property_id'      => $this->property->id,
            'code'             => $this->nextUnitCode(),
            'name'             => '',
            'type'             => 'shop',
            'internal_number'  => '',
            'area'             => null,
            'floor'            => '',
            'electricity_meter'=> '',
            'water_meter'      => '',
            'description'      => '',
            'status'           => UnitStatus::Vacant->value,
        ];

        $this->showCreateUnitModal = true;
    }

    public function createUnit(): void
    {
        if (! $this->requirePermission('units.create')) return;
        $data = $this->validate($this->unitRules())['unitForm'];

        Unit::create($data);

        $this->showCreateUnitModal = false;
        $this->dispatch('notify', message: 'تم إنشاء الوحدة بنجاح');
    }

    public function openEditUnitModal(int $unitId): void
    {
        $unit = Unit::where('property_id', $this->property->id)->findOrFail($unitId);

        $this->resetValidation();
        $this->editingUnitId = $unit->id;

        $this->unitForm = [
            'property_id'       => $unit->property_id,
            'code'              => $unit->code,
            'name'              => $unit->name,
            'type'              => $unit->type,
            'internal_number'   => $unit->internal_number,
            'area'              => $unit->area,
            'floor'             => $unit->floor,
            'electricity_meter' => $unit->electricity_meter,
            'water_meter'       => $unit->water_meter,
            'description'       => $unit->description,
            'status'            => $unit->status?->value ?? $unit->status,
        ];

        $this->showEditUnitModal = true;
    }

    public function closeUnitModal(): void
    {
        $this->showCreateUnitModal = false;
        $this->showEditUnitModal = false;
        $this->editingUnitId = null;

        $this->resetValidation();
    }

    public function updateUnit(): void
    {
        if (! $this->requirePermission('units.edit')) return;
        $unit = Unit::where('property_id', $this->property->id)->findOrFail($this->editingUnitId);

        $data = $this->validate($this->unitRules($unit->id))['unitForm'];

        $unit->update($data);

        $this->showEditUnitModal = false;
        $this->dispatch('notify', message: 'تم تحديث الوحدة بنجاح');
    }

    protected function unitRules(?int $ignoreId = null): array
    {
        $uniqueCode = 'unique:units,code';
        if ($ignoreId) {
            $uniqueCode .= ',' . $ignoreId;
        }

        return [
            'unitForm.property_id'      => ['required', 'exists:properties,id'],
            'unitForm.code'             => ['required', 'string', 'max:80', $uniqueCode],
            'unitForm.name'             => ['required', 'string', 'max:255'],
            'unitForm.type'             => ['required', 'string', 'max:50'],
            'unitForm.internal_number'  => ['nullable', 'string', 'max:80'],
            'unitForm.area'             => ['nullable', 'numeric', 'min:0'],
            'unitForm.floor'            => ['nullable', 'string', 'max:80'],
            'unitForm.electricity_meter'=> ['nullable', 'string', 'max:120'],
            'unitForm.water_meter'      => ['nullable', 'string', 'max:120'],
            'unitForm.description'      => ['nullable', 'string'],
            'unitForm.status'           => ['required', 'string', 'max:30'],
        ];
    }

    protected function nextUnitCode(): string
    {
        $next = (Unit::where('property_id', $this->property->id)->max('id') ?? 0) + 1;
        return 'UNIT-' . $this->property->id . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $this->property->loadCount([
            'units as units_count' => fn ($q) => $q->notArchived(),
            'units as rented_units_count'      => fn ($q) => $q->notArchived()->where('status', 'rented'),
            'units as vacant_units_count'       => fn ($q) => $q->notArchived()->where('status', 'vacant'),
            'units as maintenance_units_count'  => fn ($q) => $q->notArchived()->where('status', 'maintenance'),
        ]);

        // جلب الوحدات مباشرة بدل computed property
        $units = Unit::query()
            ->notArchived()
            ->where('property_id', $this->property->id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name',             'like', '%' . $this->search . '%')
                      ->orWhere('code',            'like', '%' . $this->search . '%')
                      ->orWhere('internal_number', 'like', '%' . $this->search . '%')
                      ->orWhere('electricity_meter','like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->get();

        return view('livewire.properties.property-show', compact('units'))
            ->layout('layouts.app', ['title' => $this->property->name]);
    }
}
