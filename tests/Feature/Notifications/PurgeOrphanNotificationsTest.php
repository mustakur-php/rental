<?php

namespace Tests\Feature\Notifications;

use App\Domains\Company\Models\Company;
use App\Domains\Notification\Models\Notification;
use App\Domains\Notification\Services\NotificationSyncService;
use App\Domains\Property\Models\Property;
use App\Domains\Property\Models\PropertyLease;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeOrphanNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_deletes_notifications_whose_source_row_no_longer_exists(): void
    {
        $schedule = $this->createLeaseSchedule();

        $valid  = $this->createNotification(PropertyLeaseSchedule::class, $schedule->id);
        $orphan = $this->createNotification(PropertyLeaseSchedule::class, $schedule->id + 9_999);

        app(NotificationSyncService::class)->sync();

        $this->assertModelExists($valid);
        $this->assertModelMissing($orphan);
    }

    public function test_sync_deletes_orphans_left_behind_when_schedules_are_regenerated(): void
    {
        $schedule = $this->createLeaseSchedule();
        $notification = $this->createNotification(PropertyLeaseSchedule::class, $schedule->id);

        // هذا ما يحدث فعلياً عند تعديل عقد مالك: تُحذف الجدولات ويُعاد توليدها
        $schedule->delete();

        app(NotificationSyncService::class)->sync();

        $this->assertModelMissing($notification);
    }

    public function test_sync_keeps_notifications_whose_source_type_is_not_managed(): void
    {
        // كل نوع يُطابَق بشرط `morph_type = X AND NOT EXISTS(...)`،
        // فالأنواع خارج القائمة يجب ألا تُمَس مهما كان معرّف المصدر.
        $foreign = Notification::create([
            'notifiable_source_type' => 'App\\Domains\\Legacy\\Models\\Whatever',
            'notifiable_source_id'   => 12_345,
            'type'                   => 'legacy_alert',
            'severity'               => 'info',
            'title'                  => 'تنبيه من نظام قديم',
            'trigger_date'           => now()->toDateString(),
            'status'                 => 'open',
        ]);

        app(NotificationSyncService::class)->sync();

        $this->assertModelExists($foreign);
    }

    private function createLeaseSchedule(): PropertyLeaseSchedule
    {
        $company = Company::create([
            'code'   => 'COMP-NOTIF',
            'name'   => 'Notification Company',
            'status' => 'active',
        ]);

        $property = Property::create([
            'company_id'     => $company->id,
            'code'           => 'PROP-NOTIF-001',
            'name'           => 'Notification Property',
            'type'           => 'residential',
            'ownership_type' => 'leased',
            'status'         => 'active',
        ]);

        $lease = PropertyLease::create([
            'property_id'        => $property->id,
            'owner_name'         => 'Owner Notif',
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

        // مستحقة بعد 10 أيام: تبقى pending ولا يحوّلها markOverdueStatuses إلى overdue
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

    private function createNotification(string $sourceType, int $sourceId): Notification
    {
        return Notification::create([
            'notifiable_source_type' => $sourceType,
            'notifiable_source_id'   => $sourceId,
            'type'                   => 'lease_payment_due',
            'severity'               => 'warning',
            'title'                  => 'دفعة إيجار مالك مستحقة',
            'trigger_date'           => now()->addDays(10)->toDateString(),
            'status'                 => 'open',
        ]);
    }
}
