<?php

namespace Tests\Feature\Notifications;

use App\Domains\Company\Models\Company;
use App\Domains\Notification\Models\Notification;
use App\Domains\Notification\Services\NotificationSyncService;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Property\Models\Property;
use App\Domains\Property\Models\PropertyLease;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use App\Livewire\Notifications\NotificationCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCenterFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_tabs_split_overdue_from_upcoming(): void
    {
        $this->loginAsSuperAdmin();

        $overdue  = $this->makeNotification('دفعة فائتة', now()->subDays(20));
        $soon     = $this->makeNotification('دفعة قريبة', now()->addDays(10));
        $far      = $this->makeNotification('دفعة بعيدة', now()->addDays(75));

        Livewire::test(NotificationCenter::class)
            ->assertSee($overdue->title)->assertSee($soon->title)->assertSee($far->title)
            ->set('period', 'overdue')
            ->assertSee($overdue->title)->assertDontSee($soon->title)->assertDontSee($far->title)
            ->set('period', '30')
            ->assertSee($soon->title)->assertDontSee($overdue->title)->assertDontSee($far->title)
            ->set('period', '90')
            ->assertSee($far->title)->assertDontSee($overdue->title);
    }

    public function test_search_matches_title_and_message(): void
    {
        $this->loginAsSuperAdmin();

        $match = $this->makeNotification('دفعة متأخرة', now()->subDay(), 'المستأجر خالد العتيبي');
        $other = $this->makeNotification('وحدة شاغرة', now()->subDay(), 'الوحدة A-12 جاهزة');

        Livewire::test(NotificationCenter::class)
            ->set('search', 'خالد')
            ->assertSee($match->message)
            ->assertDontSee($other->message);
    }

    public function test_severity_filter_narrows_the_list(): void
    {
        $this->loginAsSuperAdmin();

        $danger = $this->makeNotification('عاجل جداً', now()->subDay(), severity: 'danger');
        $info   = $this->makeNotification('معلومة فقط', now()->subDay(), severity: 'info');

        Livewire::test(NotificationCenter::class)
            ->set('severity', 'danger')
            ->assertSee($danger->title)
            ->assertDontSee($info->title);
    }

    public function test_snoozing_hides_a_notification_and_unsnoozing_brings_it_back(): void
    {
        $this->loginAsSuperAdmin();

        $notification = $this->makeNotification('دفعة مؤجلة', now()->subDays(5));

        Livewire::test(NotificationCenter::class)
            ->assertSee($notification->title)
            ->call('snooze', $notification->id)
            ->assertDontSee($notification->title)
            ->set('period', 'snoozed')
            ->assertSee($notification->title)
            ->call('unsnooze', $notification->id)
            ->set('period', 'all')
            ->assertSee($notification->title);

        $this->assertNull($notification->fresh()->snoozed_until);
    }

    public function test_a_snooze_expires_on_its_own(): void
    {
        $notification = $this->makeNotification('دفعة عادت', now()->subDays(5));

        $notification->update(['snoozed_until' => now()->addDay()]);
        $this->assertSame(0, Notification::visible()->count());

        $notification->update(['snoozed_until' => now()->subMinute()]);
        $this->assertSame(1, Notification::visible()->count());
    }

    public function test_sync_does_not_wake_a_snoozed_notification(): void
    {
        // مصدر حقيقي: لولاه لحذف purgeOrphans التنبيه فلا يختبر شيئاً
        $schedule = $this->createRealSchedule();

        app(NotificationSyncService::class)->sync();

        $notification = Notification::where('notifiable_source_id', $schedule->id)->firstOrFail();
        $notification->update(['snoozed_until' => now()->addDays(7)]);

        app(NotificationSyncService::class)->sync();

        $this->assertNotNull($notification->fresh()->snoozed_until, 'المزامنة أعادت تنبيهاً مؤجَّلاً');
        $this->assertSame(0, Notification::visible()->count());
    }

    private function createRealSchedule(): PropertyLeaseSchedule
    {
        $company = Company::create(['code' => 'COMP-SNZ', 'name' => 'Snooze Co', 'status' => 'active']);

        $property = Property::create([
            'company_id'     => $company->id,
            'code'           => 'PROP-SNZ',
            'name'           => 'Snooze Property',
            'type'           => 'residential',
            'ownership_type' => 'leased',
            'status'         => 'active',
        ]);

        $lease = PropertyLease::create([
            'property_id'        => $property->id,
            'owner_name'         => 'Snooze Owner',
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

    private function makeNotification(
        string $title,
        \DateTimeInterface $triggerDate,
        string $message = 'تفاصيل التنبيه',
        string $severity = 'warning',
    ): Notification {
        return Notification::create([
            'notifiable_source_type' => PaymentSchedule::class,
            'notifiable_source_id'   => random_int(100_000, 999_999),
            'type'                   => 'payment_due',
            'severity'               => $severity,
            'title'                  => $title,
            'message'                => $message,
            'trigger_date'           => $triggerDate,
            'status'                 => 'open',
        ]);
    }
}
