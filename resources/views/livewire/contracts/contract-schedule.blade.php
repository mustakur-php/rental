<div class="erp-container">

    @if(session('success'))
        <div class="mb-4 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
            <span class="text-base">✓</span>
            {{ session('success') }}
        </div>
    @endif

    <x-page-header title="جدول الدفع" subtitle="عقد {{ $contract->code }} — {{ $contract->tenant?->name }}">
        <x-slot:actions>
            @if($contract->contract_file_path)
                <a href="{{ Storage::disk('public')->url($contract->contract_file_path) }}"
                   target="_blank"
                   class="erp-btn-soft text-sm">
                    📎 عرض نسخة العقد
                </a>
            @endif
            <a href="{{ route('contracts.index') }}" class="erp-btn-soft text-sm">← العقود</a>
        </x-slot:actions>
    </x-page-header>

    {{-- ملخص العقد --}}
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="erp-card p-5 text-center">
            <div class="erp-muted text-xs">إجمالي العقد</div>
            <div class="mt-1 text-2xl font-black">{{ number_format($summary['total'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
        <div class="erp-card p-5 text-center">
            <div class="erp-muted text-xs">المدفوع</div>
            <div class="mt-1 text-2xl font-black text-emerald-600">{{ number_format($summary['paid'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
        <div class="erp-card p-5 text-center">
            <div class="erp-muted text-xs">المتبقي</div>
            <div class="mt-1 text-2xl font-black text-rose-600">{{ number_format($summary['remaining'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
        <div class="erp-card p-5 text-center">
            <div class="erp-muted text-xs">نسبة التحصيل</div>
            <div class="mt-1 text-2xl font-black text-blue-600">
                {{ $summary['total'] > 0 ? number_format(($summary['paid'] / $summary['total']) * 100, 1) : 0 }}%
            </div>
        </div>
    </div>

    {{-- جدول الأقساط --}}
    <div class="erp-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 bg-slate-50 text-right text-xs font-bold text-slate-500">
                <tr>
                    <th class="px-5 py-3">#</th>
                    <th class="px-5 py-3">تاريخ الاستحقاق</th>
                    <th class="px-5 py-3">المبلغ</th>
                    <th class="px-5 py-3">المدفوع</th>
                    <th class="px-5 py-3">المتبقي</th>
                    <th class="px-5 py-3">الحالة</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" x-data="{ openSchedule: null }">
                @foreach($schedules as $schedule)
                @php
                    $statusVal = is_object($schedule->status) ? $schedule->status->value : $schedule->status;
                    $statusLabels  = ['pending' => 'معلق', 'near_due' => 'قرب الاستحقاق', 'due' => 'مستحق', 'partial' => 'جزئي', 'paid' => 'مدفوع', 'overdue' => 'متأخر', 'cancelled' => 'ملغي'];
                    $statusClasses = ['pending' => 'bg-slate-100 text-slate-600', 'near_due' => 'bg-amber-50 text-amber-700', 'due' => 'bg-sky-50 text-sky-700', 'partial' => 'bg-purple-50 text-purple-700', 'paid' => 'bg-emerald-50 text-emerald-700', 'overdue' => 'bg-rose-50 text-rose-700', 'cancelled' => 'bg-slate-100 text-slate-400'];
                    $canPay = !in_array($statusVal, ['paid', 'cancelled']) && $schedule->remaining_amount > 0;
                    $methodLabels = ['bank_transfer' => 'تحويل بنكي', 'cash' => 'نقداً', 'cheque' => 'شيك', 'other' => 'أخرى'];
                @endphp
                    <tr class="hover:bg-slate-50 transition {{ $statusVal === 'overdue' ? 'bg-rose-50/30' : '' }}">
                        <td class="px-5 py-4 text-slate-500">{{ $schedule->installment_no }}</td>
                        <td class="px-5 py-4 font-semibold {{ $statusVal === 'overdue' ? 'text-rose-700' : 'text-slate-700' }}">
                            {{ $schedule->due_date?->format('Y/m/d') }}
                        </td>
                        <td class="px-5 py-4 text-slate-700">{{ number_format($schedule->total_amount, 0) }} ر.س</td>
                        <td class="px-5 py-4 font-bold text-emerald-700">{{ number_format($schedule->paid_amount, 0) }} ر.س</td>
                        <td class="px-5 py-4 font-bold text-rose-700">{{ number_format($schedule->remaining_amount, 0) }} ر.س</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$statusVal] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $statusLabels[$statusVal] ?? $statusVal }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-left">
                            <div class="flex items-center gap-2 justify-end">
                                @if($canPay)
                                    <button wire:click="openPaymentModal({{ $schedule->id }})" class="erp-btn-primary text-xs">
                                        تسجيل دفعة
                                    </button>
                                @endif
                                @if($schedule->payments->isNotEmpty())
                                    <button @click="openSchedule = openSchedule === {{ $schedule->id }} ? null : {{ $schedule->id }}"
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200"
                                            :class="openSchedule === {{ $schedule->id }} ? 'rotate-180' : ''"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if($schedule->payments->isNotEmpty())
                    @foreach($schedule->payments as $payment)
                    <tr x-show="openSchedule === {{ $schedule->id }}" x-cloak
                        class="bg-emerald-50/40 text-xs border-t border-emerald-100">
                        <td class="px-5 py-2 text-slate-400">└</td>
                        <td class="px-5 py-2 text-slate-500">{{ $payment->payment_date?->format('Y/m/d') }}</td>
                        <td class="px-5 py-2 font-semibold text-emerald-700">{{ number_format($payment->amount, 0) }} ر.س</td>
                        <td class="px-5 py-2 text-slate-500">{{ $methodLabels[$payment->method] ?? $payment->method }}</td>
                        <td class="px-5 py-2 text-slate-400">{{ $payment->reference_number ?: '—' }}</td>
                        <td class="px-5 py-2 text-slate-400" colspan="2">{{ $payment->notes ?: '' }}</td>
                    </tr>
                    @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- مودال الدفع --}}
    @if($showPaymentModal)
    <div class="erp-modal-overlay">
        <div class="erp-modal-box max-w-md">
            <div class="erp-modal-header">
                <h2 class="text-lg font-bold text-slate-900">تسجيل دفعة</h2>
                <button wire:click="$set('showPaymentModal', false)"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition text-lg leading-none">
                    ×
                </button>
            </div>
            <div class="erp-modal-body space-y-4">
                <div>
                    <label class="erp-label">المبلغ (ر.س) *</label>
                    <input type="number" step="0.01" wire:model="paymentForm.amount"
                        class="erp-input {{ $errors->has('paymentForm.amount') ? 'erp-input-error' : '' }}">
                    @error('paymentForm.amount') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="erp-label">تاريخ الدفع *</label>
                    <input type="date" wire:model="paymentForm.paid_at"
                        class="erp-input {{ $errors->has('paymentForm.paid_at') ? 'erp-input-error' : '' }}">
                    @error('paymentForm.paid_at') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="erp-label">طريقة الدفع *</label>
                    <select wire:model="paymentForm.payment_method" class="erp-select">
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="cash">نقداً</option>
                        <option value="cheque">شيك</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
                <div>
                    <label class="erp-label">رقم المرجع / الحوالة</label>
                    <input wire:model="paymentForm.reference_number" class="erp-input"
                        placeholder="اختياري">
                </div>
                <div>
                    <label class="erp-label">ملاحظات</label>
                    <textarea wire:model="paymentForm.notes" rows="2" class="erp-textarea"
                        placeholder="ملاحظات اختيارية..."></textarea>
                </div>
            </div>
            <div class="erp-modal-footer">
                <button wire:click="$set('showPaymentModal', false)" class="erp-btn-soft">إلغاء</button>
                <button wire:click="registerPayment" wire:loading.attr="disabled" class="erp-btn-primary">
                    <span wire:loading.remove wire:target="registerPayment">تسجيل الدفعة</span>
                    <span wire:loading wire:target="registerPayment">جارٍ الحفظ...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
