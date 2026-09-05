<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use App\Domains\Notification\Models\Notification;
use App\Domains\Notification\Services\NotificationSyncService;
use Illuminate\Support\Facades\Cache;

class NotificationCenter extends Component
{
    /**
     * المزامنة عملية كتابة ثقيلة: تُحدّث حالات الاستحقاقات في قاعدة البيانات
     * وتُنشئ مئات التنبيهات. لذلك لا تعمل تلقائياً أثناء render — تعمل عبر
     * جدولة الـ cron كل ساعة، أو بضغطة صريحة من المستخدم هنا.
     */
    public function syncNow(): void
    {
        $lock = Cache::lock('notifications_sync_lock', 300);

        if (! $lock->get()) {
            $this->dispatch('notify', message: 'المزامنة قيد التنفيذ بالفعل — أعد المحاولة بعد قليل');

            return;
        }

        try {
            app(NotificationSyncService::class)->sync();
            $this->dispatch('notify', message: 'تم تحديث التنبيهات');
        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        $d30 = now()->addDays(30)->endOfDay();
        $d60 = now()->addDays(60)->endOfDay();
        $d90 = now()->addDays(90)->endOfDay();

        // Portable severity sort (works on MySQL and SQLite)
        $severityOrder = "CASE severity WHEN 'danger' THEN 0 WHEN 'warning' THEN 1 WHEN 'info' THEN 2 ELSE 9 END";

        $groups = [
            30 => Notification::open()
                ->where('trigger_date', '<=', $d30)
                ->orderByRaw($severityOrder)
                ->orderBy('trigger_date')
                ->get(),

            60 => Notification::open()
                ->whereBetween('trigger_date', [$d30->copy()->addSecond(), $d60])
                ->orderByRaw($severityOrder)
                ->orderBy('trigger_date')
                ->get(),

            90 => Notification::open()
                ->whereBetween('trigger_date', [$d60->copy()->addSecond(), $d90])
                ->orderByRaw($severityOrder)
                ->orderBy('trigger_date')
                ->get(),
        ];

        $totalOpen = Notification::open()->count();
        $lastSync  = Cache::get(NotificationSyncService::LAST_SYNC_KEY);

        return view('livewire.notifications.notification-center', compact('groups', 'totalOpen', 'lastSync'))
            ->layout('layouts.app', ['title' => 'مركز التنبيهات']);
    }
}
