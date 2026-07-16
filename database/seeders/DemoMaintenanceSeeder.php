<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoMaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $propertyId = DB::table('properties')->where('code', 'PROP-0001')->value('id');
        $unitId = DB::table('units')->where('code', 'UNIT-SALAM-09')->value('id');

        DB::table('maintenance_requests')->updateOrInsert(
            ['code' => 'MNT-0001'],
            [
                'property_id' => $propertyId,
                'unit_id' => $unitId,
                'type' => 'electricity',
                'title' => 'مشكلة كهرباء في الوحدة',
                'description' => 'طلب صيانة تجريبي',
                'priority' => 'high',
                'status' => 'in_progress',
                'request_date' => now()->toDateString(),
                'cost' => 1500,
                'unit_impact' => 'stops_unit',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
