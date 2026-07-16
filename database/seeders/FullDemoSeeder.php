<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;

class FullDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoCompanySeeder::class,
            DemoPropertySeeder::class,
            DemoUnitSeeder::class,
            DemoTenantSeeder::class,
            DemoContractSeeder::class,
            DemoPaymentScheduleSeeder::class,
            DemoPaymentSeeder::class,
            DemoMaintenanceSeeder::class,
            DemoPropertyMapSeeder::class,
        ]);
    }
}
