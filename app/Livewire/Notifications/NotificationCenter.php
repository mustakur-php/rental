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
        $today = now()->startOfDay();
        $d30   = now()->addDays(30)->endOfDay();
        $d60   = now()->addDays(60)->endOfDay();
        $d90   = now()->addDays(90)->endOfDay();

        // Portable severity sort (works on MySQL and SQLite)
        $severityOrder = "CASE severity WHEN 'danger' THEN 0 WHEN 'warning' THEN 1 WHEN 'info' THEN 2 ELSE 9 END";

        $ordered = fn () => Notification::open()
            ->orderByRaw($severityOrder)
            ->orderBy('trigger_date');

        // "متأخر" مجموعة مستقلة: كانت مدمجة داخل مجموعة الـ30 يوم لأن شرطها
        // كان `<= اليوم+30` بلا حد أدنى، فصارت تضم كل ما فات منذ سنوات تحت
        // عنوان "خلال 30 يوم".
        $groups = [
            'overdue' => $ordered()->where('trigger_date', '<', $today)->get(),
            30        => $ordered()->whereBetween('trigger_date', [$today, $d30])->get(),
            60        => $ordered()->whereBetween('trigger_date', [$d30->copy()->addSecond(), $d60])->get(),
            90        => $ordered()->whereBetween('trigger_date', [$d60->copy()->addSecond(), $d90])->get(),
        ];

        $totalOpen = Notification::open()->count();
        $lastSync  = Cache::get(NotificationSyncService::LAST_SYNC_KEY);

        return view('livewire.notifications.notification-center', compact('groups', 'totalOpen', 'lastSync'))
            ->layout('layouts.app', ['title' => 'مركز التنبيهات']);
    }
}
