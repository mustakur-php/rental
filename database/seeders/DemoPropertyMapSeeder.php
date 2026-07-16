<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoPropertyMapSeeder extends Seeder
{
    public function run(): void
    {
        $propertyId = DB::table('properties')->where('code', 'PROP-0001')->value('id');

        DB::table('property_maps')->updateOrInsert(
            ['property_id' => $propertyId, 'name' => 'الدور الأرضي'],
            [
                'image_path' => 'demo/maps/salam-ground-floor.jpg',
                'sort_order' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $mapId = DB::table('property_maps')->where('property_id', $propertyId)->where('name', 'الدور الأرضي')->value('id');

        for ($i = 1; $i <= 9; $i++) {
            $unitId = DB::table('units')->where('code', 'UNIT-SALAM-' . str_pad($i, 2, '0', STR_PAD_LEFT))->value('id');

            DB::table('unit_map_markers')->updateOrInsert(
                ['property_map_id' => $mapId, 'unit_id' => $unitId],
                [
                    'label' => (string) $i,
                    'x_coordinate' => 15 + (($i - 1) % 3) * 30,
                    'y_coordinate' => 20 + intdiv($i - 1, 3) * 25,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
