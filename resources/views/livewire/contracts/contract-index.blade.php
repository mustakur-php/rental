<div class="erp-container">
    <x-page-header title="العقود" subtitle="قائمة جميع عقود الإيجار في النظام">
        <x-slot:actions>
            @can('contracts.create')
            <a href="{{ route('contracts.create') }}" class="erp-btn-primary">+ إنشاء عقد</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- فلاتر --}}
    <div class="erp-card mb-6 p-4">
        <div class="grid gap-3 md:grid-cols-2">
            <input
                wire:model.live.debounce.300ms="search"
                class="erp-input"
                placeholder="بحث بكود العقد، اسم المستأجر، اسم الوحدة..."
            >
            <select wire:model.live="status" class="erp-select">
                <option value="">كل الحالات</option>
                <option value="active">نشط</option>
                <option value="ended">منتهي</option>
                <option value="early_ended">منتهي مبكراً</option>
                <option value="cancelled">ملغي</option>
                <option value="renewed">مجدد</option>
                <option value="draft">مسودة</option>
            </select>
        </div>
    </div>

    {{-- الجدول --}}
    @if($contracts->isEmpty())
        <div class="erp-card p-16 text-center">
            <div class="mb-4 text-4xl">📄</div>
            <div class="text-lg font-bold text-slate-700">لا توجد عقود</div>
            <p class="mt-2 text-sm text-slate-400">ابدأ بإنشاء أول عقد إيجار.</p>
            <a href="{{ route('contracts.create') }}" class="erp-btn-primary mt-6 inline-block">+ إنشاء عقد</a>
        </div>
    @else
        <div class="erp-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-right text-xs font-bold text-slate-500">
                    <tr>
                        <th class="px-5 py-3">كود العقد</th>
                        <th class="px-5 py-3">المستأجر</th>
                        <th class="px-5 py-3">الوحدة / العقار</th>
                        <th class="px-5 py-3">تاريخ البداية</th>
                        <th class="px-5 py-3">تاريخ النهاية</th>
                        <th class="px-5 py-3">الإجمالي</th>
                        <th class="px-5 py-3">عدد الدفعات</th>
                        <th class="px-5 py-3">الحالة</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($contracts as $contract)
                    @php
                        $statusLabels  = ['active' => 'نشط', 'ended' => 'منتهي', 'early_ended' => 'منتهي مبكراً', 'cancelled' => 'ملغي', 'renewed' => 'مجدد', 'draft' => 'مسودة'];
                        $statusClasses = ['active' => 'bg-emerald-50 text-emerald-700', 'ended' => 'bg-slate-100 text-slate-600', 'early_ended' => 'bg-amber-50 text-amber-700', 'cancelled' => 'bg-rose-50 text-rose-700', 'renewed' => 'bg-sky-50 text-sky-700', 'draft' => 'bg-slate-100 text-slate-500'];
                        $statusValue = is_object($contract->status) ? $contract->status->value : $contract->status;
                    @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $contract->code }}</td>
                            <td class="px-5 py-4 font-bold text-slate-900">{{ $contract->tenant?->name ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800">{{ $contract->unit?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ $contract->unit?->property?->name ?? '' }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $contract->start_date?->format('Y/m/d') }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $contract->end_date?->format('Y/m/d') }}</td>
                            <td class="px-5 py-4 font-bold text-slate-800">
                                {{ number_format($contract->total_with_vat, 0) }} ر.س
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-800">
                                {{ $contract->payment_schedules_count }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$statusValue] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabels[$statusValue] ?? $statusValue }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-left">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('contracts.schedule', $contract) }}" class="erp-btn-soft erp-btn-sm">جدول الدفع</a>
                                    @can('contracts.terminate')
                                        @if($statusValue === 'active')
                                            <button wire:click="openTerminateModal({{ $contract->id }})"
                                                class="erp-btn-soft erp-btn-sm text-rose-700 border-rose-200 hover:bg-rose-50">
                                                إنهاء العقد
                                            </button>
                                        @else
                                            <button wire:click="archiveContract({{ $contract->id }})"
                                                wire:confirm="نقل العقد إلى الأرشيف؟"
                                                class="erp-btn-soft erp-btn-sm text-amber-700">
                                                أرشفة
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $contracts->links() }}</div>
    @endif

    {{-- ═══ موديل إنهاء العقد ═══ --}}
    @if($showTerminateModal)
    @php
        $tContract = $contracts->firstWhere('id', $terminatingContractId);
    @endphp
    <div class="erp-modal-overlay">
        <div class="erp-modal-box max-w-lg">

            {{-- رأس --}}
            <div class="erp-modal-header">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">إنهاء العقد</h2>
                    @if($tContract)
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $tContract->code }} — {{ $tContract->tenant?->name }}
                        </p>
                    @endif
                </div>
                <button wire:click="$set('showTerminateModal', false)"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition text-lg leading-none">
                    ×
                </button>
            </div>

            <div class="erp-modal-body space-y-5">

                {{-- تحذير --}}
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 space-y-2">
                    <div class="flex items-center gap-2 text-sm font-bold text-rose-700">
                        <span class="text-base">⚠️</span> سيترتب على هذا الإجراء:
                    </div>
                    <ul class="text-xs text-rose-600 space-y-1 list-disc list-inside">
                        <li>تغيير حالة العقد إلى <strong>منتهي مبكراً</strong> أو <strong>منتهي</strong> حسب تاريخ الإنهاء</li>
                        <li>تحويل الوحدة إلى <strong>شاغرة</strong> فوراً</li>
                        <li>إلغاء الأقساط <strong>المستقبلية</strong> (بعد تاريخ الإنهاء) تلقائياً</li>
                        <li class="font-bold">لا يمكن التراجع عن هذا الإجراء</li>
                    </ul>
                </div>

                {{-- تنبيه الديون المتأخرة --}}
                @if($overdueDebt['count'] > 0)
                <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 space-y-1">
                    <div class="flex items-center gap-2 text-sm font-bold text-amber-800">
                        <span>💰</span> دفعات متأخرة مستحقة
                    </div>
                    <p class="text-xs text-amber-700">
                        يوجد <strong>{{ $overdueDebt['count'] }} قسط متأخر</strong>
                        بإجمالي <strong>{{ number_format($overdueDebt['amount'], 0) }} ر.س</strong>
                        لم يُسدَّد حتى الآن.
                    </p>
                    <p class="text-xs text-amber-700">
                        هذه الأقساط <strong>ستبقى مسجّلة كديون</strong> على المستأجر بعد إنهاء العقد
                        ويمكن تحصيلها من صفحة الدفعات.
                    </p>
                </div>
                @endif

                {{-- تاريخ الإنهاء --}}
                <div>
                    <label class="erp-label">تاريخ الإنهاء <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model="terminateForm.date"
                        class="erp-input {{ $errors->has('terminateForm.date') ? 'erp-input-error' : '' }}">
                    @error('terminateForm.date')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                    @if($tContract)
                        <p class="mt-1 text-xs text-slate-400">
                            تاريخ انتهاء العقد الأصلي: {{ $tContract->end_date?->format('Y/m/d') }}
                        </p>
                    @endif
                </div>

                {{-- سبب الإنهاء --}}
                <div>
                    <label class="erp-label">سبب الإنهاء <span class="text-rose-500">*</span></label>
                    <select wire:model="terminateForm.reason"
                        class="erp-select {{ $errors->has('terminateForm.reason') ? 'border-rose-300' : '' }}">
                        <option value="">— اختر السبب —</option>
                        <option value="mutual_agreement">اتفاق مشترك بين الطرفين</option>
                        <option value="tenant_request">طلب المستأجر</option>
                        <option value="owner_request">طلب المالك</option>
                        <option value="breach_of_contract">إخلال بشروط العقد</option>
                        <option value="non_payment">عدم السداد</option>
                        <option value="unit_sold">بيع الوحدة</option>
                        <option value="other">أخرى</option>
                    </select>
                    @error('terminateForm.reason')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ملاحظات --}}
                <div>
                    <label class="erp-label">ملاحظات <span class="text-slate-400 font-normal text-xs">(اختياري)</span></label>
                    <textarea wire:model="terminateForm.notes" rows="3"
                        class="erp-textarea"
                        placeholder="أي تفاصيل إضافية حول سبب الإنهاء..."></textarea>
                </div>

            </div>

            {{-- أزرار --}}
            <div class="erp-modal-footer">
                <button wire:click="$set('showTerminateModal', false)" class="erp-btn-soft">إلغاء</button>
                <button wire:click="terminateContract"
                    wire:loading.attr="disabled"
                    class="erp-btn-primary bg-rose-700 hover:bg-rose-800 focus:ring-rose-500">
                    <span wire:loading.remove wire:target="terminateContract">تأكيد إنهاء العقد</span>
                    <span wire:loading wire:target="terminateContract">جارٍ الإنهاء...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
