<?php

namespace App\Domains\Report\Services;

use App\Domains\Report\DTOs\ReportFilters;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use Carbon\Carbon;

class ArrearsReportService
{
    // ─── دفعات المستأجرين المتأخرة ──────────────────────────────────
    public function overdue(ReportFilters $filters): array
    {
        $query = PaymentSchedule::query()
            ->with(['contract.tenant', 'contract.unit.property'])
            ->whereIn('status', ['partial', 'overdue'])
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '<', now()->toDateString());

        app(ReportQueryService::class)->applyScheduleFilters($query, $filters);

        return $query->get()->map(function ($schedule) {
            $paid      = $schedule->payments()->where('status', 'registered')->sum('amount');
            $remaining = max($schedule->total_amount - $paid, 0);

            return [
                'schedule_id'  => $schedule->id,
                'tenant'       => $schedule->contract?->tenant?->name,
                'property'     => $schedule->contract?->unit?->property?->name,
                'unit'         => $schedule->contract?->unit?->name,
                'due_date'     => $schedule->due_date,
                'total_amount' => round($schedule->total_amount, 2),
                'paid'         => round($paid, 2),
                'remaining'    => round($remaining, 2),
                'days_overdue' => (int) Carbon::parse($schedule->due_date)->diffInDays(now()->startOfDay()),
                'notes'        => $schedule->notes,
            ];
        })->filter(fn ($row) => $row['remaining'] > 0)->values()->toArray();
    }

    // ─── دفعات الملاك المتأخرة ───────────────────────────────────────
    public function ownerOverdue(ReportFilters $filters): array
    {
        $query = PropertyLeaseSchedule::query()
            ->with(['lease.property'])
            ->whereIn('status', ['partial', 'overdue'])
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '<', now()->toDateString());

        // فلتر العقار إذا محدد
        if ($filters->propertyId) {
            $query->whereHas('lease', fn ($q) => $q->where('property_id', $filters->propertyId));
        }

        return $query->get()->map(function ($schedule) {
            return [
                'schedule_id'  => $schedule->id,
                'owner'        => $schedule->lease?->owner_name,
                'property'     => $schedule->lease?->property?->name,
                'due_date'     => $schedule->due_date,
                'total_amount' => round($schedule->amount, 2),
                'paid'         => round($schedule->paid_amount, 2),
                'remaining'    => round($schedule->remaining_amount, 2),
                'days_overdue' => (int) Carbon::parse($schedule->due_date)->diffInDays(now()->startOfDay()),
                'notes'        => $schedule->notes,
            ];
        })->filter(fn ($row) => $row['remaining'] > 0)->values()->toArray();
    }

    /**
     * تقادم الذمم — مفصولاً بين المدين والدائن.
     *
     * كانت الدالة تدمج الطرفين في سلال واحدة، فيظهر رقم واحد يجمع ما لنا
     * (ذمم مدينة، أصل) مع ما علينا (ذمم دائنة، التزام). المقاصة بينهما ممنوعة
     * محاسبياً، والرقم الناتج كان يوحي بعكس الحقيقة: أكثر من 97% منه دَين علينا.
     *
     * @return array{tenant: array<string,float>, owner: array<string,float>}
     */
    public function aging(ReportFilters $filters): array
    {
        return [
            'tenant' => $this->bucketByAge($this->overdue($filters)),       // مستحق لنا
            'owner'  => $this->bucketByAge($this->ownerOverdue($filters)),  // مستحق علينا
        ];
    }

    /** @param array<int,array{days_overdue:int,remaining:float}> $rows */
    private function bucketByAge(array $rows): array
    {
        $buckets = ['0_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];

        foreach ($rows as $row) {
            $days   = $row['days_overdue'];
            $amount = $row['remaining'];

            if ($days <= 30)      $buckets['0_30']    += $amount;
            elseif ($days <= 60)  $buckets['31_60']   += $amount;
            elseif ($days <= 90)  $buckets['61_90']   += $amount;
            else                  $buckets['90_plus'] += $amount;
        }

        $buckets['total'] = array_sum($buckets);

        return array_map(fn ($v) => round($v, 2), $buckets);
    }
}
