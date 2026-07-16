<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoContractSeeder extends Seeder
{
    public function run(): void
    {
        $tenant1 = DB::table('tenants')->where('code', 'TEN-0001')->value('id');
        $tenant2 = DB::table('tenants')->where('code', 'TEN-0002')->value('id');
        $tenant3 = DB::table('tenants')->where('code', 'TEN-0003')->value('id');

        $unit1 = DB::table('units')->where('code', 'UNIT-SALAM-01')->value('id');
        $unit2 = DB::table('units')->where('code', 'UNIT-SALAM-02')->value('id');
        $unit3 = DB::table('units')->where('code', 'UNIT-SALAM-03')->value('id');
        $unitOld = DB::table('units')->where('code', 'UNIT-SALAM-07')->value('id');

        $contracts = [
            [
                'code' => 'CNT-0001',
                'tenant_id' => $tenant1,
                'unit_id' => $unit1,
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'total_contract_amount' => 120000,
                'vat_rate' => 15,
                'status' => 'active',
                'payment_cycle' => 'quarterly',
            ],
            [
                'code' => 'CNT-0002',
                'tenant_id' => $tenant2,
                'unit_id' => $unit2,
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'total_contract_amount' => 180000,
                'vat_rate' => 15,
                'status' => 'active',
                'payment_cycle' => 'quarterly',
            ],
            [
                'code' => 'CNT-0003',
                'tenant_id' => $tenant3,
                'unit_id' => $unit3,
                'start_date' => now()->subMonths(3)->toDateString(),
                'end_date' => now()->addMonths(9)->toDateString(),
                'total_contract_amount' => 60000,
                'vat_rate' => 15,
                'status' => 'active',
                'payment_cycle' => 'monthly',
            ],
            [
                'code' => 'CNT-OLD-0001',
                'tenant_id' => $tenant1,
                'unit_id' => $unitOld,
                'start_date' => now()->subYear()->startOfYear()->toDateString(),
                'end_date' => now()->subYear()->endOfYear()->toDateString(),
                'total_contract_amount' => 48000,
                'vat_rate' => 15,
                'status' => 'ended',
                'payment_cycle' => 'monthly',
            ],
        ];

        foreach ($contracts as $contract) {
            $vatAmount = $contract['total_contract_amount'] * ($contract['vat_rate'] / 100);
            DB::table('contracts')->updateOrInsert(
                ['code' => $contract['code']],
                array_merge($contract, [
                    'vat_amount' => $vatAmount,
                    'total_with_vat' => $contract['total_contract_amount'] + $vatAmount,
                    'currency' => 'SAR',
                    'deposit_amount' => 0,
                    'notes' => 'عقد تجريبي',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
