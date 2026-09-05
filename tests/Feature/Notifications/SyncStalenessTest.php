<?php

namespace Tests\Feature\Notifications;

use App\Domains\Notification\Services\NotificationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SyncStalenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_is_stale_when_it_has_never_run(): void
    {
        Cache::forget(NotificationSyncService::LAST_SYNC_KEY);

        $this->assertNull(NotificationSyncService::lastSyncedAt());
        $this->assertTrue(NotificationSyncService::isStale());
    }

    public function test_sync_is_not_stale_right_after_running(): void
    {
        app(NotificationSyncService::class)->sync();

        $this->assertNotNull(NotificationSyncService::lastSyncedAt());
        $this->assertFalse(NotificationSyncService::isStale());
    }

    public function test_sync_becomes_stale_once_the_threshold_passes(): void
    {
        // ساعة واحدة فقط: تشغيل واحد فُوِّت — لا إنذار
        Cache::forever(
            NotificationSyncService::LAST_SYNC_KEY,
            now()->subHours(NotificationSyncService::STALE_AFTER_HOURS - 1)->toDateTimeString()
        );
        $this->assertFalse(NotificationSyncService::isStale());

        // تجاوز الحد: الجدولة متوقفة فعلاً
        Cache::forever(
            NotificationSyncService::LAST_SYNC_KEY,
            now()->subHours(NotificationSyncService::STALE_AFTER_HOURS + 1)->toDateTimeString()
        );
        $this->assertTrue(NotificationSyncService::isStale());
    }

    public function test_dashboard_shows_the_warning_only_when_sync_is_stale(): void
    {
        $this->loginAsSuperAdmin();

        Cache::forever(NotificationSyncService::LAST_SYNC_KEY, now()->toDateTimeString());
        $this->get(route('dashboard'))->assertOk()->assertDontSee('لم تتم مزامنة التنبيهات', false);

        Cache::forever(
            NotificationSyncService::LAST_SYNC_KEY,
            now()->subHours(NotificationSyncService::STALE_AFTER_HOURS + 1)->toDateTimeString()
        );
        $this->get(route('dashboard'))->assertOk()->assertSee('لم تتم مزامنة التنبيهات', false);
    }
}
