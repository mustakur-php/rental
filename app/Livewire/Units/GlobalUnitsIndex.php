<?php

namespace App\Livewire\Units;

use App\Traits\HasPermissionGuard;
use Livewire\Component;
use Livewire\WithPagination;
use App\Domains\Unit\Models\Unit;
use App\Domains\Property\Models\Property;

class GlobalUnitsIndex extends Component
{
    use WithPagination, HasPermissionGuard;

    public string $viewMode = 'cards';
    public string $search = '';
    public string $status = '';
    public string $propertyId = '';

    public function archiveUnit(int $unitId): void
    {
        if (! $this->requirePermission('units.archive')) return;
        $unit = Unit::with('activeContract')->findOrFail($unitId);

        if ($unit->activeContract) {
            $this->dispatch('notify', message: 'لا يمكن أرشفة وحدة مؤجرة — أنهِ العقد أولاً');
            return;
        }

        $unit->update([
            'archived_at'     => now(),
            'archived_reason' => 'archived_from_units',
        ]);

        $this->dispatch('notify', message: 'تم نقل الوحدة إلى الأرشيف');
    }

    public function render()
    {
        $units = Unit::query()
            ->notArchived()
            ->with(['property', 'activeContract.tenant'])
            ->whereHas('property', fn ($query) => $query->notArchived())
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->propertyId, fn ($q) => $q->where('property_id', $this->propertyId))
            ->latest()
            ->paginate(18);

        $properties = Property::notArchived()->orderBy('name')->get(['id', 'name']);

        return view('livewire.units.global-units-index', compact('units', 'properties'))
            ->layout('layouts.app', ['title' => 'كل الوحدات']);
    }
}
