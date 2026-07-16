<?php

namespace App\Livewire\ActivityLog;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class ActivityLogIndex extends Component
{
    use WithPagination;

    // ─── Filters ─────────────────────────────────────
    public string  $search      = '';
    public string  $logName     = '';
    public string  $causerId    = '';
    public string  $dateFrom    = '';
    public string  $dateTo      = '';

    // ─── Detail modal ────────────────────────────────
    public ?int    $detailId    = null;
    public bool    $showDetail  = false;

    public function updatingSearch(): void  { $this->resetPage(); }
    public function updatingLogName(): void { $this->resetPage(); }
    public function updatingCauserId(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void  { $this->resetPage(); }

    // ─── تسميات المجالات ─────────────────────────────
    public function logNameLabel(string $name): string
    {
        return [
            'properties' => 'العقارات',
            'units'       => 'الوحدات',
            'contracts'   => 'العقود',
            'payments'    => 'الدفعات',
            'tenants'     => 'المستأجرون',
            'maintenance' => 'الصيانة',
            'companies'   => 'الشركات',
            'users'       => 'المستخدمون',
            'User'        => 'المستخدمون',
            'Property'    => 'العقارات',
            'Unit'        => 'الوحدات',
            'Contract'    => 'العقود',
            'Payment'     => 'الدفعات',
            'Tenant'      => 'المستأجرون',
        ][$name] ?? $name;
    }

    // ─── وصف الحدث ──────────────────────────────────
    public function eventLabel(string $event): array
    {
        return [
            'created' => ['label' => 'إنشاء',  'color' => 'emerald'],
            'updated' => ['label' => 'تعديل',  'color' => 'blue'],
            'deleted' => ['label' => 'حذف',    'color' => 'rose'],
        ][$event] ?? ['label' => $event, 'color' => 'slate'];
    }

    // ─── فتح تفاصيل سجل ─────────────────────────────
    public function openDetail(int $id): void
    {
        $this->detailId   = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
    }

    // ─── Render ──────────────────────────────────────
    public function render()
    {
        $query = Activity::with('causer')
            ->when($this->search, fn ($q) => $q->where('description', 'like', "%{$this->search}%"))
            ->when($this->logName, fn ($q) => $q->where('log_name', $this->logName))
            ->when($this->causerId, fn ($q) => $q->where('causer_id', $this->causerId)
                                                   ->where('causer_type', User::class))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest();

        $logs    = $query->paginate(20);
        $detail  = $this->detailId ? Activity::find($this->detailId) : null;

        $logNames = Activity::distinct()->pluck('log_name')->filter()->sort()->values();
        $users    = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.activity-log.activity-log-index', compact('logs', 'detail', 'logNames', 'users'));
    }
}
