<?php

namespace Tests\Feature\Notifications;

use App\Domains\Company\Models\Company;
use App\Domains\Contract\Models\Contract;
use App\Domains\Notification\Models\Notification;
use App\Domains\Notification\Services\NotificationSyncService;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Property\Models\Property;
use App\Domains\Property\Models\PropertyLease;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Unit\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivedSourceNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_notification_is_created_for_a_schedule_of_an_archived_contract(): void
    {
        $schedule = $this->createTenantSchedule(archived: true);

        app(NotificationSyncService::class)->sync();

        $this->assertSame(0, Notification::where('notifiable_source_id', $schedule->id)
            ->where('notifiable_source_type', PaymentSchedule::class)
            ->count());
    }

    public function test_notification_is_created_for_a_schedule_of_an_active_contract(): void
    {
        $schedule = $this->createTenantSchedule(archived: false);

        app(NotificationSyncService::class)->sync();

        $this->assertSame(1, Notification::where('notifiable_source_id', $schedule->id)
            ->where('notifiable_source_type', PaymentSchedule::class)
            ->count());
    }

    public function test_existing_notification_is_removed_once_its_contract_is_archived(): void
    {
        $schedule = $this->createTenantSchedule(archived: false);

        app(NotificationSyncService::class)->sync();
        $this->assertSame(1, Notification::where('notifiable_source_id', $schedule->id)->count());

        $schedule->contract->update(['archived_at' => now()]);
        app(NotificationSyncService::class)->sync();

        $this->assertSame(0, Notification::where('notifiable_source_id', $schedule->id)->count());
    }

    public function test_owner_lease_notification_is_removed_once_the_lease_is_archived(): void
    {
        $schedule = $this->createOwnerSchedule();

        app(NotificationSyncService::class)->sync();
        $this->assertSame(1, Notification::where('notifiable_source_type', PropertyLeaseSchedule::class)
            ->where('notifiable_source_id', $schedule->id)->count());

        $schedule->lease->update(['archived_at' => now()]);
        app(NotificationSyncService::class)->sync();

        $this->assertSame(0, Notification::where('notifiable_source_type', PropertyLeaseSchedule::class)
            ->where('notifiable_source_id', $schedule->id)->count());
    }

    public function test_owner_lease_notification_is_removed_once_its_property_is_archived(): void
    {
        $schedule = $this->createOwnerSchedule();

        app(NotificationSyncService::class)->sync();
        $this->assertSame(1, Notification::where('notifiable_source_type', PropertyLeaseSchedule::class)
            ->where('notifiable_source_id', $schedule->id)->count());

        $schedule->lease->property->update(['archived_at' => now()]);
        app(NotificationSyncService::class)->sync();

        $this->assertSame(0, Notification::where('notifiable_source_type', PropertyLeaseSchedule::class)
            ->where('notifiable_source_id', $schedule->id)->count());
    }

    private function createTenantSchedule(bool $archived): PaymentSchedule
    {
        $property = $this->createProperty();

        $unit = Unit::create([
            'property_id' => $property->id,
            'code'        => 'UNIT-ARCH',
            'name'        => 'Archived Test Unit',
            'type'        => 'apartment',
            'status'      => 'rented',
        ]);

        $tenant = Tenant::create([
            'code'   => 'TEN-ARCH',
            'type'   => 'individual',
            'name'   => 'Archived Test Tenant',
            'status' => 'active',
        ]);

        $contract = Contract::create([
            'tenant_id'             => $tenant->id,
            'unit_id'               => $unit->id,
            'code'                  => 'CON-ARCH',
            'start_date'            => now()->subMonths(2)->toDateString(),
            'end_date'              => now()->addYear()->toDateString(),
            'total_contract_amount' => 120000,
            'vat_rate'              => 0,
            'vat_amount'            => 0,
            'total_with_vat'        => 120000,
            'payment_cycle'         => 'annually',
            'installments_count'    => 1,
            'status'                => 'active',
            'archived_at'           => $archived ? now() : null,
        ]);

        // مستحقة بعد 10 أيام ⇒ تُنتج تنبيه payment_due لعقد نشط
        return PaymentSchedule::create([
            'contract_id'      => $contract->id,
            'installment_no'   => 1,
            'due_date'         => now()->addDays(10)->toDateString(),
            'base_amount'      => 120000,
            'vat_amount'       => 0,
            'total_amount'     => 120000,
            'paid_amount'      => 0,
            'remaining_amount' => 120000,
            'status'           => 'pending',
        ]);
    }

    private function createOwnerSchedule(): PropertyLeaseSchedule
    {
        $lease = PropertyLease::create([
            'property_id'        => $this->createProperty(['ownership_type' => 'leased'])->id,
            'owner_name'         => 'Archived Owner',
            'start_date'         => now()->subMonth()->toDateString(),
            'end_date'           => now()->addYear()->toDateString(),
            'total_amount'       => 120000,
            'vat_rate'           => 0,
            'vat_amount'         => 0,
            'total_with_vat'     => 120000,
            'payment_cycle'      => 'annually',
            'installments_count' => 1,
            'status'             => 'active',
        ]);

        return PropertyLeaseSchedule::create([
            'property_lease_id' => $lease->id,
            'installment_no'    => 1,
            'due_date'          => now()->addDays(10)->toDateString(),
            'amount'            => 120000,
            'paid_amount'       => 0,
            'remaining_amount'  => 120000,
            'status'            => 'pending',
        ]);
    }

    private function createProperty(array $attributes = []): Property
    {
        $company = Company::create([
            'code'   => 'COMP-ARCH-'.random_int(1000, 9999),
            'name'   => 'Archive Test Company',
            'status' => 'active',
        ]);

        return Property::create(array_merge([
            'company_id'     => $company->id,
            'code'           => 'PROP-ARCH-'.random_int(1000, 9999),
            'name'           => 'Archive Test Property',
            'type'           => 'residential',
            'ownership_type' => 'owned',
            'status'         => 'active',
        ], $attributes));
    }
}
