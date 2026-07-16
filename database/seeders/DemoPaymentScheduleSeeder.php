<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoPaymentScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = DB::table('contracts')->whereIn('code', ['CNT-0001', 'CNT-0002', 'CNT-0003'])->get();

        foreach ($contracts as $contract) {
            $parts = $contract->payment_cycle === 'monthly' ? 12 : 4;
            $baseAmount = round($contract->total_contract_amount / $parts, 2);
            $vatAmount = round($contract->vat_amount / $parts, 2);
            $totalAmount = round($contract->total_with_vat / $parts, 2);

            for ($i = 1; $i <= $parts; $i++) {
                $dueDate = Carbon::parse($contract->start_date)
                    ->addMonths($contract->payment_cycle === 'monthly' ? $i - 1 : ($i - 1) * 3)
                    ->toDateString();

                $status = 'pending';
                if (Carbon::parse($dueDate)->isPast()) {
                    $status = $i === 1 ? 'paid' : ($i === 2 ? 'partial' : 'overdue');
                }

                DB::table('payment_schedules')->updateOrInsert(
                    [
                        'contract_id' => $contract->id,
                        'installment_no' => $i,
                    ],
                    [
                        'due_date' => $dueDate,
                        'base_amount' => $baseAmount,
                        'vat_amount' => $vatAmount,
                        'total_amount' => $totalAmount,
                        'paid_amount' => $status === 'paid' ? $totalAmount : ($status === 'partial' ? round($totalAmount * 0.55, 2) : 0),
                        'remaining_amount' => $status === 'paid' ? 0 : ($status === 'partial' ? round($totalAmount * 0.45, 2) : $totalAmount),
                        'status' => $status,
                        'grace_period_days' => 10,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
