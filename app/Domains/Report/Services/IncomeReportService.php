<?php

namespace App\Domains\Report\Services;

use App\Domains\Report\DTOs\ReportFilters;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Property\Models\Property;
use App\Enums\PaymentStatus;

class IncomeReportService
{
    public function summary(ReportFilters $filters): array
    {
        // نستخدم PaymentSchedule كأساس لكلا القيمتين لتوحيد الدلالة:
        // required = مجموع الأقساط المطلوبة (due_date ضمن الفترة)
        // paid     = مجموع paid_amount على نفس الأقساط (المدفوع فعلاً منها)
        // هذا يمنع حالة تجاوز نسبة التحصيل 100% عند فلترة بتاريخ
        $scheduleQuery = PaymentSchedule::query();
        app(ReportQueryService::class)->applyScheduleFilters($scheduleQuery, $filters);

        $required  = (clone $scheduleQuery)->sum('total_amount');
        $paid      = (clone $scheduleQuery)->sum('paid_amount');
        $remaining = max($required - $paid, 0);

        return [
            'required'        => round($required, 2),
            'paid'            => round($paid, 2),
            'remaining'       => round($remaining, 2),
            'collection_rate' => $required > 0 ? round(($paid / $required) * 100, 2) : 0,
        ];
    }

    public function byProperty(ReportFilters $filters): array
    {
        $accrual = $this->accrualByProperty($filters);

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
            ->map(function ($property) use ($accrual) {
                $row = $accrual->get($property->id);

                return [
                    'property_id'   => $property->id,
                    'property_name' => $property->name,
                    'units_count'   => $property->units_count,

                    // أساس نقدي: ما دخل فعلاً في الفترة (payments.payment_date)
                    'paid_total'    => round((float) $property->paid_total, 2),

                    // أساس استحقاق: ما استُحق في الفترة وكم حُصِّل منه (due_date).
                    // الصافي يُحسب من هذه لا من paid_total، لأن جانب الملاك لا
                    // يملك سجل دفعات بتواريخ فلا يمكن قياسه نقدياً أصلاً.
                    'required'      => round((float) ($row->required ?? 0), 2),
                    'collected'     => round((float) ($row->collected ?? 0), 2),
                ];
            })
            ->toArray();
    }

    /**
     * مجاميع الاستحقاق لكل العقارات في استعلام واحد مجمَّع.
     *
     * مفصولة عن paid_total عمداً: ذاك يقيس النقد الوارد في الفترة، وهذه تقيس
     * ما استُحق فيها. الرقمان مشروعان لكنهما ليسا الشيء نفسه، ولا يصح طرح
     * أحدهما من رقم مقيس بالأساس الآخر.
     */
    private function accrualByProperty(ReportFilters $filters)
    {
        return PaymentSchedule::query()
            ->join('contracts', 'contracts.id', '=', 'payment_schedules.contract_id')
            ->join('units', 'units.id', '=', 'contracts.unit_id')
            ->when($filters->dateFrom, fn ($q) => $q->whereDate('payment_schedules.due_date', '>=', $filters->dateFrom))
            ->when($filters->dateTo,   fn ($q) => $q->whereDate('payment_schedules.due_date', '<=', $filters->dateTo))
            ->when($filters->propertyId, fn ($q) => $q->where('units.property_id', $filters->propertyId))
            ->when($filters->unitId,     fn ($q) => $q->where('contracts.unit_id', $filters->unitId))
            ->when($filters->tenantId,   fn ($q) => $q->where('contracts.tenant_id', $filters->tenantId))
            ->groupBy('units.property_id')
            ->selectRaw('units.property_id as property_id')
            ->selectRaw('SUM(payment_schedules.total_amount) as required')
            ->selectRaw('SUM(payment_schedules.paid_amount) as collected')
            ->get()
            ->keyBy('property_id');
    }
}
