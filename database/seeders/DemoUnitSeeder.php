<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoUnitSeeder extends Seeder
{
    public function run(): void
    {
        $salamId = DB::table('properties')->where('code', 'PROP-0001')->value('id');
        $nadaId = DB::table('properties')->where('code', 'PROP-0002')->value('id');

        $units = [];

        for ($i = 1; $i <= 9; $i++) {
            $status = match (true) {
                $i <= 6 => 'rented',
                $i <= 8 => 'vacant',
                default => 'maintenance',
            };

            $units[] = [
                'code' => 'UNIT-SALAM-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'name' => 'محل ' . $i,
                'property_id' => $salamId,
                'type' => 'shop',
                'internal_number' => 'A' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'area' => 60 + ($i * 5),
                'floor' => 'الأرضي',
                'electricity_meter' => 'ELEC-SALAM-' . $i,
                'water_meter' => 'WATER-SALAM-' . $i,
                'status' => $status,
            ];
        }

        for ($i = 1; $i <= 6; $i++) {
            $units[] = [
                'code' => 'UNIT-NADA-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'name' => 'شقة ' . (100 + $i),
                'property_id' => $nadaId,
                'type' => 'apartment',
                'internal_number' => (string) (100 + $i),
                'area' => 120,
                'floor' => $i <= 3 ? 'الأول' : 'الثاني',
                'electricity_meter' => 'ELEC-NADA-' . $i,
                'water_meter' => 'WATER-NADA-' . $i,
                'status' => $i <= 4 ? 'rented' : 'vacant',
            ];
        }

        foreach ($units as $unit) {
            DB::table('units')->updateOrInsert(
                ['code' => $unit['code']],
                array_merge($unit, [
                    'description' => 'وحدة تجريبية',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
