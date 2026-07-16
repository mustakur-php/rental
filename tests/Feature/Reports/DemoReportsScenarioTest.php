<?php

namespace Tests\Feature\Reports;

use App\Domains\Contract\Actions\CreateContractAction;
use App\Domains\Contract\Data\ContractData;
use App\Domains\Payment\Actions\RegisterPaymentAction;
use App\Domains\Company\Models\Company;
use App\Domains\Property\Models\Property;
use App\Domains\Unit\Models\Unit;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Report\DTOs\ReportFilters;
use App\Domains\Report\Services\IncomeReportService;
use App\Domains\Report\Services\OccupancyReportService;
use App\Livewire\Dashboard\MainDashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoReportsScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_can_calculate_demo_kpis(): void
    {
        $company  = Company::create(['code' => 'C-001', 'name' => 'شركة', 'status' => 'active']);
        $property = Property::create(['company_id' => $company->id, 'code' => 'P-001', 'name' => 'عقار', 'type' => 'residential', 'status' => 'active']);
        $unit     = Unit::create(['property_id' => $property->id, 'code' => 'U-001', 'name' => 'وحدة', 'type' => 'apartment', 'status' => 'vacant']);
        $tenant   = Tenant::create(['code' => 'T-001', 'type' => 'individual', 'name' => 'مستأجر', 'status' => 'active']);

        app(CreateContractAction::class)->execute(new ContractData(
            tenantId: $tenant->id,
            unitId: $unit->id,
            startDate: now()->toDateString(),
            endDate: now()->addYear()->toDateString(),
            billingCycle: 'monthly',
            totalAmount: 12000,
            vatRate: 15,
        ));

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        // تأكيد أن الـ KPIs تُحسب بشكل صحيح
        $occupancy = app(OccupancyReportService::class)->summary();
        $this->assertEquals(1, $occupancy['total_units']);
        $this->assertEquals(1, $occupancy['rented_units']);
        $this->assertEquals(100.0, $occupancy['occupancy_rate']);
    }

    public function test_aging_report_includes_overdue_demo_schedule(): void
    {
        $company  = Company::create(['code' => 'C-001', 'name' => 'شركة', 'status' => 'active']);
        $property = Property::create(['company_id' => $company->id, 'code' => 'P-001', 'name' => 'عقار', 'type' => 'residential', 'status' => 'active']);
        $unit     = Unit::create(['property_id' => $property->id, 'code' => 'U-001', 'name' => 'وحدة', 'type' => 'apartment', 'status' => 'vacant']);
        $tenant   = Tenant::create(['code' => 'T-001', 'type' => 'individual', 'name' => 'مستأجر', 'status' => 'active']);

        $contract = app(CreateContractAction::class)->execute(new ContractData(
            tenantId: $tenant->id,
            unitId: $unit->id,
            startDate: now()->subYear()->toDateString(),
            endDate: now()->addMonths(6)->toDateString(),
            billingCycle: 'monthly',
            totalAmount: 12000,
            vatRate: 15,
        ));

        // نجعل أول قسط متأخر
        $schedule = $contract->paymentSchedules()->first();
        $schedule->update([
            'due_date' => now()->subDays(20)->toDateString(),
            'status'   => 'overdue',
        ]);

        $income = app(IncomeReportService::class)->summary(new ReportFilters());

        $this->assertGreaterThan(0, $income['required']);
        $this->assertEquals(0, $income['paid']);
        $this->assertEquals($income['required'], $income['remaining']);
        $this->assertEquals(0, $income['collection_rate']);
    }
}
