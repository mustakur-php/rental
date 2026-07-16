<?php

namespace Tests\Feature\Demo;

use Database\Seeders\FullDemoSeeder;
use App\Domains\Property\Models\Property;
use App\Domains\Unit\Models\Unit;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Contract\Models\Contract;
use App\Domains\Payment\Models\PaymentSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_demo_seeder_can_run(): void
    {
        $this->seed(FullDemoSeeder::class);

        // تأكيد وجود بيانات تجريبية
        $this->assertGreaterThan(0, Property::count());
        $this->assertGreaterThan(0, Unit::count());
        $this->assertGreaterThan(0, Tenant::count());
        $this->assertGreaterThan(0, Contract::count());
        $this->assertGreaterThan(0, PaymentSchedule::count());

        // تأكيد وجود عقود نشطة
        $this->assertGreaterThan(0, Contract::where('status', 'active')->count());

        // تأكيد وجود وحدات مؤجرة
        $this->assertGreaterThan(0, Unit::where('status', 'rented')->count());
    }
}
