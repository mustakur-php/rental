<?php

namespace Tests\Feature\Notifications;

use App\Domains\Notification\Models\Notification;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Livewire\Dashboard\MainDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_are_grouped_by_period_including_overdue(): void
    {
        $this->loginAsSuperAdmin();

        $this->makeNotification('payment_overdue', now()->subDays(30));
        $this->makeNotification('payment_due', now()->addDays(10));
        $this->makeNotification('lease_payment_due', now()->addDays(45));
        $this->makeNotification('unit_vacant', now()->addDays(75));
        $this->makeNotification('contract_expiring', now()->addDays(10));

        $alerts = Livewire::test(MainDashboard::class)->get('kpis')['alerts'];

        $this->assertSame(1, $alerts['overdue']['tenant_payments']);
        $this->assertSame(1, $alerts[30]['tenant_payments']);
        $this->assertSame(1, $alerts[30]['contracts']);
        $this->assertSame(1, $alerts[60]['lease_payments']);
        $this->assertSame(1, $alerts[90]['vacant_units']);
    }

    public function test_snoozed_notifications_are_hidden_from_the_dashboard(): void
    {
        $this->loginAsSuperAdmin();

        $notification = $this->makeNotification('payment_overdue', now()->subDays(30));

        $before = Livewire::test(MainDashboard::class)->get('kpis')['alerts'];
        $this->assertSame(1, $before['overdue']['tenant_payments']);

        // التأجيل في مركز التنبيهات يجب أن ينعكس على اللوحة أيضاً — كانت اللوحة
        // تستعلم من جداول المصدر فلا ترى التأجيل إطلاقاً.
        $notification->update(['snoozed_until' => now()->addDays(7)]);

        $after = Livewire::test(MainDashboard::class)->get('kpis')['alerts'];
        $this->assertSame(0, $after['overdue']['tenant_payments']);
    }

    public function test_owner_contract_expiry_counts_with_tenant_contract_expiry(): void
    {
        $this->loginAsSuperAdmin();

        $this->makeNotification('contract_expiring', now()->addDays(5));
        $this->makeNotification('property_lease_expiring', now()->addDays(5));

        $alerts = Livewire::test(MainDashboard::class)->get('kpis')['alerts'];

        $this->assertSame(2, $alerts[30]['contracts']);
    }

    private function makeNotification(string $type, \DateTimeInterface $triggerDate): Notification
    {
        return Notification::create([
            'notifiable_source_type' => PaymentSchedule::class,
            'notifiable_source_id'   => random_int(100_000, 999_999),
            'type'                   => $type,
            'severity'               => 'warning',
            'title'                  => 'تنبيه اختبار',
            'message'                => 'تفاصيل',
            'trigger_date'           => $triggerDate,
            'status'                 => 'open',
        ]);
    }
}
