<?php

namespace App\Domains\Report\Services;

use App\Domains\Property\Models\Property;
use App\Domains\Report\DTOs\ReportFilters;
use App\Domains\Unit\Models\Unit;

class OccupancyReportService
{
    public function summary(?ReportFilters $filters = null): array
    {
        $query = Unit::query()
            ->notArchived()
            ->whereHas('property', fn ($q) => $q->notArchived())
            ->when($filters?->propertyId, fn ($q) => $q->where('property_id', $filters->propertyId))
            ->when($filters?->unitId, fn ($q) => $q->where('id', $filters->unitId));

        $total = (clone $query)->count();
        $rented = (clone $query)->where('status', 'rented')->count();
        $vacant = (clone $query)->where('status', 'vacant')->count();

        return [
            'total_units' => $total,
            'rented_units' => $rented,
            'vacant_units' => $vacant,
            'occupancy_rate' => $total > 0 ? round(($rented / $total) * 100, 2) : 0,
        ];
    }

    public function byProperty(): array
    {
        return Property::query()
            ->notArchived()
            ->withCount([
                'units as total_units' => fn ($q) => $q->notArchived(),
                'units as rented_units' => fn ($q) => $q->notArchived()->where('status', 'rented'),
                'units as vacant_units' => fn ($q) => $q->notArchived()->where('status', 'vacant'),
            ])
            ->get()
            ->map(fn ($property) => [
                'property' => $property->name,
                'total_units' => $property->total_units,
                'rented_units' => $property->rented_units,
                'vacant_units' => $property->vacant_units,
                'occupancy_rate' => $property->total_units > 0
                    ? round(($property->rented_units / $property->total_units) * 100, 2)
                    : 0,
            ])
            ->toArray();
    }
}
