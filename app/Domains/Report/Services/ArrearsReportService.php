<?php

namespace App\Domains\Report\Services;

use App\Domains\Report\DTOs\ReportFilters;
use App\Domains\Payment\Models\PaymentSchedule;
use Carbon\Carbon;

class ArrearsReportService
{
    public function overdue(ReportFilters $filters): array
    {
        $query = PaymentSchedule::query()
            ->with(['contract.tenant', 'contract.unit.property'])
            ->whereIn('status', ['partial', 'overdue'])
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '<', now()->toDateString());

        app(ReportQueryService::class)->applyScheduleFilters($query, $filters);

        return $query->get()->map(function ($schedule) {
            $paid = $schedule->payments()->where('status', 'registered')->sum('amount');
            $remaining = max($schedule->total_amount - $paid, 0);

            return [
                'tenant' => $schedule->contract?->tenant?->name,
                'property' => $schedule->contract?->unit?->property?->name,
                'unit' => $schedule->contract?->unit?->name,
                'due_date' => $schedule->due_date,
                'total_amount' => round($schedule->total_amount, 2),
                'paid' => round($paid, 2),
                'remaining' => round($remaining, 2),
                'days_overdue' => Carbon::parse($schedule->due_date)->diffInDays(now()),
            ];
        })->filter(fn ($row) => $row['remaining'] > 0)->values()->toArray();
    }

    public function aging(ReportFilters $filters): array
    {
        $rows = $this->overdue($filters);

        $buckets = [
            '0_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '90_plus' => 0,
        ];

        foreach ($rows as $row) {
            $days = $row['days_overdue'];
            $amount = $row['remaining'];

            if ($days <= 30) {
                $buckets['0_30'] += $amount;
            } elseif ($days <= 60) {
                $buckets['31_60'] += $amount;
            } elseif ($days <= 90) {
                $buckets['61_90'] += $amount;
            } else {
                $buckets['90_plus'] += $amount;
            }
        }

        return array_map(fn ($v) => round($v, 2), $buckets);
    }
}
