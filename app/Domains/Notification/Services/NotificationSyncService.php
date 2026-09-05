<?php

namespace App\Domains\Notification\Services;

use App\Domains\Notification\Models\Notification;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Contract\Models\Contract;
use App\Domains\Unit\Models\Unit;
use App\Domains\Property\Models\PropertyLease;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use App\Enums\PaymentScheduleStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class NotificationSyncService
{
    /** مفتاح الكاش الذي يحمل وقت آخر مزامنة ناجحة (يقرأه مركز التنبيهات للعرض) */
    public const LAST_SYNC_KEY = 'notifications_last_sync';

    /**
     * بعد كم ساعة تُعتبر البيانات قديمة. الجدولة كل ساعة، فثلاث ساعات
     * تعني أن تشغيلين متتاليين فُوِّتا — إشارة حقيقية لا إنذار كاذب.
     */
    public const STALE_AFTER_HOURS = 3;

    public static function lastSyncedAt(): ?Carbon
    {
        $value = Cache::get(self::LAST_SYNC_KEY);

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * هل توقفت المزامنة؟
     *
     * يُحسب أثناء عرض الصفحة — أي في عملية الويب لا في الـ cron. وهذا مقصود:
     * أي فحص يعمل عبر الجدولة نفسها سيتوقف بصمت مع توقفها، فلا يكتشف عطلها أبداً.
     */
    public static function isStale(): bool
    {
        $last = self::lastSyncedAt();

        return $last === null || $last->lt(now()->subHours(self::STALE_AFTER_HOURS));
    }

    public function sync(): void
    {
        $this->purgeOrphans();
        $this->markOverdueStatuses();
        $this->syncOverduePayments();
        $this->syncUpcomingTenantPayments();
        $this->syncExpiringContracts();
        $this->syncVacantUnits();
        $this->syncPropertyLeasePayments();
        $this->syncExpiringPropertyLeases();

        Cache::forever(self::LAST_SYNC_KEY, now()->toDateTimeString());
    }

    // ─── حذف التنبيهات التي اختفى مصدرها ───────────────────────────
    // التنظيف داخل كل دالة أدناه يعتمد على whereHasMorph، وهو لا يطابق أبداً
    // عندما يُحذف صف المصدر نفسه — وهذا يحدث كلما أُعيد توليد جدولة عقد
    // (syncLeaseSchedules يحذف الجدولات القديمة وينشئ غيرها)، فتبقى تنبيهاتها
    // معلّقة للأبد. whereDoesntHaveMorph هو النقيض الدقيق الذي يلتقطها.
    //
    // ملاحظة أمان: كل نوع يُطابَق بشرط `morph_type = X AND NOT EXISTS(...)`،
    // فالتنبيهات ذات نوع خارج هذه القائمة لا تُحذف إطلاقاً.
    protected function purgeOrphans(): void
    {
        Notification::whereDoesntHaveMorph('notifiableSource', [
            PaymentSchedule::class,
            PropertyLeaseSchedule::class,
            Contract::class,
            PropertyLease::class,
            Unit::class,
        ])->delete();
    }

    // ─── تحديث حالة الاستحقاقات المتأخرة ──────────────────────────
    public function markOverdueStatuses(): void
    {
        // دفعات المستأجرين — كل ما فات تاريخه وما زال غير مدفوع
        PaymentSchedule::query()
            ->whereNotIn('status', [
                PaymentScheduleStatus::Paid->value,
                PaymentScheduleStatus::Cancelled->value,
            ])
            ->where('remaining_amount', '>', 0)
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => PaymentScheduleStatus::Overdue->value]);

        // دفعات الملاك — نفس المنطق
        PropertyLeaseSchedule::query()
            ->where('status', '!=', 'paid')
            ->where('remaining_amount', '>', 0)
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }

    // ─── دفعات المستأجرين المتأخرة ──────────────────────────────────
    protected function syncOverduePayments(): void
    {
        $overdue = PaymentSchedule::query()
            ->where('status', 'overdue')
            ->where('remaining_amount', '>', 0)
            ->whereHas('contract', fn ($q) => $q->notArchived())
            ->with('contract.tenant')
            ->get();

        foreach ($overdue as $schedule) {
            Notification::updateOrCreate(
                ['notifiable_source_type' => PaymentSchedule::class, 'notifiable_source_id' => $schedule->id, 'type' => 'payment_overdue'],
                [
                    'severity'     => 'danger',
                    'title'        => 'دفعة متأخرة',
                    'message'      => 'الدفعة رقم '.$schedule->installment_no.' بمبلغ '.number_format($schedule->remaining_amount, 0).' ر.س للمستأجر '.($schedule->contract?->tenant?->name ?? '—').' — استحقاق '.$schedule->due_date?->format('Y/m/d'),
                    'trigger_date' => $schedule->due_date,
                    'status'       => 'open',
                    'payload'      => ['contract_id' => $schedule->contract_id, 'remaining' => $schedule->remaining_amount],
                ]
            );
        }

        Notification::where('type', 'payment_overdue')->where('status', 'open')
            ->whereHasMorph('notifiableSource', [PaymentSchedule::class], fn ($q) => $q
                ->where('status', 'paid')
                ->orWhere('status', PaymentScheduleStatus::Cancelled->value)
                ->orWhere('remaining_amount', '<=', 0)
                ->orWhereHas('contract', fn ($c) => $c->whereNotNull('archived_at'))
            )
            ->delete();
    }

    // ─── دفعات المستأجرين القادمة (خلال 90 يوماً) ──────────────────
    protected function syncUpcomingTenantPayments(): void
    {
        $upcoming = PaymentSchedule::query()
            ->whereIn('status', [
                PaymentScheduleStatus::Pending->value,
                PaymentScheduleStatus::Due->value,
                PaymentScheduleStatus::Partial->value,
            ])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(90)->toDateString()])
            ->where('remaining_amount', '>', 0)
            ->whereHas('contract', fn ($q) => $q->notArchived())
            ->with('contract.tenant')
            ->get();

        foreach ($upcoming as $schedule) {
            $daysLeft = (int) now()->diffInDays($schedule->due_date, false);
            $severity = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'info');

            Notification::updateOrCreate(
                ['notifiable_source_type' => PaymentSchedule::class, 'notifiable_source_id' => $schedule->id, 'type' => 'payment_due'],
                [
                    'severity'     => $severity,
                    'title'        => 'دفعة مستأجر مستحقة',
                    'message'      => 'الدفعة رقم '.$schedule->installment_no.' بمبلغ '.number_format($schedule->remaining_amount, 0).' ر.س للمستأجر '.($schedule->contract?->tenant?->name ?? '—').' — استحقاق '.$schedule->due_date?->format('Y/m/d').' (خلال '.$daysLeft.' يوم)',
                    'trigger_date' => $schedule->due_date,
                    'status'       => 'open',
                    'payload'      => ['contract_id' => $schedule->contract_id, 'remaining' => $schedule->remaining_amount, 'days_left' => $daysLeft],
                ]
            );
        }

        // حذف التنبيهات التي سُددت أو ألغيت أو أصبحت متأخرة أو أُرشف عقدها
        Notification::where('type', 'payment_due')->where('status', 'open')
            ->whereHasMorph('notifiableSource', [PaymentSchedule::class], fn ($q) => $q
                ->where('status', 'paid')
                ->orWhere('status', PaymentScheduleStatus::Cancelled->value)
                ->orWhere('remaining_amount', '<=', 0)
                ->orWhere('due_date', '<', now()->toDateString())
                ->orWhereHas('contract', fn ($c) => $c->whereNotNull('archived_at'))
            )
            ->delete();
    }

    // ─── عقود المستأجرين المنتهية خلال 90 يوماً ─────────────────────
    protected function syncExpiringContracts(): void
    {
        $expiring = Contract::query()
            ->notArchived()
            ->where('status', 'active')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(90)->toDateString()])
            ->with('tenant', 'unit')
            ->get();

        foreach ($expiring as $contract) {
            $daysLeft = (int) now()->diffInDays($contract->end_date, false);
            $severity = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'info');

            Notification::updateOrCreate(
                ['notifiable_source_type' => Contract::class, 'notifiable_source_id' => $contract->id, 'type' => 'contract_expiring'],
                [
                    'severity'     => $severity,
                    'title'        => 'عقد مستأجر ينتهي قريباً',
                    'message'      => 'عقد المستأجر '.($contract->tenant?->name ?? '—').' للوحدة '.($contract->unit?->name ?? '—').' ينتهي خلال '.$daysLeft.' يوم ('.$contract->end_date->format('Y/m/d').')',
                    'trigger_date' => $contract->end_date,
                    'status'       => 'open',
                    'payload'      => ['days_left' => $daysLeft, 'end_date' => $contract->end_date->toDateString()],
                ]
            );
        }

        Notification::where('type', 'contract_expiring')->where('status', 'open')
            ->whereHasMorph('notifiableSource', [Contract::class], fn ($q) => $q
                ->whereIn('status', ['ended', 'early_ended', 'cancelled', 'renewed'])
                ->orWhere('end_date', '<', now()->toDateString())
                ->orWhereNotNull('archived_at'))
            ->delete();
    }

    // ─── وحدات شاغرة ───────────────────────────────────────────────
    protected function syncVacantUnits(): void
    {
        foreach (Unit::notArchived()
            ->where('status', 'vacant')
            ->whereHas('property', fn ($q) => $q->notArchived())
            ->with('property')
            ->get() as $unit) {
            Notification::updateOrCreate(
                ['notifiable_source_type' => Unit::class, 'notifiable_source_id' => $unit->id, 'type' => 'unit_vacant'],
                [
                    'severity'     => 'info',
                    'title'        => 'وحدة شاغرة',
                    'message'      => 'الوحدة '.$unit->name.' في '.($unit->property?->name ?? '—').' شاغرة وجاهزة للتأجير',
                    'trigger_date' => now()->toDateString(),
                    'status'       => 'open',
                    'payload'      => ['property_name' => $unit->property?->name],
                ]
            );
        }

        Notification::where('type', 'unit_vacant')->where('status', 'open')
            ->whereHasMorph('notifiableSource', [Unit::class], fn ($q) => $q
                ->where('status', 'rented')
                ->orWhereNotNull('archived_at')
                ->orWhereHas('property', fn ($q) => $q->whereNotNull('archived_at')))
            ->delete();
    }

    // ─── دفعات الملاك خلال 90 يوماً ────────────────────────────────
    protected function syncPropertyLeasePayments(): void
    {
        $due = PropertyLeaseSchedule::query()
            ->whereIn('status', ['overdue', 'pending', 'partial'])
            ->where('due_date', '<=', now()->addDays(90)->toDateString())
            ->where('remaining_amount', '>', 0)
            ->whereHas('lease', fn ($q) => $q
                ->notArchived()
                ->whereHas('property', fn ($p) => $p->notArchived()))
            ->with('lease.property')
            ->get();

        foreach ($due as $schedule) {
            $daysLeft  = (int) now()->diffInDays($schedule->due_date, false);
            $isOverdue = $schedule->due_date->isPast();
            $severity  = $isOverdue ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'info');

            Notification::updateOrCreate(
                ['notifiable_source_type' => PropertyLeaseSchedule::class, 'notifiable_source_id' => $schedule->id, 'type' => 'lease_payment_due'],
                [
                    'severity'     => $severity,
                    'title'        => $isOverdue ? 'دفعة إيجار مالك متأخرة' : 'دفعة إيجار مالك مستحقة',
                    'message'      => 'دفعة رقم '.$schedule->installment_no.' بمبلغ '.number_format($schedule->remaining_amount, 0).' ر.س لمالك عقار '.($schedule->lease?->property?->name ?? '—').' ('.($schedule->lease?->owner_name ?? '').')'.' — استحقاق '.$schedule->due_date->format('Y/m/d'),
                    'trigger_date' => $schedule->due_date,
                    'status'       => 'open',
                    'payload'      => ['property_id' => $schedule->lease?->property_id, 'owner_name' => $schedule->lease?->owner_name, 'remaining' => $schedule->remaining_amount],
                ]
            );
        }

        Notification::where('type', 'lease_payment_due')->where('status', 'open')
            ->whereHasMorph('notifiableSource', [PropertyLeaseSchedule::class], fn ($q) => $q
                ->where('status', 'paid')
                ->orWhere('remaining_amount', '<=', 0)
                ->orWhereHas('lease', fn ($l) => $l->whereNotNull('archived_at'))
                ->orWhereHas('lease.property', fn ($p) => $p->whereNotNull('archived_at'))
            )
            ->delete();
    }

    // ─── عقود إيجار العقارات المنتهية خلال 90 يوماً ─────────────────
    protected function syncExpiringPropertyLeases(): void
    {
        $expiring = PropertyLease::query()
            ->notArchived()
            ->whereHas('property', fn ($q) => $q->notArchived())
            ->where('status', 'active')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(90)->toDateString()])
            ->with('property')
            ->get();

        foreach ($expiring as $lease) {
            $daysLeft = (int) now()->diffInDays($lease->end_date, false);
            $severity = $daysLeft <= 30 ? 'danger' : ($daysLeft <= 60 ? 'warning' : 'info');

            Notification::updateOrCreate(
                ['notifiable_source_type' => PropertyLease::class, 'notifiable_source_id' => $lease->id, 'type' => 'property_lease_expiring'],
                [
                    'severity'     => $severity,
                    'title'        => 'عقد إيجار عقار ينتهي قريباً',
                    'message'      => 'عقد إيجار عقار '.($lease->property?->name ?? '—').' من المالك '.($lease->owner_name).' ينتهي خلال '.$daysLeft.' يوم ('.$lease->end_date->format('Y/m/d').')',
                    'trigger_date' => $lease->end_date,
                    'status'       => 'open',
                    'payload'      => ['days_left' => $daysLeft, 'owner_name' => $lease->owner_name],
                ]
            );
        }

        Notification::where('type', 'property_lease_expiring')->where('status', 'open')
            ->whereHasMorph('notifiableSource', [PropertyLease::class], fn ($q) => $q
                ->whereIn('status', ['ended', 'cancelled'])
                ->orWhere('end_date', '<', now()->toDateString())
                ->orWhereNotNull('archived_at'))
            ->delete();
    }
}
