<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'code' => 'TEN-0001',
                'type' => 'individual',
                'name' => 'محمد أحمد',
                'mobile' => '0500000001',
                'email' => 'mohammed@example.test',
                'national_id' => '1000000001',
                'nationality' => 'سعودي',
            ],
            [
                'code' => 'TEN-0002',
                'type' => 'company',
                'name' => 'شركة السلام التجارية',
                'mobile' => '0500000002',
                'email' => 'alsalam@example.test',
                'commercial_registration' => '1010000001',
                'contact_person_name' => 'خالد محمد',
                'contact_person_mobile' => '0500000099',
            ],
            [
                'code' => 'TEN-0003',
                'type' => 'individual',
                'name' => 'عبدالله صالح',
                'mobile' => '0500000003',
                'email' => 'abdullah@example.test',
                'national_id' => '1000000003',
                'nationality' => 'سعودي',
            ],
        ];

        foreach ($tenants as $tenant) {
            DB::table('tenants')->updateOrInsert(
                ['code' => $tenant['code']],
                array_merge([
                    'address' => 'عنوان تجريبي',
                    'notes' => null,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $tenant)
            );
        }
    }
}
