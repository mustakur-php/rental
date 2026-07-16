<?php

namespace App\Domains\Report\Services;

use App\Domains\Report\DTOs\ReportFilters;

class NetReportService
{
    public function __construct(
        private IncomeReportService   $income,
        private OutgoingReportService $outgoing,
    ) {}

    /**
     * الصافي = الوارد - الصادر
     */
    public function summary(ReportFilters $filters): array
    {
        $inc = $this->income->summary($filters);
        $out = $this->outgoing->summary($filters);

        $netRequired = round($inc['required'] - $out['required'], 2);
        $netPaid     = round($inc['paid'] - $out['paid'], 2);

        return [
            // الوارد
            'income_required'    => $inc['required'],
            'income_paid'        => $inc['paid'],
            'income_remaining'   => $inc['remaining'],
            'income_rate'        => $inc['collection_rate'],

            // الصادر
            'outgoing_required'  => $out['required'],
            'outgoing_paid'      => $out['paid'],
            'outgoing_remaining' => $out['remaining'],
            'outgoing_overdue'   => $out['overdue'],
            'outgoing_rate'      => $out['payment_rate'],

            // الصافي
            'net_required'       => $netRequired,
            'net_paid'           => $netPaid,
            'net_margin'         => $inc['required'] > 0
                                    ? round(($netRequired / $inc['required']) * 100, 2)
                                    : 0,
        ];
    }

    /**
     * الصافي حسب كل عقار
     */
    public function byProperty(ReportFilters $filters): array
    {
        $incomeByProp   = collect($this->income->byProperty($filters))->keyBy('property_id');
        $outgoingByProp = collect($this->outgoing->byProperty($filters))->keyBy('property_id');

        $allIds = $incomeByProp->keys()->merge($outgoingByProp->keys())->unique();

        return $allIds->map(function ($id) use ($incomeByProp, $outgoingByProp) {
            $inc = $incomeByProp->get($id,  ['property_name' => '—', 'paid_total' => 0]);
            $out = $outgoingByProp->get($id, ['property_name' => '—', 'owner_name' => '—', 'paid' => 0, 'required' => 0, 'remaining' => 0]);

            $incomePaid       = (float) ($inc['paid_total'] ?? 0);
            $outgoingPaid     = (float) ($out['paid'] ?? 0);
            $outgoingRequired = (float) ($out['required'] ?? 0);
            $outgoingRemaining= (float) ($out['remaining'] ?? 0);
            $net              = round($incomePaid - $outgoingPaid, 2);

            return [
                'property_id'        => $id,
                'property_name'      => $inc['property_name'] !== '—' ? $inc['property_name'] : ($out['property_name'] ?? '—'),
                'income_paid'        => $incomePaid,
                'outgoing_paid'      => $outgoingPaid,
                'outgoing_required'  => $outgoingRequired,
                'outgoing_remaining' => $outgoingRemaining,
                'net'                => $net,
                'is_leased'          => isset($out['owner_name']) && $out['owner_name'] !== '—',
                'owner_name'         => $out['owner_name'] ?? '—',
            ];
        })->values()->toArray();
    }
}
