<?php

namespace App\Livewire\Units;

use Livewire\Component;
use App\Domains\Unit\Models\Unit;

class UnitShow extends Component
{
    public Unit $unit;

    public function mount(Unit $unit): void
    {
        $this->unit = $unit->load([
            'property',
            'activeContract.tenant',
            'contracts.tenant',
            'maintenanceRequests',
            'attachments',
        ]);
    }

    public function render()
    {
        return view('livewire.units.unit-show')
            ->layout('layouts.app', ['title' => $this->unit->name]);
    }
}