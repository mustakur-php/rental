<div class="erp-container">
    <x-page-header title="دفعات الملاك" subtitle="جداول سداد إيجار العقارات المستأجرة" />

    {{-- KPIs --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="erp-kpi text-center">
            <div class="text-xs text-slate-400 mb-1">إجمالي الالتزامات</div>
            <div class="text-2xl font-black text-slate-900">{{ number_format($totals['total'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
        <div class="erp-kpi text-center">
            <div class="text-xs text-slate-400 mb-1">المدفوع للملاك</div>
            <div class="text-2xl font-black text-emerald-600">{{ number_format($totals['paid'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
        <div class="erp-kpi text-center">
            <div class="text-xs text-slate-400 mb-1">المتبقي</div>
            <div class="text-2xl font-black text-slate-700">{{ number_format($totals['remaining'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
        <div class="erp-kpi border-rose-100 text-center">
            <div class="text-xs text-rose-400 mb-1">المتأخر للملاك</div>
            <div class="text-2xl font-black text-rose-600">{{ number_format($totals['overdue'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
    </div>

    {{-- فلاتر --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search"
            placeholder="ابحث باسم المالك أو العقار..."
            class="erp-input flex-1 min-w-48">

        <select wire:model.live="status" class="erp-select w-auto">
            <option value="">كل الحالات</option>
            <option value="pending">معلق</option>
            <option value="partial">جزئي</option>
            <option value="overdue">متأخر</option>
            <option value="paid">مدفوع</option>
        </select>

        <select wire:model.live="property" class="erp-select w-auto">
            <option value="">كل العقارات المستأجرة</option>
            @foreach($properties as $prop)
                <option value="{{ $prop->id }}">{{ $prop->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- الجدول --}}
    <div class="erp-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 bg-slate-50 text-right text-xs font-bold text-slate-500">
                <tr>
                    <th class="px-5 py-3">العقار</th>
                    <th class="px-5 py-3">المالك</th>
                    <th class="px-5 py-3">القسط</th>
                    <th class="px-5 py-3">الاستحقاق</th>
                    <th class="px-5 py-3">المبلغ</th>
                    <th class="px-5 py-3">المدفوع</th>
                    <th class="px-5 py-3">المتبقي</th>
                    <th class="px-5 py-3">الحالة</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($schedules as $schedule)
                @php
                    $s = $schedule->status ?? 'pending';
                    $labels  = ['pending'=>'معلق','partial'=>'جزئي','overdue'=>'متأخر','paid'=>'مدفوع'];
                    $classes = ['pending'=>'erp-badge-slate','partial'=>'erp-badge-purple','overdue'=>'erp-badge-red','paid'=>'erp-badge-green'];
                    $canPay  = !in_array($s, ['paid']) && $schedule->remaining_amount > 0;
                @endphp
                    <tr class="hover:bg-slate-50 transition {{ $s === 'overdue' ? 'bg-rose-50/30' : '' }}">
                        <td class="px-5 py-3.5 font-semibold text-slate-800">
                            {{ $schedule->lease?->property?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-600">
                            {{ $schedule->lease?->owner_name ?? '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-500">{{ $schedule->installment_no }}</td>
                        <td class="px-5 py-3.5 font-semibold {{ $s === 'overdue' ? 'text-rose-700' : 'text-slate-700' }}">
                            {{ $schedule->due_date?->format('Y/m/d') }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-700">{{ number_format($schedule->amount, 0) }}</td>
                        <td class="px-5 py-3.5 font-bold text-emerald-700">{{ number_format($schedule->paid_amount, 0) }}</td>
                        <td class="px-5 py-3.5 font-bold {{ $schedule->remaining_amount > 0 ? 'text-rose-700' : 'text-slate-400' }}">
                            {{ number_format($schedule->remaining_amount, 0) }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="erp-badge {{ $classes[$s] ?? 'erp-badge-slate' }}">
                                {{ $labels[$s] ?? $s }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-left">
                            @can('payments.create')
                            @if($canPay)
                                <button wire:click="openPaymentModal({{ $schedule->id }})"
                                    class="erp-btn-primary erp-btn-sm">
                                    تسجيل دفعة
                                </button>
                            @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-12 text-center text-sm text-slate-400">
                            لا توجد استحقاقات مطابقة
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($schedules->hasPages())
            <div class="erp-divider px-5 py-4">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>

    {{-- مودال الدفع --}}
    @if($showPaymentModal)
    <div class="erp-modal-overlay">
        <div class="erp-modal-box max-w-md">
            <div class="erp-modal-header">
                <h2 class="text-lg font-bold text-slate-900">تسجيل دفعة للمالك</h2>
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
                    @error('paymentForm.amount')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="erp-label">طريقة الدفع</label>
                    <select wire:model="paymentForm.payment_method" class="erp-select">
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="cash">نقداً</option>
                        <option value="cheque">شيك</option>
                        <option value="online">دفع إلكتروني</option>
                    </select>
                </div>
                <div>
                    <label class="erp-label">تاريخ الدفع *</label>
                    <input type="date" wire:model="paymentForm.paid_at"
                        class="erp-input {{ $errors->has('paymentForm.paid_at') ? 'erp-input-error' : '' }}">
                    @error('paymentForm.paid_at')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="erp-label">رقم المرجع / الحوالة *</label>
                    <input wire:model="paymentForm.reference_number"
                        class="erp-input {{ $errors->has('paymentForm.reference_number') ? 'erp-input-error' : '' }}"
                        placeholder="رقم الحوالة أو رقم الشيك...">
                    @error('paymentForm.reference_number')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="erp-label">ملاحظات</label>
                    <textarea wire:model="paymentForm.notes" rows="2"
                        class="erp-textarea" placeholder="أي ملاحظات إضافية..."></textarea>
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
