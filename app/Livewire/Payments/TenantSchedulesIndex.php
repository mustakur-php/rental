<?php

namespace App\Livewire\Payments;

use App\Traits\HasPermissionGuard;
use Livewire\Component;
use Livewire\WithPagination;
use App\Domains\Payment\Actions\RegisterPaymentAction;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Property\Models\Property;
use App\Domains\Notification\Services\NotificationSyncService;
use Illuminate\Support\Facades\Cache;

class TenantSchedulesIndex extends Component
{
    use WithPagination, HasPermissionGuard;

    public string $search   = '';
    public string $status   = '';
    public string $property = '';

    public bool  $showPaymentModal  = false;
    public ?int  $payingScheduleId  = null;

    // ملاحظات الدفعة
    public bool   $showNoteModal   = false;
    public ?int   $noteScheduleId  = null;
    public string $noteText        = '';

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

        Cache::remember('overdue_statuses_marked', now()->addMinutes(5), function () {
            app(NotificationSyncService::class)->markOverdueStatuses();
            return now()->toDateTimeString();
        });
    }

    public function updatingSearch(): void   { $this->resetPage(); }
    public function updatingStatus(): void   { $this->resetPage(); }
    public function updatingProperty(): void { $this->resetPage(); }

    public function openPaymentFor(int $scheduleId): void
    {
        $schedule = PaymentSchedule::findOrFail($scheduleId);
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

    public function registerPayment(RegisterPaymentAction $action): void
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

        try {
            $action->execute([
                'payment_schedule_id' => $this->payingScheduleId,
                'amount'              => $this->paymentForm['amount'],
                'payment_method'      => $this->paymentForm['payment_method'],
                'paid_at'             => $this->paymentForm['paid_at'],
                'reference_number'    => $this->paymentForm['reference_number'] ?: null,
                'notes'               => $this->paymentForm['notes'] ?: null,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // إعادة تعيين مفاتيح الخطأ من Action (مثل 'amount') إلى مفاتيح الواجهة (paymentForm.amount)
            foreach ($e->errors() as $field => $messages) {
                $this->addError('paymentForm.' . $field, $messages[0]);
            }
            return;
        }

        $this->showPaymentModal = false;
        $this->dispatch('notify', message: 'تم تسجيل الدفعة بنجاح');
    }

    public function openNoteModal(int $scheduleId): void
    {
        $schedule = PaymentSchedule::findOrFail($scheduleId);
        $this->noteScheduleId = $schedule->id;
        $this->noteText       = $schedule->notes ?? '';
        $this->showNoteModal  = true;
    }

    public function saveNote(): void
    {
        PaymentSchedule::findOrFail($this->noteScheduleId)
            ->update(['notes' => trim($this->noteText) ?: null]);

        $this->showNoteModal = false;
        $this->dispatch('notify', message: 'تم حفظ الملاحظة');
    }

    public function render()
    {
        // الاستعلام الأساسي: استثناء العقود المؤرشفة والأقساط الملغاة (ما لم يُختَر فلتر "ملغي" صراحةً)
        $base = PaymentSchedule::query()
            ->whereHas('contract', fn ($q) => $q->whereNull('archived_at'))
            ->when(
                $this->status !== 'cancelled',
                fn ($q) => $q->where('status', '!=', 'cancelled')
            )
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->property, fn ($q) => $q->whereHas(
                'contract.unit', fn ($q) => $q->where('property_id', $this->property)
            ))
            ->when($this->search, fn ($q) => $q->whereHas(
                'contract', fn ($q) => $q
                    ->whereHas('tenant', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                    ->orWhere('code', 'like', "%{$this->search}%")
            ));

        $schedules = (clone $base)
            ->with(['contract.tenant', 'contract.unit.property'])
            ->orderBy('due_date')
            ->paginate(20);

        $properties = Property::notArchived()->orderBy('name')->get(['id', 'name']);

        // KPIs: تستثني الأقساط الملغاة دائماً بغض النظر عن الفلتر
        $kpiBase = PaymentSchedule::query()
            ->whereHas('contract', fn ($q) => $q->whereNull('archived_at'))
            ->where('status', '!=', 'cancelled')
            ->when($this->property, fn ($q) => $q->whereHas(
                'contract.unit', fn ($q) => $q->where('property_id', $this->property)
            ))
            ->when($this->search, fn ($q) => $q->whereHas(
                'contract', fn ($q) => $q
                    ->whereHas('tenant', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                    ->orWhere('code', 'like', "%{$this->search}%")
            ));

        $totals = [
            'total'     => (clone $kpiBase)->sum('total_amount'),
            'paid'      => (clone $kpiBase)->sum('paid_amount'),
            'remaining' => (clone $kpiBase)->sum('remaining_amount'),
            'overdue'   => (clone $kpiBase)->where('status', 'overdue')->sum('remaining_amount'),
        ];

        return view('livewire.payments.tenant-schedules-index', compact('schedules', 'properties', 'totals'))
            ->layout('layouts.app', ['title' => 'دفعات المستأجرين']);
    }
}
