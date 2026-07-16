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
use App\Domains\Report\Services\ArrearsReportService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsBlueprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_can_be_loaded(): void
    {
        $user = $this->loginAsSuperAdmin();
        $response = $this->actingAs($user)->get('/reports');
        $response->assertStatus(200);
    }

    public function test_income_summary_calculates_required_paid_remaining_and_collection_rate(): void
    {
        [$contract] = $this->createContractScenario();

        $schedule = $contract->paymentSchedules()->first();
        $paidAmount = round((float) $schedule->total_amount / 2, 2);

        app(RegisterPaymentAction::class)->execute([
            'payment_schedule_id' => $schedule->id,
            'amount'              => $paidAmount,
            'payment_method'      => 'bank_transfer',
            'paid_at'             => now()->toDateString(),
        ]);

        $summary = app(IncomeReportService::class)->summary(new ReportFilters());

        $this->assertArrayHasKey('required', $summary);
        $this->assertArrayHasKey('paid', $summary);
        $this->assertArrayHasKey('remaining', $summary);
        $this->assertArrayHasKey('collection_rate', $summary);

        $this->assertEquals($paidAmount, $summary['paid']);
        $this->assertGreaterThan(0, $summary['required']);
        $this->assertGreaterThanOrEqual(0, $summary['remaining']);
        $this->assertGreaterThan(0, $summary['collection_rate']);
    }

    public function test_income_summary_can_be_filtered_by_unit(): void
    {
        [$firstContract, $firstUnit] = $this->createContractScenario();

        $secondUnit = Unit::create([
            'property_id' => $firstUnit->property_id,
            'code' => 'U-002',
            'name' => 'ÙˆØ­Ø¯Ø© Ø«Ø§Ù†ÙŠØ©',
            'type' => 'apartment',
            'status' => 'vacant',
        ]);

        $secondTenant = Tenant::create([
            'code' => 'T-002',
            'type' => 'individual',
            'name' => 'Ù…Ø³ØªØ£Ø¬Ø± Ø«Ø§Ù†ÙŠ',
            'status' => 'active',
        ]);

        $secondContract = app(CreateContractAction::class)->execute(new ContractData(
            tenantId: $secondTenant->id,
            unitId: $secondUnit->id,
            startDate: now()->toDateString(),
            endDate: now()->addYear()->toDateString(),
            billingCycle: 'monthly',
            totalAmount: 24000,
            vatRate: 15,
        ));

        $firstSchedule = $firstContract->paymentSchedules()->first();
        $secondSchedule = $secondContract->paymentSchedules()->first();

        app(RegisterPaymentAction::class)->execute([
            'payment_schedule_id' => $firstSchedule->id,
            'amount' => 100,
            'payment_method' => 'bank_transfer',
            'paid_at' => now()->toDateString(),
        ]);

        app(RegisterPaymentAction::class)->execute([
            'payment_schedule_id' => $secondSchedule->id,
            'amount' => 250,
            'payment_method' => 'bank_transfer',
            'paid_at' => now()->toDateString(),
        ]);

        $summary = app(IncomeReportService::class)->summary(new ReportFilters(unitId: $secondUnit->id));

        $this->assertEquals(round((float) $secondContract->paymentSchedules()->sum('total_amount'), 2), $summary['required']);
        $this->assertEquals(250.0, $summary['paid']);
        $this->assertNotEquals(350.0, $summary['paid']);
    }

    public function test_aging_report_buckets_overdue_amounts(): void
    {
        [$contract] = $this->createContractScenario();

        // نجعل قسط متأخر بتغيير تاريخ الاستحقاق لتاريخ ماضي
        $schedule = $contract->paymentSchedules()->first();
        $schedule->update([
            'due_date' => now()->subDays(45)->toDateString(),
            'status'   => 'overdue',
        ]);

        $aging = app(ArrearsReportService::class)->aging(new ReportFilters());

        $this->assertArrayHasKey('0_30', $aging);
        $this->assertArrayHasKey('31_60', $aging);
        $this->assertArrayHasKey('61_90', $aging);
        $this->assertArrayHasKey('90_plus', $aging);

        // القسط المتأخر 45 يوم يجب أن يكون في الـ bucket 31-60
        $this->assertGreaterThan(0, $aging['31_60']);
    }

    private function createContractScenario(): array
    {
        $company  = Company::create(['code' => 'C-001', 'name' => 'شركة', 'status' => 'active']);
        $property = Property::create(['company_id' => $company->id, 'code' => 'P-001', 'name' => 'عقار', 'type' => 'residential', 'status' => 'active']);
        $unit     = Unit::create(['property_id' => $property->id, 'code' => 'U-001', 'name' => 'وحدة', 'type' => 'apartment', 'status' => 'vacant']);
        $tenant   = Tenant::create(['code' => 'T-001', 'type' => 'individual', 'name' => 'مستأجر', 'status' => 'active']);

        $contract = app(CreateContractAction::class)->execute(new ContractData(
            tenantId: $tenant->id,
            unitId: $unit->id,
            startDate: now()->toDateString(),
            endDate: now()->addYear()->toDateString(),
            billingCycle: 'monthly',
            totalAmount: 12000,
            vatRate: 15,
        ));

        return [$contract, $unit, $tenant];
    }
}
