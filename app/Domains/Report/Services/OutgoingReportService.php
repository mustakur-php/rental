<?php

namespace App\Domains\Report\Services;

use App\Domains\Property\Models\PropertyLease;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use App\Domains\Report\DTOs\ReportFilters;

class OutgoingReportService
{
    /**
     * ملخص الدفعات الصادرة للملاك
     */
    public function summary(ReportFilters $filters): array
    {
        $query = PropertyLeaseSchedule::query()
            ->when($filters->dateFrom, fn ($q) => $q->whereDate('due_date', '>=', $filters->dateFrom))
            ->when($filters->dateTo,   fn ($q) => $q->whereDate('due_date', '<=', $filters->dateTo))
            ->when($filters->propertyId, fn ($q) => $q->whereHas('lease', fn ($q) => $q->where('property_id', $filters->propertyId)))
            ->when($filters->unitId, fn ($q) => $q->whereRaw('1 = 0'));

        $required    = (clone $query)->sum('amount');
        $paid        = (clone $query)->sum('paid_amount');
        $remaining   = (clone $query)->sum('remaining_amount');
        $overdue     = (clone $query)->where('status', 'overdue')->sum('remaining_amount');

        return [
            'required'    => round($required, 2),
            'paid'        => round($paid, 2),
            'remaining'   => round($remaining, 2),
            'overdue'     => round($overdue, 2),
            'payment_rate'=> $required > 0 ? round(($paid / $required) * 100, 2) : 0,
        ];
    }

    /**
     * تفصيل الصادر حسب كل عقار مستأجر
     */
    public function byProperty(ReportFilters $filters): array
    {
        return PropertyLease::query()
            ->with('property')
            ->where('status', 'active')
            ->when($filters->propertyId, fn ($q) => $q->where('property_id', $filters->propertyId))
            ->when($filters->unitId, fn ($q) => $q->whereRaw('1 = 0'))
            ->get()
            ->map(function ($lease) use ($filters) {
                $schedules = $lease->schedules()
                    ->when($filters->dateFrom, fn ($q) => $q->whereDate('due_date', '>=', $filters->dateFrom))
                    ->when($filters->dateTo,   fn ($q) => $q->whereDate('due_date', '<=', $filters->dateTo))
                    ->get();

                return [
                    'property_id'   => $lease->property_id,
                    'property_name' => $lease->property?->name ?? '—',
                    'owner_name'    => $lease->owner_name,
                    'required'      => round($schedules->sum('amount'), 2),
                    'paid'          => round($schedules->sum('paid_amount'), 2),
                    'remaining'     => round($schedules->sum('remaining_amount'), 2),
                    'end_date'      => $lease->end_date->format('Y/m/d'),
                ];
            })
            ->toArray();
    }
}
