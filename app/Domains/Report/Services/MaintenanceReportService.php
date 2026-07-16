<?php

namespace App\Domains\Report\Services;

use App\Domains\Maintenance\Models\MaintenanceRequest;
use App\Domains\Report\DTOs\ReportFilters;

class MaintenanceReportService
{
    public function summary(ReportFilters $filters): array
    {
        $query = MaintenanceRequest::query()
            ->when($filters->dateFrom, fn ($q) => $q->whereDate('request_date', '>=', $filters->dateFrom))
            ->when($filters->dateTo, fn ($q) => $q->whereDate('request_date', '<=', $filters->dateTo))
            ->when($filters->propertyId, fn ($q) => $q->where('property_id', $filters->propertyId))
            ->when($filters->unitId, fn ($q) => $q->where('unit_id', $filters->unitId));

        return [
            'total_requests' => (clone $query)->count(),
            'open_requests' => (clone $query)->whereIn('status', ['new', 'in_progress'])->count(),
            'completed_requests' => (clone $query)->where('status', 'completed')->count(),
            'total_cost' => round((clone $query)->sum('cost'), 2),
        ];
    }
}
