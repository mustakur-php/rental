<?php

namespace App\Livewire\Payments;

use App\Traits\HasPermissionGuard;
use Livewire\Component;
use Livewire\WithPagination;
use App\Domains\Property\Models\Property;
use App\Domains\Property\Models\PropertyLeaseSchedule;
use App\Domains\Notification\Services\NotificationSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaseSchedulesIndex extends Component
{
    use WithPagination, HasPermissionGuard;

    public string $search   = '';
    public string $status   = '';
    public string $property = '';

    public bool $showPaymentModal = false;
    public ?int  $payingScheduleId = null;

    public array $paymentForm = [
        'amount'           => '',
        'payment_method'   => 'bank_transfer',
        'paid_at'          => '',
        'reference_number' => '',
        'notes'            => '',
    ];

    public function mount(): void
    {
        $this->paymentForm['paid_at'] = now()->toDateString();

        // تحديث حالات التأخير عند كل فتح للصفحة (مرة كل 5 دقائق كحد أقصى)
        Cache::remember('overdue_statuses_marked', now()->addMinutes(5), function () {
            app(NotificationSyncService::class)->markOverdueStatuses();
            return now()->toDateTimeString();
        });
    }

    public function updatingSearch(): void   { $this->resetPage(); }
    public function updatingStatus(): void   { $this->resetPage(); }
    public function updatingProperty(): void { $this->resetPage(); }

    public function openPaymentModal(int $scheduleId): void
    {
        $schedule = PropertyLeaseSchedule::findOrFail($scheduleId);
        $this->resetValidation();
        $this->payingScheduleId = $schedule->id;
        $this->paymentForm = [
            'amount'           => $schedule->remaining_amount,
            'payment_method'   => 'bank_transfer',
            'paid_at'          => now()->toDateString(),
            'reference_number' => '',
            'notes'            => '',
        ];
        $this->showPaymentModal = true;
    }

    public function registerPayment(): void
    {
        if (! $this->requirePermission('payments.create')) return;
        $this->validate([
            'paymentForm.amount'            => ['required', 'numeric', 'min:0.01'],
            'paymentForm.payment_method'    => ['required', 'string'],
            'paymentForm.paid_at'           => ['required', 'date'],
            'paymentForm.reference_number'  => ['required', 'string', 'max:100'],
        ], [
            'paymentForm.amount.required'           => 'المبلغ إلزامي',
            'paymentForm.amount.min'                => 'المبلغ يجب أن يكون أكبر من صفر',
            'paymentForm.paid_at.required'          => 'تاريخ الدفع إلزامي',
            'paymentForm.reference_number.required' => 'رقم المرجع / الحوالة إلزامي',
            'paymentForm.reference_number.max'      => 'رقم المرجع يجب ألا يتجاوز 100 حرف',
        ]);

        DB::transaction(function () {
            $schedule = PropertyLeaseSchedule::lockForUpdate()->findOrFail($this->payingScheduleId);

            $amount = (float) $this->paymentForm['amount'];

            if ($amount > (float) $schedule->remaining_amount) {
                $this->addError('paymentForm.amount', 'المبلغ أكبر من المبلغ المتبقي');
                throw ValidationException::withMessages([
                    'paymentForm.amount' => 'المبلغ أكبر من المبلغ المتبقي',
                ]);
            }

            $paidAmount      = round((float) $schedule->paid_amount + $amount, 2);
            $remainingAmount = round(max((float) $schedule->amount - $paidAmount, 0), 2);

            $schedule->update([
                'paid_amount'      => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'paid_at'          => $remainingAmount <= 0 ? $this->paymentForm['paid_at'] : null,
                'payment_method'   => $this->paymentForm['payment_method'],
                'reference_number' => $this->paymentForm['reference_number'] ?: null,
                'notes'            => $this->paymentForm['notes'] ?: null,
                'status'           => $remainingAmount <= 0 ? 'paid' : 'partial',
            ]);
        });

        $this->showPaymentModal = false;
        $this->dispatch('notify', message: 'تم تسجيل الدفعة بنجاح');
    }

    public function render()
    {
        $schedules = PropertyLeaseSchedule::query()
            ->with(['lease.property'])
            ->when($this->status,   fn ($q) => $q->where('status', $this->status))
            ->when($this->property, fn ($q) => $q->whereHas(
                'lease', fn ($q) => $q->where('property_id', $this->property)
            ))
            ->when($this->search, fn ($q) => $q->whereHas(
                'lease', fn ($q) => $q
                    ->where('owner_name', 'like', "%{$this->search}%")
                    ->orWhereHas('property', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ))
            ->orderBy('due_date')
            ->paginate(20);

        $properties = Property::where('ownership_type', 'leased')->orderBy('name')->get(['id', 'name']);

        $baseQuery = PropertyLeaseSchedule::query()
            ->when($this->status,   fn ($q) => $q->where('status', $this->status))
            ->when($this->property, fn ($q) => $q->whereHas(
                'lease', fn ($q) => $q->where('property_id', $this->property)
            ))
            ->when($this->search, fn ($q) => $q->whereHas(
                'lease', fn ($q) => $q
                    ->where('owner_name', 'like', "%{$this->search}%")
                    ->orWhereHas('property', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ));

        $totals = [
            'total'     => (clone $baseQuery)->sum('amount'),
            'paid'      => (clone $baseQuery)->sum('paid_amount'),
            'remaining' => (clone $baseQuery)->sum('remaining_amount'),
            'overdue'   => (clone $baseQuery)->where('status', 'overdue')->sum('remaining_amount'),
        ];

        return view('livewire.payments.lease-schedules-index', compact('schedules', 'properties', 'totals'))
            ->layout('layouts.app', ['title' => 'دفعات الملاك']);
    }
}
