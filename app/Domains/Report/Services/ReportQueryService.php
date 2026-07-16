<?php

namespace App\Domains\Report\Services;

use App\Domains\Report\DTOs\ReportFilters;
use Illuminate\Database\Eloquent\Builder;

class ReportQueryService
{
    public function applyScheduleFilters(Builder $query, ReportFilters $filters): Builder
    {
        return $query
            ->when($filters->dateFrom, fn ($q) => $q->whereDate('due_date', '>=', $filters->dateFrom))
            ->when($filters->dateTo, fn ($q) => $q->whereDate('due_date', '<=', $filters->dateTo))
            ->when($filters->propertyId, fn ($q) => $q->whereHas('contract.unit', fn ($u) => $u->where('property_id', $filters->propertyId)))
            ->when($filters->unitId, fn ($q) => $q->whereHas('contract', fn ($c) => $c->where('unit_id', $filters->unitId)))
            ->when($filters->tenantId, fn ($q) => $q->whereHas('contract', fn ($c) => $c->where('tenant_id', $filters->tenantId)));
    }
}
