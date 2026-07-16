<?php

namespace App\Livewire\Contracts;

use Livewire\Component;
use App\Domains\Contract\Models\Contract;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Payment\Actions\RegisterPaymentAction;

class ContractSchedule extends Component
{
    public Contract $contract;

    public bool $showPaymentModal = false;
    public ?int $payingScheduleId = null;

    public array $paymentForm = [
        'amount'           => '',
        'payment_method'   => 'bank_transfer',
        'paid_at'          => '',
        'reference_number' => '',
        'notes'            => '',
    ];

    public function mount(Contract $contract): void
    {
        $this->contract = $contract;
        $this->paymentForm['paid_at'] = now()->toDateString();
    }

    public function openPaymentModal(int $scheduleId): void
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
        $this->validate([
            'paymentForm.amount'           => ['required', 'numeric', 'min:0.01'],
            'paymentForm.payment_method'   => ['required', 'string'],
            'paymentForm.paid_at'          => ['required', 'date'],
            'paymentForm.reference_number' => ['required', 'string', 'max:100'],
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
            // رسم خطأ Action على حقل الموديل الصحيح
            foreach ($e->errors() as $field => $messages) {
                $this->addError('paymentForm.' . $field, $messages[0]);
            }
            return;
        }

        $this->showPaymentModal = false;
        $this->dispatch('notify', message: 'تم تسجيل الدفعة بنجاح');
    }

    public function render()
    {
        $schedules = PaymentSchedule::query()
            ->where('contract_id', $this->contract->id)
            ->with('payments')
            ->orderBy('installment_no')
            ->get();

        $summary = [
            'total'     => $schedules->sum('total_amount'),
            'paid'      => $schedules->sum('paid_amount'),
            'remaining' => $schedules->sum('remaining_amount'),
        ];

        return view('livewire.contracts.contract-schedule', compact('schedules', 'summary'))
            ->layout('layouts.app', ['title' => 'جدول الدفع - ' . $this->contract->code]);
    }
}
