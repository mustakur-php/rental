<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use App\Domains\Notification\Models\Notification;
use App\Domains\Notification\Services\NotificationSyncService;
use Illuminate\Support\Facades\Cache;

class NotificationCenter extends Component
{
    public function mount(): void
    {
        Cache::remember('notifications_last_sync', now()->addMinutes(15), function () {
            app(NotificationSyncService::class)->sync();
            return now()->toDateTimeString();
        });
    }

    public function render()
    {
        $d30 = now()->addDays(30)->endOfDay();
        $d60 = now()->addDays(60)->endOfDay();
        $d90 = now()->addDays(90)->endOfDay();

        // ترتيب الخطورة: danger أولاً ثم warning ثم info
        $severityOrder = ['danger' => 0, 'warning' => 1, 'info' => 2];

        $sortFn = fn ($a, $b) =>
            ($severityOrder[$a->severity] ?? 9) <=> ($severityOrder[$b->severity] ?? 9)
            ?: $a->trigger_date <=> $b->trigger_date;

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

        return view('livewire.notifications.notification-center', compact('groups', 'totalOpen'))
            ->layout('layouts.app', ['title' => 'مركز التنبيهات']);
    }
}
