<?php

namespace App\Domains\Contract\Actions;

use App\Domains\Contract\Data\ContractData;
use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractPeriod;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Unit\Models\Unit;
use App\Enums\ContractStatus;
use App\Enums\PaymentScheduleStatus;
use App\Enums\UnitStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateContractAction
{
    public function execute(ContractData $data): Contract
    {
        return DB::transaction(function () use ($data) {
            Tenant::query()
                ->notArchived()
                ->where('status', 'active')
                ->findOrFail($data->tenantId);

            $unit = Unit::query()
                ->notArchived()
                ->whereHas('property', fn ($q) => $q->notArchived())
                ->where('status', UnitStatus::Vacant->value)
                ->with('property')
                ->findOrFail($data->unitId);

            $hasActiveContract = Contract::query()
                ->notArchived()
                ->where('unit_id', $unit->id)
                ->where('status', ContractStatus::Active->value)
                ->exists();

            if ($hasActiveContract) {
                throw ValidationException::withMessages([
                    'unit_id' => 'لا يمكن إنشاء عقد جديد على وحدة لديها عقد نشط.',
                ]);
            }

            $installmentsCount = $this->installmentsCount($data->startDate, $data->endDate, $data->billingCycle);
            $vatAmount = round($data->totalAmount * ($data->vatRate / 100), 2);
            $totalWithVat = round($data->totalAmount + $vatAmount, 2);

            $contract = Contract::query()->create([
                'tenant_id' => $data->tenantId,
                'unit_id' => $data->unitId,
                'code' => $this->generateCode(),
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'total_contract_amount' => $data->totalAmount,
                'vat_rate' => $data->vatRate,
                'vat_amount' => $vatAmount,
                'total_with_vat' => $totalWithVat,
                'deposit_amount' => $data->depositAmount,
                'currency' => 'SAR',
                'payment_cycle' => $data->billingCycle,
                'installments_count' => $installmentsCount,
                'status' => ContractStatus::Active->value,
                'notes' => $data->notes,
            ]);

            if (! empty($data->periods)) {
                $this->saveContractPeriods($contract, $data->periods);
                $this->createPaymentSchedulesFromPeriods($contract, $data->periods);
            } else {
                $this->createPaymentSchedules($contract, $installmentsCount);
            }

            $unit->update(['status' => UnitStatus::Rented->value]);

            return $contract->load(['tenant', 'unit', 'paymentSchedules']);
        });
    }

    private function installmentsCount(string $startDate, string $endDate, string $cycle): int
    {
        $start     = Carbon::parse($startDate)->startOfDay();
        $end       = Carbon::parse($endDate)->startOfDay();
        $monthStep = $this->cycleMonths($cycle);

        // Add one day to end to treat it as an exclusive boundary, then compare
        // full calendar months. This avoids the day-compression drift caused by
        // addMonthsNoOverflow when the start day exceeds the target month's length
        // (e.g. May 31 → Jun 30 → ... → May 28, which wrongly fits a 13th iteration
        // inside a May 31–May 30 contract).
        $totalMonths = (int) $start->diffInMonths($end->copy()->addDay());
        $count       = (int) floor($totalMonths / $monthStep);

        return max(1, $count);
    }

    private function createPaymentSchedules(Contract $contract, int $installmentsCount): void
    {
        $baseInstallment = round($contract->total_contract_amount / $installmentsCount, 2);
        $vatInstallment = round($contract->vat_amount / $installmentsCount, 2);

        $startDate = Carbon::parse($contract->start_date);
        $monthStep = $this->cycleMonths($contract->payment_cycle);

        for ($i = 1; $i <= $installmentsCount; $i++) {
            $baseAmount = $i === $installmentsCount
                ? round((float) $contract->total_contract_amount - ($baseInstallment * ($installmentsCount - 1)), 2)
                : $baseInstallment;
            $vatAmount = $i === $installmentsCount
                ? round((float) $contract->vat_amount - ($vatInstallment * ($installmentsCount - 1)), 2)
                : $vatInstallment;
            $totalAmount = round($baseAmount + $vatAmount, 2);

            PaymentSchedule::query()->create([
                'contract_id' => $contract->id,
                'installment_no' => $i,
                'due_date' => $startDate->copy()->addMonthsNoOverflow(($i - 1) * $monthStep)->toDateString(),
                'base_amount' => $baseAmount,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'status' => PaymentScheduleStatus::Pending->value,
            ]);
        }
    }

    private function saveContractPeriods(Contract $contract, array $periods): void
    {
        foreach ($periods as $i => $period) {
            ContractPeriod::create([
                'contract_id'         => $contract->id,
                'period_no'           => $i + 1,
                'duration_months'     => $period['duration_months'],
                'annual_amount'       => $period['annual_amount'],
                'increase_percentage' => $period['increase_percentage'],
            ]);
        }
    }

    private function createPaymentSchedulesFromPeriods(Contract $contract, array $periods): void
    {
        $installmentNo   = 1;
        $periodStartDate = Carbon::parse($contract->start_date);
        $monthStep       = $this->cycleMonths($contract->payment_cycle);
        $vatRate         = (float) $contract->vat_rate;

        foreach ($periods as $period) {
            $durationMonths    = (int) $period['duration_months'];
            $annualAmount      = (float) $period['annual_amount'];
            $periodTotal       = round($annualAmount * ($durationMonths / 12), 2);
            $periodInstallments = max(1, (int) floor($durationMonths / $monthStep));

            $baseInstallment = round($periodTotal / $periodInstallments, 2);

            for ($i = 1; $i <= $periodInstallments; $i++) {
                $baseAmount = $i === $periodInstallments
                    ? round($periodTotal - ($baseInstallment * ($periodInstallments - 1)), 2)
                    : $baseInstallment;
                $vatAmount   = round($baseAmount * ($vatRate / 100), 2);
                $totalAmount = round($baseAmount + $vatAmount, 2);

                PaymentSchedule::create([
                    'contract_id'     => $contract->id,
                    'installment_no'  => $installmentNo++,
                    'due_date'        => $periodStartDate->copy()->addMonthsNoOverflow(($i - 1) * $monthStep)->toDateString(),
                    'base_amount'     => $baseAmount,
                    'vat_amount'      => $vatAmount,
                    'total_amount'    => $totalAmount,
                    'paid_amount'     => 0,
                    'remaining_amount'=> $totalAmount,
                    'status'          => PaymentScheduleStatus::Pending->value,
                ]);
            }

            $periodStartDate->addMonthsNoOverflow($durationMonths);
        }
    }

    private function cycleMonths(string $cycle): int
    {
        return match ($cycle) {
            'monthly' => 1,
            'two_months', 'bimonthly' => 2,
            'quarterly' => 3,
            'semi_annually', 'semi_annual' => 6,
            'annually', 'annual' => 12,
            default => 1,
        };
    }

    private function generateCode(): string
    {
        return 'CON-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
    }
}
