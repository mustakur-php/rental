<?php

namespace App\Livewire\Notifications;

use App\Domains\Contract\Models\Contract;
use App\Domains\Notification\Models\Notification;
use App\Domains\Notification\Services\NotificationSyncService;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Property\Models\Property;
use App\Domains\Property\Models\PropertyLease;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use App\Domains\Unit\Models\Unit;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use WithPagination;

    /** كم يوماً يختفي التنبيه عند التأجيل قبل أن يعود إن بقي سببه قائماً */
    public const SNOOZE_DAYS = 7;

    public string $period   = 'all';   // all | overdue | 30 | 60 | 90 | snoozed
    public string $type     = '';
    public string $severity = '';
    public string $property = '';
    public string $search   = '';

    public function updatingPeriod(): void   { $this->resetPage(); }
    public function updatingType(): void     { $this->resetPage(); }
    public function updatingSeverity(): void { $this->resetPage(); }
    public function updatingProperty(): void { $this->resetPage(); }
    public function updatingSearch(): void   { $this->resetPage(); }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['period', 'type', 'severity', 'property', 'search']);
        $this->resetPage();
    }

    public function snooze(int $notificationId): void
    {
        Notification::whereKey($notificationId)
            ->update(['snoozed_until' => now()->addDays(self::SNOOZE_DAYS)]);

        $this->dispatch('notify', message: 'تم تأجيل التنبيه '.self::SNOOZE_DAYS.' أيام');
    }

    public function unsnooze(int $notificationId): void
    {
        Notification::whereKey($notificationId)->update(['snoozed_until' => null]);

        $this->dispatch('notify', message: 'تم إلغاء التأجيل');
    }

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

    /** كل الفلاتر عدا الفترة — تُبنى مرة وتُعاد لكل عدّاد ولقائمة النتائج */
    private function filtered()
    {
        return Notification::query()
            ->open()
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->severity, fn ($q) => $q->where('severity', $this->severity))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('message', 'like', '%'.$this->search.'%')))
            ->when($this->property, fn ($q) => $this->scopeToProperty($q));
    }

    /**
     * التنبيه لا يحمل property_id، والمصدر يختلف حسب النوع — فنصل إلى العقار
     * عبر علاقة كل نوع على حدة. يُطبَّق فقط عند اختيار عقار.
     */
    private function scopeToProperty($query)
    {
        return $query->where(fn ($q) => $q
            ->whereHasMorph('notifiableSource', [PropertyLeaseSchedule::class],
                fn ($s) => $s->whereHas('lease', fn ($l) => $l->where('property_id', $this->property)))
            ->orWhereHasMorph('notifiableSource', [PaymentSchedule::class],
                fn ($s) => $s->whereHas('contract.unit', fn ($u) => $u->where('property_id', $this->property)))
            ->orWhereHasMorph('notifiableSource', [Contract::class],
                fn ($s) => $s->whereHas('unit', fn ($u) => $u->where('property_id', $this->property)))
            ->orWhereHasMorph('notifiableSource', [Unit::class],
                fn ($s) => $s->where('property_id', $this->property))
            ->orWhereHasMorph('notifiableSource', [PropertyLease::class],
                fn ($s) => $s->where('property_id', $this->property)));
    }

    private function applyPeriod($query, string $period)
    {
        $today = now()->startOfDay();
        $d30   = now()->addDays(30)->endOfDay();
        $d60   = now()->addDays(60)->endOfDay();
        $d90   = now()->addDays(90)->endOfDay();

        return match ($period) {
            'snoozed' => $query->snoozed(),
            'overdue' => $query->visible()->where('trigger_date', '<', $today),
            '30'      => $query->visible()->whereBetween('trigger_date', [$today, $d30]),
            '60'      => $query->visible()->whereBetween('trigger_date', [$d30->copy()->addSecond(), $d60]),
            '90'      => $query->visible()->whereBetween('trigger_date', [$d60->copy()->addSecond(), $d90]),
            default   => $query->visible(),
        };
    }

    public function render()
    {
        // Portable severity sort (works on MySQL and SQLite)
        $severityOrder = "CASE severity WHEN 'danger' THEN 0 WHEN 'warning' THEN 1 WHEN 'info' THEN 2 ELSE 9 END";

        $counts = [];
        foreach (['all', 'overdue', '30', '60', '90', 'snoozed'] as $period) {
            $counts[$period] = $this->applyPeriod($this->filtered(), $period)->count();
        }

        $notifications = $this->applyPeriod($this->filtered(), $this->period)
            ->orderByRaw($severityOrder)
            ->orderBy('trigger_date')
            ->paginate(25);

        return view('livewire.notifications.notification-center', [
            'notifications' => $notifications,
            'counts'        => $counts,
            'properties'    => Property::notArchived()->orderBy('name')->get(['id', 'name']),
            'lastSync'      => Cache::get(NotificationSyncService::LAST_SYNC_KEY),
        ])->layout('layouts.app', ['title' => 'مركز التنبيهات']);
    }
}
