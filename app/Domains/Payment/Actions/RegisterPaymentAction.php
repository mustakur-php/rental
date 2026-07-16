<?php

namespace App\Domains\Payment\Actions;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Enums\PaymentScheduleStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterPaymentAction
{
    public function execute(array $data): Payment
    {
        $payment = DB::transaction(function () use ($data) {
            $schedule = PaymentSchedule::query()
                ->with(['contract.unit.property', 'contract.tenant'])
                ->lockForUpdate()
                ->findOrFail($data['payment_schedule_id']);

            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'المبلغ يجب أن يكون أكبر من صفر.',
                ]);
            }

            if ($amount > (float) $schedule->remaining_amount) {
                throw ValidationException::withMessages([
                    'amount' => 'المبلغ أكبر من المبلغ المتبقي على الاستحقاق.',
                ]);
            }

            $contract = $schedule->contract;
            $unit     = $contract->unit;

            $payment = Payment::query()->create([
                'code'                => $this->generateCode(),
                'contract_id'         => $contract->id,
                'payment_schedule_id' => $schedule->id,
                'tenant_id'           => $contract->tenant_id,
                'unit_id'             => $contract->unit_id,
                'property_id'         => $unit?->property_id,
                'amount'              => $amount,
                'payment_date'        => $data['paid_at'],
                'method'              => $data['payment_method'],
                'reference_number'    => $data['reference_number'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'status'              => PaymentStatus::Registered->value,
            ]);

            $paidAmount      = round((float) $schedule->paid_amount + $amount, 2);
            $remainingAmount = round(max((float) $schedule->total_amount - $paidAmount, 0), 2);

            $schedule->update([
                'paid_amount'      => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'paid_at'          => $remainingAmount <= 0 ? ($data['paid_at'] ?? now()->toDateString()) : null,
                'status'           => $remainingAmount <= 0
                    ? PaymentScheduleStatus::Paid->value
                    : PaymentScheduleStatus::Partial->value,
            ]);

            return $payment;
        });

        return $payment;
    }

    private function generateCode(): string
    {
        return 'PAY-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
    }
}
