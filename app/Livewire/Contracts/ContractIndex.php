<?php

namespace App\Livewire\Contracts;

use App\Enums\ContractStatus;
use App\Enums\PaymentScheduleStatus;
use App\Enums\UnitStatus;
use App\Traits\HasPermissionGuard;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Domains\Contract\Models\Contract;

class ContractIndex extends Component
{
    use WithPagination, HasPermissionGuard;

    public string $search = '';
    public string $status = '';

    // ─── إنهاء العقد ─────────────────────────────────
    public bool  $showTerminateModal      = false;
    public ?int  $terminatingContractId   = null;

    public array $terminateForm = [
        'date'   => '',
        'reason' => '',
        'notes'  => '',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // معلومات الأقساط المتأخرة (تُحمَّل عند فتح الموديل)
    public array $overdueDebt = ['count' => 0, 'amount' => 0.0];

    // ─── فتح موديل الإنهاء ───────────────────────────
    public function openTerminateModal(int $contractId): void
    {
        $this->resetValidation();
        $this->terminatingContractId = $contractId;
        $this->terminateForm = [
            'date'   => now()->toDateString(),
            'reason' => '',
            'notes'  => '',
        ];

        // حساب الديون المتأخرة الموجودة قبل الإنهاء
        $overdue = \App\Domains\Payment\Models\PaymentSchedule::where('contract_id', $contractId)
            ->where('status', PaymentScheduleStatus::Overdue->value)
            ->where('remaining_amount', '>', 0)
            ->get();

        $this->overdueDebt = [
            'count'  => $overdue->count(),
            'amount' => (float) $overdue->sum('remaining_amount'),
        ];

        $this->showTerminateModal = true;
    }

    // ─── تنفيذ الإنهاء ───────────────────────────────
    public function terminateContract(): void
    {
        if (! $this->requirePermission('contracts.terminate')) return;

        $this->validate([
            'terminateForm.date'   => ['required', 'date'],
            'terminateForm.reason' => ['required', 'string'],
        ], [
            'terminateForm.date.required'   => 'تاريخ الإنهاء إلزامي',
            'terminateForm.reason.required' => 'سبب الإنهاء إلزامي',
        ]);

        $contract = Contract::with('unit', 'paymentSchedules')
            ->notArchived()
            ->where('status', ContractStatus::Active->value)
            ->findOrFail($this->terminatingContractId);

        $terminationDate = Carbon::parse($this->terminateForm['date']);

        // منتهي مبكراً إذا قبل تاريخ النهاية الأصلي، وإلا منتهي
        $newStatus = $terminationDate->lt($contract->end_date)
            ? ContractStatus::EarlyEnded
            : ContractStatus::Ended;

        // 1) تحديث العقد
        $contract->update([
            'status'             => $newStatus->value,
            'termination_date'   => $terminationDate->toDateString(),
            'termination_reason' => $this->terminateForm['reason'],
            'termination_notes'  => $this->terminateForm['notes'] ?: null,
        ]);

        // 2) تحرير الوحدة
        $contract->unit?->update(['status' => UnitStatus::Vacant->value]);

        // 3) إلغاء الأقساط المستقبلية فقط (due_date > تاريخ الإنهاء)
        //    الأقساط المتأخرة والجزئية التي مرّ تاريخها تبقى كـ"دين" على المستأجر
        $contract->paymentSchedules()
            ->whereIn('status', [
                PaymentScheduleStatus::Pending->value,
                PaymentScheduleStatus::NearDue->value,
                PaymentScheduleStatus::Due->value,
                PaymentScheduleStatus::Partial->value,
            ])
            ->where('due_date', '>', $terminationDate->toDateString())
            ->update([
                'status'           => PaymentScheduleStatus::Cancelled->value,
                'remaining_amount' => 0,
            ]);

        $this->showTerminateModal = false;
        $this->terminatingContractId = null;
        $this->dispatch('notify', message: 'تم إنهاء العقد وتحرير الوحدة بنجاح');
    }

    // ─── أرشفة العقود غير النشطة ─────────────────────
    public function archiveContract(int $contractId): void
    {
        if (! $this->requirePermission('contracts.archive')) return;
        Contract::findOrFail($contractId)->update([
            'archived_at'     => now(),
            'archived_reason' => 'archived_from_contracts',
        ]);

        $this->dispatch('notify', message: 'تم نقل العقد إلى الأرشيف');
    }

    public function render()
    {
        $contracts = Contract::query()
            ->notArchived()
            ->with(['tenant', 'unit.property'])
            ->withCount('paymentSchedules')
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('code', 'like', '%' . $this->search . '%')
                      ->orWhereHas('tenant', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                      ->orWhereHas('unit', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(15);

        return view('livewire.contracts.contract-index', compact('contracts'))
            ->layout('layouts.app', ['title' => 'العقود']);
    }
}
