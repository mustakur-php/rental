<?php

namespace Tests\Feature\Payments;

use App\Domains\Contract\Actions\CreateContractAction;
use App\Domains\Contract\Data\ContractData;
use App\Domains\Payment\Actions\RegisterPaymentAction;
use App\Domains\Company\Models\Company;
use App\Domains\Property\Models\Property;
use App\Domains\Unit\Models\Unit;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Payment\Models\PaymentSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartialPaymentScenarioTest extends TestCase
{
    use RefreshDatabase;

    private PaymentSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['code' => 'C-001', 'name' => 'شركة', 'status' => 'active']);
        $property = Property::create(['company_id' => $company->id, 'code' => 'P-001', 'name' => 'عقار', 'type' => 'residential', 'status' => 'active']);
        $unit = Unit::create(['property_id' => $property->id, 'code' => 'U-001', 'name' => 'وحدة', 'type' => 'apartment', 'status' => 'vacant']);
        $tenant = Tenant::create(['code' => 'T-001', 'type' => 'individual', 'name' => 'مستأجر', 'status' => 'active']);

        $contract = app(CreateContractAction::class)->execute(new ContractData(
            tenantId: $tenant->id,
            unitId: $unit->id,
            startDate: now()->toDateString(),
            endDate: now()->addYear()->toDateString(),
            billingCycle: 'monthly',
            totalAmount: 12000,
            vatRate: 15,
        ));

        $this->schedule = $contract->paymentSchedules()->first();
    }

    public function test_partial_payment_updates_schedule_remaining_amount(): void
    {
        $totalAmount = (float) $this->schedule->total_amount;
        $partialAmount = round($totalAmount / 2, 2);

        app(RegisterPaymentAction::class)->execute([
            'payment_schedule_id' => $this->schedule->id,
            'amount'              => $partialAmount,
            'payment_method'      => 'bank_transfer',
            'paid_at'             => now()->toDateString(),
        ]);

        $this->schedule->refresh();

        $this->assertEquals($partialAmount, (float) $this->schedule->paid_amount);
        $this->assertEquals(round($totalAmount - $partialAmount, 2), (float) $this->schedule->remaining_amount);
        $this->assertEquals('partial', $this->schedule->status->value);
    }

    public function test_payment_more_than_remaining_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $overAmount = (float) $this->schedule->total_amount + 100;

        app(RegisterPaymentAction::class)->execute([
            'payment_schedule_id' => $this->schedule->id,
            'amount'              => $overAmount,
            'payment_method'      => 'cash',
            'paid_at'             => now()->toDateString(),
        ]);
    }
}
