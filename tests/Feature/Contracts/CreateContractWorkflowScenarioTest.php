<?php

namespace Tests\Feature\Contracts;

use App\Domains\Contract\Actions\CreateContractAction;
use App\Domains\Contract\Data\ContractData;
use App\Domains\Contract\Models\Contract;
use App\Domains\Company\Models\Company;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Property\Models\Property;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Unit\Models\Unit;
use App\Livewire\Contracts\CreateContractWizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CreateContractWorkflowScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_contract_generates_schedule_and_marks_unit_as_rented(): void
    {
        // إنشاء بيانات أساسية
        $company = Company::create([
            'code' => 'COMP-001',
            'name' => 'شركة الاختبار',
            'status' => 'active',
        ]);

        $property = Property::create([
            'company_id' => $company->id,
            'code' => 'PROP-0001',
            'name' => 'عقار الاختبار',
            'type' => 'residential',
            'status' => 'active',
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'code' => 'UNIT-001',
            'name' => 'وحدة 1',
            'type' => 'apartment',
            'status' => 'vacant',
        ]);

        $tenant = Tenant::create([
            'code' => 'TEN-0001',
            'type' => 'individual',
            'name' => 'مستأجر الاختبار',
            'status' => 'active',
        ]);

        // تنفيذ الـ Action
        $data = new ContractData(
            tenantId: $tenant->id,
            unitId: $unit->id,
            startDate: now()->toDateString(),
            endDate: now()->addYear()->subDay()->toDateString(),
            billingCycle: 'monthly',
            totalAmount: 12000,
            vatRate: 15,
        );

        $contract = app(CreateContractAction::class)->execute($data);

        // تأكيد إنشاء العقد
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'status' => 'active',
        ]);

        // تأكيد تغيير حالة الوحدة
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'status' => 'rented',
        ]);

        // تأكيد توليد جداول الدفع (12 قسط شهري)
        $schedulesCount = PaymentSchedule::where('contract_id', $contract->id)->count();
        $this->assertEquals(12, $schedulesCount);

        // تأكيد حساب الضريبة
        $this->assertEquals(1800.00, (float) $contract->vat_amount);
        $this->assertEquals(13800.00, (float) $contract->total_with_vat);
    }

    public function test_contract_schedules_are_generated_for_full_contract_duration(): void
    {
        [$unit, $tenant] = $this->createScenario();

        $contract = app(CreateContractAction::class)->execute(new ContractData(
            tenantId: $tenant->id,
            unitId: $unit->id,
            startDate: '2026-01-01',
            endDate: '2028-12-31',
            billingCycle: 'semi_annually',
            totalAmount: 300000,
            vatRate: 15,
        ));

        $this->assertEquals(6, $contract->installments_count);
        $this->assertEquals(6, $contract->paymentSchedules()->count());
        $this->assertEquals(345000.0, round((float) $contract->paymentSchedules()->sum('total_amount'), 2));
        $this->assertEquals([
            '2026-01-01',
            '2026-07-01',
            '2027-01-01',
            '2027-07-01',
            '2028-01-01',
            '2028-07-01',
        ], $contract->paymentSchedules()->orderBy('installment_no')->get()->pluck('due_date')->map->format('Y-m-d')->all());
    }

    public function test_contract_can_be_created_from_livewire_wizard_with_uploaded_file(): void
    {
        $this->loginAsSuperAdmin();
        Storage::fake('public');
        [$unit, $tenant] = $this->createScenario();

        Livewire::test(CreateContractWizard::class)
            ->set('tenant_id', $tenant->id)
            ->set('unit_id', $unit->id)
            ->set('ejar_number', 'EJAR-123')
            ->set('start_date', '2026-01-01')
            ->set('end_date', '2026-12-31')
            ->set('billing_cycle', 'quarterly')
            ->set('annual_rent', 120000)   // 1 سنة × 120,000 = 120,000 إجمالي
            ->set('vat_rate', 15)
            ->set('contract_file', UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'))
            ->call('createContract')
            ->assertHasNoErrors();

        $contract = Contract::where('ejar_number', 'EJAR-123')->firstOrFail();

        $this->assertEquals(4, $contract->installments_count);
        $this->assertEquals(4, $contract->paymentSchedules()->count());
        $this->assertNotNull($contract->contract_file_path);
        Storage::disk('public')->assertExists($contract->contract_file_path);
    }

    public function test_wizard_advances_after_selecting_tenant_and_unit(): void
    {
        [$unit, $tenant] = $this->createScenario();

        // الاختيار وحده لا يُقدّم الخطوة — التالي هو الذي يُقدّم
        Livewire::test(CreateContractWizard::class)
            ->assertSet('step', 1)
            ->call('selectTenant', $tenant->id)
            ->assertSet('tenant_id', $tenant->id)
            ->assertSet('step', 1)           // يبقى في 1 حتى يضغط التالي
            ->call('nextStep')
            ->assertSet('step', 2)           // تقدّم بعد التالي
            ->call('selectUnit', $unit->id)
            ->assertSet('unit_id', $unit->id)
            ->assertSet('step', 2)           // يبقى في 2 حتى يضغط التالي
            ->call('nextStep')
            ->assertSet('step', 3);          // تقدّم بعد التالي
    }

    public function test_wizard_prefills_unit_from_query_string(): void
    {
        [$unit, $tenant] = $this->createScenario();

        // الوحدة تُملأ مسبقاً من query string لكن الخطوة تبقى 1 (المستأجر إلزامي أولاً)
        Livewire::withQueryParams(['unit_id' => $unit->id])
            ->test(CreateContractWizard::class)
            ->assertSet('unit_id', $unit->id)
            ->assertSet('step', 1)
            ->call('selectTenant', $tenant->id)
            ->assertSet('tenant_id', $tenant->id)
            ->assertSet('step', 1)           // لا تقدّم تلقائي
            ->call('nextStep')
            ->assertSet('step', 2);          // الوحدة محددة مسبقاً، التالي يتقدم لـ 2 ثم سيمكن التقدم لـ 3
    }

    private function createScenario(): array
    {
        $company = Company::create([
            'code' => 'COMP-SCENARIO',
            'name' => 'Scenario Company',
            'status' => 'active',
        ]);

        $property = Property::create([
            'company_id' => $company->id,
            'code' => 'PROP-SCENARIO',
            'name' => 'Scenario Property',
            'type' => 'residential',
            'status' => 'active',
        ]);

        $unit = Unit::create([
            'property_id' => $property->id,
            'code' => 'UNIT-SCENARIO',
            'name' => 'Scenario Unit',
            'type' => 'apartment',
            'status' => 'vacant',
        ]);

        $tenant = Tenant::create([
            'code' => 'TEN-SCENARIO',
            'type' => 'individual',
            'name' => 'Scenario Tenant',
            'status' => 'active',
        ]);

        return [$unit, $tenant];
    }
}
