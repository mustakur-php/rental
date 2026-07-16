<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Domains\Property\Models\Property;
use App\Domains\Unit\Models\Unit;
use App\Domains\Contract\Models\Contract;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Maintenance\Models\MaintenanceRequest;
use App\Domains\Notification\Services\NotificationSyncService;
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
        app(NotificationSyncService::class)->markOverdueStatuses();

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

        $this->kpis['alerts'] = [
            30 => [
                'contracts' => Contract::notArchived()->where('status', 'active')
                    ->whereBetween('end_date', [$today, $d30])->count(),
                'tenant_payments' => PaymentSchedule::whereIn('status', ['pending', 'due', 'partial', 'overdue'])
                    ->where('remaining_amount', '>', 0)
                    ->whereBetween('due_date', [$today, $d30])->count(),
                'lease_payments'  => PropertyLeaseSchedule::whereNotIn('status', ['paid'])
                    ->where('remaining_amount', '>', 0)
                    ->whereBetween('due_date', [$today, $d30])->count(),
            ],
            60 => [
                'contracts' => Contract::notArchived()->where('status', 'active')
                    ->whereBetween('end_date', [$d30->copy()->addSecond(), $d60])->count(),
                'tenant_payments' => PaymentSchedule::whereIn('status', ['pending', 'due', 'partial'])
                    ->where('remaining_amount', '>', 0)
                    ->whereBetween('due_date', [$d30->copy()->addSecond(), $d60])->count(),
                'lease_payments'  => PropertyLeaseSchedule::whereNotIn('status', ['paid'])
                    ->where('remaining_amount', '>', 0)
                    ->whereBetween('due_date', [$d30->copy()->addSecond(), $d60])->count(),
            ],
            90 => [
                'contracts' => Contract::notArchived()->where('status', 'active')
                    ->whereBetween('end_date', [$d60->copy()->addSecond(), $d90])->count(),
                'tenant_payments' => PaymentSchedule::whereIn('status', ['pending', 'due', 'partial'])
                    ->where('remaining_amount', '>', 0)
                    ->whereBetween('due_date', [$d60->copy()->addSecond(), $d90])->count(),
                'lease_payments'  => PropertyLeaseSchedule::whereNotIn('status', ['paid'])
                    ->where('remaining_amount', '>', 0)
                    ->whereBetween('due_date', [$d60->copy()->addSecond(), $d90])->count(),
            ],
        ];

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
