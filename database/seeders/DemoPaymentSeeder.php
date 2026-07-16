<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = DB::table('payment_schedules')
            ->whereIn('status', ['paid', 'partial'])
            ->get();

        foreach ($schedules as $schedule) {
            $contract = DB::table('contracts')->where('id', $schedule->contract_id)->first();
            $unit = DB::table('units')->where('id', $contract->unit_id)->first();
            $propertyId = $unit->property_id;

            DB::table('payments')->updateOrInsert(
                [
                    'payment_schedule_id' => $schedule->id,
                    'reference_number' => 'REF-' . $schedule->id,
                ],
                [
                    'code' => 'PAY-' . str_pad($schedule->id, 5, '0', STR_PAD_LEFT),
                    'contract_id' => $contract->id,
                    'tenant_id' => $contract->tenant_id,
                    'unit_id' => $contract->unit_id,
                    'property_id' => $propertyId,
                    'amount' => $schedule->paid_amount,
                    'payment_date' => now()->subDays(3)->toDateString(),
                    'method' => 'bank_transfer',
                    'notes' => 'دفعة تجريبية',
                    'status' => 'registered',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
