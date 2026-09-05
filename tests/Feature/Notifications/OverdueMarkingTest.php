<?php

namespace Tests\Feature\Notifications;

use App\Domains\Company\Models\Company;
use App\Domains\Contract\Models\Contract;
use App\Domains\Notification\Services\NotificationSyncService;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Property\Models\Property;
use App\Domains\Property\Models\PropertyLease;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use App\Enums\PaymentScheduleStatus;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Unit\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueMarkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_partially_paid_schedule_is_not_overwritten_as_overdue(): void
    {
        // القاعدة في PaymentSchedule::refreshPaymentStatus(): partial تسبق overdue
        $schedule = $this->makeTenantSchedule(
            dueDate: now()->subDays(60),
            paid: 5000,
            status: 'partial',
        );

        app(NotificationSyncService::class)->markOverdueStatuses();

        $this->assertSame(PaymentScheduleStatus::Partial, $schedule->fresh()->status);
    }

    public function test_an_unpaid_schedule_past_its_grace_period_becomes_overdue(): void
    {
        $schedule = $this->makeTenantSchedule(
            dueDate: now()->subDays(60),
            paid: 0,
            status: 'pending',
            grace: 10,
        );

        app(NotificationSyncService::class)->markOverdueStatuses();

        $this->assertSame(PaymentScheduleStatus::Overdue, $schedule->fresh()->status);
    }

    public function test_a_schedule_still_inside_its_grace_period_is_left_alone(): void
    {
        // فات موعدها بيومين لكن فترة السماح 10 أيام ⇒ لم تتأخر بعد
        $schedule = $this->makeTenantSchedule(
            dueDate: now()->subDays(2),
            paid: 0,
            status: 'due',
            grace: 10,
        );

        app(NotificationSyncService::class)->markOverdueStatuses();

        $this->assertSame(PaymentScheduleStatus::Due, $schedule->fresh()->status);
    }

    public function test_owner_schedule_with_a_partial_payment_is_not_overwritten(): void
    {
        $schedule = $this->makeOwnerSchedule(paid: 5000, status: 'partial');

        app(NotificationSyncService::class)->markOverdueStatuses();

        $this->assertSame('partial', $schedule->fresh()->status);
    }

    public function test_unpaid_owner_schedule_past_due_becomes_overdue(): void
    {
        $schedule = $this->makeOwnerSchedule(paid: 0, status: 'pending');

        app(NotificationSyncService::class)->markOverdueStatuses();

        $this->assertSame('overdue', $schedule->fresh()->status);
    }

    public function test_every_type_shares_one_severity_scale(): void
    {
        $service = app(NotificationSyncService::class);

        $severityFor = function (int $days) use ($service) {
            $method = new \ReflectionMethod($service, 'severityFor');

            return $method->invoke($service, $days);
        };

        $this->assertSame('danger', $severityFor(-5));  // فات موعده
        $this->assertSame('danger', $severityFor(0));
        $this->assertSame('danger', $severityFor(7));
        $this->assertSame('warning', $severityFor(8));
        $this->assertSame('warning', $severityFor(30));
        $this->assertSame('info', $severityFor(31));
        $this->assertSame('info', $severityFor(90));
    }

    private function makeTenantSchedule(
        \DateTimeInterface $dueDate,
        float $paid,
        string $status,
        int $grace = 0,
    ): PaymentSchedule {
        $property = $this->makeProperty();

        $unit = Unit::create([
            'property_id' => $property->id,
            'code'        => 'UNIT-OVD',
            'name'        => 'Overdue Unit',
            'type'        => 'apartment',
            'status'      => 'rented',
        ]);

        $tenant = Tenant::create([
            'code'   => 'TEN-OVD',
            'type'   => 'individual',
            'name'   => 'Overdue Tenant',
            'status' => 'active',
        ]);

        $contract = Contract::create([
            'tenant_id'             => $tenant->id,
            'unit_id'               => $unit->id,
            'code'                  => 'CON-OVD',
            'start_date'            => now()->subYear()->toDateString(),
            'end_date'              => now()->addYear()->toDateString(),
            'total_contract_amount' => 60000,
            'vat_rate'              => 0,
            'vat_amount'            => 0,
            'total_with_vat'        => 60000,
            'payment_cycle'         => 'annually',
            'installments_count'    => 1,
            'status'                => 'active',
        ]);

        return PaymentSchedule::create([
            'contract_id'       => $contract->id,
            'installment_no'    => 1,
            'due_date'          => $dueDate,
            'base_amount'       => 60000,
            'vat_amount'        => 0,
            'total_amount'      => 60000,
            'paid_amount'       => $paid,
            'remaining_amount'  => 60000 - $paid,
            'grace_period_days' => $grace,
            'status'            => $status,
        ]);
    }

    private function makeOwnerSchedule(float $paid, string $status): PropertyLeaseSchedule
    {
        $lease = PropertyLease::create([
            'property_id'        => $this->makeProperty(['ownership_type' => 'leased'])->id,
            'owner_name'         => 'Overdue Owner',
            'start_date'         => now()->subYear()->toDateString(),
            'end_date'           => now()->addYear()->toDateString(),
            'total_amount'       => 60000,
            'vat_rate'           => 0,
            'vat_amount'         => 0,
            'total_with_vat'     => 60000,
            'payment_cycle'      => 'annually',
            'installments_count' => 1,
            'status'             => 'active',
        ]);

        return PropertyLeaseSchedule::create([
            'property_lease_id' => $lease->id,
            'installment_no'    => 1,
            'due_date'          => now()->subDays(60)->toDateString(),
            'amount'            => 60000,
            'paid_amount'       => $paid,
            'remaining_amount'  => 60000 - $paid,
            'status'            => $status,
        ]);
    }

    private function makeProperty(array $attributes = []): Property
    {
        $company = Company::create([
            'code'   => 'COMP-OVD-'.random_int(1000, 9999),
            'name'   => 'Overdue Co',
            'status' => 'active',
        ]);

        return Property::create(array_merge([
            'company_id'     => $company->id,
            'code'           => 'PROP-OVD-'.random_int(1000, 9999),
            'name'           => 'Overdue Property',
            'type'           => 'residential',
            'ownership_type' => 'owned',
            'status'         => 'active',
        ], $attributes));
    }
}
