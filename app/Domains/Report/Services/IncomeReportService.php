<?php

namespace App\Domains\Report\Services;

use App\Domains\Report\DTOs\ReportFilters;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Property\Models\Property;
use App\Enums\PaymentStatus;

class IncomeReportService
{
    public function summary(ReportFilters $filters): array
    {
        $scheduleQuery = PaymentSchedule::query();
        app(ReportQueryService::class)->applyScheduleFilters($scheduleQuery, $filters);

        $required = (clone $scheduleQuery)->sum('total_amount');
        $paid = Payment::query()
            ->where('status', 'registered')
            ->when($filters->dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $filters->dateFrom))
            ->when($filters->dateTo, fn ($q) => $q->whereDate('payment_date', '<=', $filters->dateTo))
            ->when($filters->propertyId, fn ($q) => $q->where('property_id', $filters->propertyId))
            ->when($filters->unitId, fn ($q) => $q->where('unit_id', $filters->unitId))
            ->when($filters->tenantId, fn ($q) => $q->where('tenant_id', $filters->tenantId))
            ->sum('amount');

        $remaining = max($required - $paid, 0);

        return [
            'required' => round($required, 2),
            'paid' => round($paid, 2),
            'remaining' => round($remaining, 2),
            'collection_rate' => $required > 0 ? round(($paid / $required) * 100, 2) : 0,
        ];
    }

    public function byProperty(ReportFilters $filters): array
    {
        return Property::query()
            ->when($filters->propertyId, fn ($q) => $q->where('id', $filters->propertyId))
            ->when($filters->unitId, fn ($q) => $q->whereHas('units', fn ($q) => $q->where('id', $filters->unitId)))
            ->withSum([
                'payments as paid_total' => fn ($q) => $q
                    ->where('status', PaymentStatus::Registered->value)
                    ->when($filters->dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $filters->dateFrom))
                    ->when($filters->dateTo,   fn ($q) => $q->whereDate('payment_date', '<=', $filters->dateTo))
                    ->when($filters->unitId, fn ($q) => $q->where('unit_id', $filters->unitId)),
            ], 'amount')
            ->withCount([
                'units' => fn ($q) => $q->when($filters->unitId, fn ($q) => $q->where('id', $filters->unitId)),
            ])
            ->get()
            ->map(fn ($property) => [
                'property_id'   => $property->id,
                'property_name' => $property->name,
                'units_count'   => $property->units_count,
                'paid_total'    => round((float) $property->paid_total, 2),
            ])
            ->toArray();
    }
}
