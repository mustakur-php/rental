<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoPropertySeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('companies')->where('code', 'COMP-001')->value('id');

        $properties = [
            [
                'code' => 'PROP-0001',
                'name' => 'مجمع السلام',
                'type' => 'commercial_complex',
                'city' => 'الرياض',
                'district' => 'النرجس',
                'address' => 'طريق الملك سلمان',
                'status' => 'active',
            ],
            [
                'code' => 'PROP-0002',
                'name' => 'عمارة الندى',
                'type' => 'residential_building',
                'city' => 'جدة',
                'district' => 'الصفا',
                'address' => 'شارع الأمير متعب',
                'status' => 'active',
            ],
        ];

        foreach ($properties as $property) {
            DB::table('properties')->updateOrInsert(
                ['code' => $property['code']],
                array_merge($property, [
                    'company_id' => $companyId,
                    'description' => 'بيانات تجريبية للعقار',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
