<div class="erp-container">
    <x-page-header title="دفعات المستأجرين" subtitle="جميع استحقاقات عقود الإيجار" />

    {{-- KPIs --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="erp-kpi text-center">
            <div class="text-xs text-slate-400 mb-1">إجمالي الاستحقاقات</div>
            <div class="text-2xl font-black text-slate-900">{{ number_format($totals['total'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
        <div class="erp-kpi text-center">
            <div class="text-xs text-slate-400 mb-1">المحصّل</div>
            <div class="text-2xl font-black text-emerald-600">{{ number_format($totals['paid'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
        <div class="erp-kpi text-center">
            <div class="text-xs text-slate-400 mb-1">المتبقي</div>
            <div class="text-2xl font-black text-slate-700">{{ number_format($totals['remaining'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
        <div class="erp-kpi border-rose-100 text-center">
            <div class="text-xs text-rose-400 mb-1">المتأخرات</div>
            <div class="text-2xl font-black text-rose-600">{{ number_format($totals['overdue'], 0) }}</div>
            <div class="text-xs text-slate-400">ر.س</div>
        </div>
    </div>

    {{-- فلاتر --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search"
            placeholder="ابحث باسم المستأجر أو كود العقد..."
            class="erp-input flex-1 min-w-48">

        <select wire:model.live="status" class="erp-select w-auto">
            <option value="">كل الحالات</option>
            <option value="pending">معلق</option>
            <option value="due">مستحق</option>
            <option value="partial">جزئي</option>
            <option value="overdue">متأخر</option>
            <option value="paid">مدفوع</option>
        </select>

        <select wire:model.live="property" class="erp-select w-auto">
            <option value="">كل العقارات</option>
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
                    <th class="px-5 py-3">المستأجر</th>
                    <th class="px-5 py-3">الوحدة / العقار</th>
                    <th class="px-5 py-3">القسط</th>
                    <th class="px-5 py-3">الاستحقاق</th>
                    <th class="px-5 py-3">الإجمالي</th>
                    <th class="px-5 py-3">المدفوع</th>
                    <th class="px-5 py-3">المتبقي</th>
                    <th class="px-5 py-3">الحالة</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($schedules as $schedule)
                @php
                    $s = is_object($schedule->status) ? $schedule->status->value : $schedule->status;
                    $labels  = ['pending'=>'معلق','due'=>'مستحق','partial'=>'جزئي','overdue'=>'متأخر','paid'=>'مدفوع','cancelled'=>'ملغي'];
                    $classes = ['pending'=>'erp-badge-slate','due'=>'erp-badge-blue','partial'=>'erp-badge-purple','overdue'=>'erp-badge-red','paid'=>'erp-badge-green','cancelled'=>'erp-badge-slate'];
                    $canPay  = !in_array($s, ['paid','cancelled']) && $schedule->remaining_amount > 0;
                @endphp
                    <tr wire:key="schedule-{{ $schedule->id }}" class="hover:bg-slate-50 transition {{ $s === 'overdue' ? 'bg-rose-50/30' : '' }}">
                        <td class="px-5 py-3.5 font-semibold text-slate-800">
                            {{ $schedule->contract?->tenant?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-500 text-xs">
                            {{ $schedule->contract?->unit?->name ?? '—' }}<br>
                            <span class="text-slate-400">{{ $schedule->contract?->unit?->property?->name ?? '' }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-slate-500">{{ $schedule->installment_no }}</td>
                        <td class="px-5 py-3.5 font-semibold {{ $s === 'overdue' ? 'text-rose-700' : 'text-slate-700' }}">
                            {{ $schedule->due_date?->format('Y/m/d') }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-700">{{ number_format($schedule->total_amount, 0) }}</td>
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
                            <div class="flex items-center gap-2 justify-end">
                                <button wire:click="openNoteModal({{ $schedule->id }})"
                                    title="{{ $schedule->notes ? 'توجد ملاحظة' : 'إضافة ملاحظة' }}"
                                    @class(['relative rounded-xl px-3 py-1.5 text-xs font-semibold transition',
                                        'bg-amber-100 text-amber-800 hover:bg-amber-200' => $schedule->notes,
                                        'bg-slate-100 text-slate-500 hover:bg-slate-200' => !$schedule->notes])>
                                    📝
                                    @if($schedule->notes)
                                        <span class="absolute -top-1 -left-1 h-2 w-2 rounded-full bg-amber-500"></span>
                                    @endif
                                </button>
                                @can('payments.create')
                                @if($canPay)
                                    <button wire:click="openPaymentFor({{ $schedule->id }})"
                                        class="erp-btn-primary erp-btn-sm">
                                        تسجيل دفعة
                                    </button>
                                @endif
                                @endcan
                            </div>
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
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>

    {{-- مودال تسجيل الدفعة --}}
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
                    <label class="erp-label">المبلغ المدفوع (ر.س) *</label>
                    <input type="number" step="0.01" wire:model="paymentForm.amount"
                        class="erp-input {{ $errors->has('paymentForm.amount') ? 'erp-input-error' : '' }}">
                    @error('paymentForm.amount')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
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
                    <label class="erp-label">طريقة الدفع *</label>
                    <select wire:model="paymentForm.payment_method" class="erp-select">
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="cash">نقد</option>
                        <option value="cheque">شيك</option>
                        <option value="other">أخرى</option>
                    </select>
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

    {{-- modal ملاحظات الدفعة --}}
    @if($showNoteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
         wire:click.self="$set('showNoteModal', false)">
        <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="font-bold text-slate-900">📝 ملاحظات الدفعة</h3>
                <button wire:click="$set('showNoteModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div class="px-6 py-5">
                <textarea wire:model="noteText" rows="5"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100"
                    placeholder="اكتب ملاحظاتك هنا... (متابعة العميل، تواصل، وعود دفع، إلخ)"></textarea>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button wire:click="$set('showNoteModal', false)" class="erp-btn-soft">إلغاء</button>
                <button wire:click="saveNote" class="rounded-2xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-amber-600">
                    حفظ الملاحظة
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
