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
use App\Enums\ContractStatus;
use App\Enums\PaymentScheduleStatus;
use App\Enums\UnitStatus;
use App\Livewire\Contracts\ContractIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TerminateContractTest extends TestCase
{
    use RefreshDatabase;

    private function makeActiveContract(string $start, string $end): Contract
    {
        $company  = Company::create(['code' => 'C-T', 'name' => 'Co', 'status' => 'active']);
        $property = Property::create(['company_id' => $company->id, 'code' => 'P-T', 'name' => 'Prop', 'type' => 'residential', 'status' => 'active']);
        $unit     = Unit::create(['property_id' => $property->id, 'code' => 'U-T', 'name' => 'Unit', 'type' => 'apartment', 'status' => 'vacant']);
        $tenant   = Tenant::create(['code' => 'TN-T', 'type' => 'individual', 'name' => 'Tenant', 'status' => 'active']);

        return app(CreateContractAction::class)->execute(new ContractData(
            tenantId:     $tenant->id,
            unitId:       $unit->id,
            startDate:    $start,
            endDate:      $end,
            billingCycle: 'quarterly',
            totalAmount:  40000,
            vatRate:      15,
        ));
    }

    public function test_active_contract_can_be_terminated_early(): void
    {
        $this->loginAsSuperAdmin();
        // عقد يبدأ من يناير 2026 لنهاية 2027 — ربع سنوي = 8 أقساط
        $contract = $this->makeActiveContract('2026-01-01', '2027-12-31');
        $this->assertEquals('rented', $contract->unit->fresh()->status->value);

        // نُحاكي قسطاً متأخراً: القسط الأول مرّ تاريخه ولم يُسدَّد
        $firstSchedule = $contract->paymentSchedules()->orderBy('installment_no')->first();
        $firstSchedule->update([
            'due_date' => '2026-01-01',
            'status'   => 'overdue',
        ]);

        // إنهاء العقد في منتصف العام
        Livewire::test(ContractIndex::class)
            ->call('openTerminateModal', $contract->id)
            ->set('terminateForm.date', '2026-06-30')
            ->set('terminateForm.reason', 'non_payment')
            ->set('terminateForm.notes', 'تراكم متأخرات')
            ->call('terminateContract')
            ->assertHasNoErrors();

        $contract->refresh();

        // ① الحالة منتهي مبكراً
        $this->assertEquals('early_ended', $contract->status->value);
        $this->assertEquals('2026-06-30', $contract->termination_date->format('Y-m-d'));
        $this->assertEquals('non_payment', $contract->termination_reason);

        // ② الوحدة حُرِّرت
        $this->assertEquals('vacant', $contract->unit->fresh()->status->value);

        // ③ القسط المتأخر (دين) لا يزال موجوداً كـ overdue
        $this->assertEquals(
            'overdue',
            $firstSchedule->fresh()->status->value,
            'الدين المتأخر يجب أن يبقى overdue وليس cancelled'
        );
        $this->assertGreaterThan(0, $firstSchedule->fresh()->remaining_amount,
            'مبلغ الدين يجب أن يبقى محفوظاً'
        );

        // ④ الأقساط المستقبلية (بعد 2026-06-30) ألغيت وصفرت
        $futureSchedules = PaymentSchedule::where('contract_id', $contract->id)
            ->where('due_date', '>', '2026-06-30')
            ->get();

        foreach ($futureSchedules as $s) {
            $this->assertEquals('cancelled', $s->status->value, "قسط مستقبلي يجب أن يكون cancelled");
            $this->assertEquals(0, (float)$s->remaining_amount, "مبلغ قسط مستقبلي يجب أن يكون 0");
        }
    }

    public function test_overdue_debt_is_preserved_after_termination(): void
    {
        $this->loginAsSuperAdmin();
        $contract = $this->makeActiveContract('2026-01-01', '2026-12-31');

        // نجعل 2 قسط متأخر
        $schedules = $contract->paymentSchedules()->orderBy('installment_no')->take(2)->get();
        foreach ($schedules as $s) {
            $s->update(['status' => 'overdue', 'due_date' => '2026-01-01']);
        }

        Livewire::test(ContractIndex::class)
            ->call('openTerminateModal', $contract->id)
            ->set('terminateForm.date', now()->toDateString())
            ->set('terminateForm.reason', 'non_payment')
            ->call('terminateContract')
            ->assertHasNoErrors();

        // الديون المتأخرة محفوظة
        $overdueCount = PaymentSchedule::where('contract_id', $contract->id)
            ->where('status', 'overdue')
            ->where('remaining_amount', '>', 0)
            ->count();

        $this->assertEquals(2, $overdueCount, 'يجب أن يبقى قسطان متأخران كديون');
    }

    public function test_termination_at_end_date_sets_ended_status(): void
    {
        $this->loginAsSuperAdmin();
        $contract = $this->makeActiveContract('2026-01-01', '2026-12-31');

        Livewire::test(ContractIndex::class)
            ->call('openTerminateModal', $contract->id)
            ->set('terminateForm.date', '2026-12-31')  // نفس تاريخ النهاية
            ->set('terminateForm.reason', 'mutual_agreement')
            ->call('terminateContract')
            ->assertHasNoErrors();

        $this->assertEquals(ContractStatus::Ended->value, $contract->fresh()->status->value);
    }

    public function test_termination_requires_date_and_reason(): void
    {
        $this->loginAsSuperAdmin();
        $contract = $this->makeActiveContract('2026-01-01', '2027-12-31');

        Livewire::test(ContractIndex::class)
            ->call('openTerminateModal', $contract->id)
            ->set('terminateForm.date', '')      // نفرغ التاريخ بعد openTerminateModal
            ->set('terminateForm.reason', '')
            ->call('terminateContract')
            ->assertHasErrors(['terminateForm.date', 'terminateForm.reason']);

        // العقد لا يزال نشطاً
        $this->assertEquals(ContractStatus::Active->value, $contract->fresh()->status->value);
    }

    public function test_non_active_contract_cannot_be_terminated(): void
    {
        $this->loginAsSuperAdmin();
        $contract = $this->makeActiveContract('2026-01-01', '2027-12-31');

        // نُنهي العقد أولاً
        $contract->update(['status' => ContractStatus::EarlyEnded->value]);

        // findOrFail يرمي ModelNotFoundException لأن العقد ليس active
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(ContractIndex::class)
            ->call('openTerminateModal', $contract->id)
            ->set('terminateForm.date', '2026-06-30')
            ->set('terminateForm.reason', 'other')
            ->call('terminateContract');
    }
}
