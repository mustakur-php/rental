<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Domains\Property\Models\Property;
use App\Domains\Unit\Models\Unit;
use App\Domains\Contract\Models\Contract;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Maintenance\Models\MaintenanceRequest;
use App\Domains\Notification\Models\Notification;
use App\Domains\Property\Models\PropertyLeaseSchedule;

class MainDashboard extends Component
{
    public array $kpis         = [];
    public array $incomeChart  = [];   // دفعات المستأجرين
    public array $leaseChart   = [];   // دفعات الملاك
    public array $unitsChart   = [];   // حالة الوحدات

    public function mount(): void
    {
        // لا تُحدَّث حالات التأخير هنا: عرض صفحة يجب ألا يُعدّل بيانات مالية.
        // notifications:sync يتكفّل بذلك كل ساعة، و<x-sync-stale-warning> يحذّر إن توقف.
        $this->kpis = [
            'properties'        => Property::notArchived()->count(),
            'units'             => Unit::notArchived()->whereHas('property', fn ($q) => $q->notArchived())->count(),
            'rented_units'      => Unit::notArchived()->whereHas('property', fn ($q) => $q->notArchived())->where('status', 'rented')->count(),
            'vacant_units'      => Unit::notArchived()->whereHas('property', fn ($q) => $q->notArchived())->where('status', 'vacant')->count(),
            'active_contracts'  => Contract::notArchived()->where('status', 'active')->count(),
            'overdue_schedules' => Notification::query()
                ->where('status', 'open')
                ->whereIn('type', ['payment_overdue', 'lease_payment_due'])
                ->where('severity', 'danger')
                ->count(),
            'maintenance_open'  => MaintenanceRequest::whereIn('status', ['new', 'in_progress'])->count(),
        ];

        // ─── بيانات الدخل الشهري — آخر 6 أشهر ───────────
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        $labels   = [];
        $seriesDue  = [];
        $seriesPaid = [];

        foreach ($months as $month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();

            $labels[]     = $month->locale('ar')->isoFormat('MMM YY');
            $seriesDue[]  = (float) PaymentSchedule::whereBetween('due_date', [$start, $end])->sum('total_amount');
            $seriesPaid[] = (float) PaymentSchedule::whereBetween('due_date', [$start, $end])->sum('paid_amount');
        }

        $this->incomeChart = compact('labels', 'seriesDue', 'seriesPaid');

        // ─── دفعات الملاك — آخر 6 أشهر ──────────────────
        $leaseLabels   = [];
        $leaseDue      = [];
        $leasePaid     = [];

        foreach ($months as $month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();

            $leaseLabels[] = $month->locale('ar')->isoFormat('MMM YY');
            $leaseDue[]    = (float) PropertyLeaseSchedule::whereBetween('due_date', [$start, $end])->sum('amount');
            $leasePaid[]   = (float) PropertyLeaseSchedule::whereBetween('due_date', [$start, $end])->sum('paid_amount');
        }

        $this->leaseChart = ['labels' => $leaseLabels, 'seriesDue' => $leaseDue, 'seriesPaid' => $leasePaid];

        // ─── تنبيهات مقسّمة حسب المدة ───────────────────
        $today = now()->startOfDay();
        $d30   = now()->addDays(30)->endOfDay();
        $d60   = now()->addDays(60)->endOfDay();
        $d90   = now()->addDays(90)->endOfDay();

        // تُقرأ من جدول notifications لا من جداول المصدر.
        //
        // كانت اللوحة تُعيد اشتقاق نفس المفهوم باستعلامات موازية، فنشأ مصدرا
        // حقيقة: لا ترث فلتر الأرشفة، ولا تحترم التأجيل، ولم تكن تعرض
        // المتأخرات إطلاقاً — فيرى الناظر إلى الشاشة الرئيسية أرقاماً صغيرة
        // بينما مئات التنبيهات فات موعدها.
        $periods = [
            'overdue' => fn ($q) => $q->where('trigger_date', '<', $today),
            30        => fn ($q) => $q->whereBetween('trigger_date', [$today, $d30]),
            60        => fn ($q) => $q->whereBetween('trigger_date', [$d30->copy()->addSecond(), $d60]),
            90        => fn ($q) => $q->whereBetween('trigger_date', [$d60->copy()->addSecond(), $d90]),
        ];

        $this->kpis['alerts'] = [];

        foreach ($periods as $key => $constrain) {
            $byType = $constrain(Notification::visible())
                ->selectRaw('type, COUNT(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type');

            $this->kpis['alerts'][$key] = [
                'contracts'       => ($byType['contract_expiring'] ?? 0) + ($byType['property_lease_expiring'] ?? 0),
                'tenant_payments' => ($byType['payment_due'] ?? 0) + ($byType['payment_overdue'] ?? 0),
                'lease_payments'  => $byType['lease_payment_due'] ?? 0,
                'vacant_units'    => $byType['unit_vacant'] ?? 0,
            ];
        }

        // ─── بيانات حالة الوحدات ─────────────────────────
        $unitsBase = Unit::notArchived()->whereHas('property', fn ($q) => $q->notArchived());

        $this->unitsChart = [
            'rented'      => (int) (clone $unitsBase)->where('status', 'rented')->count(),
            'vacant'      => (int) (clone $unitsBase)->where('status', 'vacant')->count(),
            'maintenance' => (int) (clone $unitsBase)->where('status', 'maintenance')->count(),
            'unavailable' => (int) (clone $unitsBase)->whereNotIn('status', ['rented', 'vacant', 'maintenance'])->count(),
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.main-dashboard')
            ->layout('layouts.app', ['title' => 'لوحة التحكم']);
    }
}
