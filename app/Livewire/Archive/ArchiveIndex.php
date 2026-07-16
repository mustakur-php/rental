<?php

namespace App\Livewire\Archive;

use App\Domains\Contract\Models\Contract;
use App\Domains\Property\Models\Property;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Unit\Models\Unit;
use App\Traits\HasPermissionGuard;
use Livewire\Component;

class ArchiveIndex extends Component
{
    use HasPermissionGuard;

    public string $tab = 'properties';

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['properties', 'units', 'tenants', 'contracts'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    public function restore(string $type, int $id): void
    {
        // Each type requires its own permission
        $permissionMap = [
            'property' => 'properties.archive',
            'unit'     => 'units.archive',
            'tenant'   => 'tenants.archive',
            'contract' => 'contracts.terminate',
        ];

        $permission = $permissionMap[$type] ?? null;
        if (! $permission || ! $this->requirePermission($permission)) return;

        match ($type) {
            'property' => $this->restoreProperty($id),
            'unit'     => Unit::archived()->findOrFail($id)->update($this->restoreData()),
            'tenant'   => Tenant::archived()->findOrFail($id)->update($this->restoreData()),
            'contract' => Contract::archived()->findOrFail($id)->update($this->restoreData()),
            default    => null,
        };

        $this->dispatch('notify', message: 'تمت إعادة التفعيل بنجاح');
    }

    protected function restoreProperty(int $id): void
    {
        $property = Property::archived()->findOrFail($id);
        $property->update($this->restoreData());

        $property->units()->whereNotNull('archived_at')->update($this->restoreData());
        $property->leases()->whereNotNull('archived_at')->update($this->restoreData());
    }

    protected function restoreData(): array
    {
        return [
            'archived_at' => null,
            'archived_reason' => null,
            'archived_notes' => null,
        ];
    }

    public function render()
    {
        return view('livewire.archive.archive-index', [
            'properties' => Property::archived()->latest('archived_at')->get(),
            'units' => Unit::archived()->with('property')->latest('archived_at')->get(),
            'tenants' => Tenant::archived()->withCount('contracts')->latest('archived_at')->get(),
            'contracts' => Contract::archived()->with(['tenant', 'unit.property'])->latest('archived_at')->get(),
        ])->layout('layouts.app', ['title' => 'الأرشيف']);
    }
}
