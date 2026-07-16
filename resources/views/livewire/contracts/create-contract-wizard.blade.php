<div class="erp-container max-w-3xl">
    <x-page-header title="إنشاء عقد جديد" subtitle="اتبع الخطوات لإنشاء العقد وتوليد جدول الاستحقاقات" />

    <div class="erp-card p-6">

        {{-- شريط الخطوات --}}
        <div class="mb-8 grid grid-cols-4 gap-3 text-center text-sm font-bold">
            @foreach([1 => 'المستأجر', 2 => 'الوحدة', 3 => 'العقد', 4 => 'المراجعة'] as $number => $label)
                <div @class(['rounded-2xl p-3 transition', 'bg-rose-700 text-white' => $step === $number, 'bg-emerald-100 text-emerald-700' => $step > $number, 'bg-slate-100 text-slate-500' => $step < $number])>
                    {{ $step > $number ? '✓' : $number }}. {{ $label }}
                </div>
            @endforeach
        </div>

        {{-- تنبيه الأخطاء --}}
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <div class="flex items-center gap-2 text-sm font-bold text-rose-700">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    يرجى تعبئة الحقول الإلزامية قبل المتابعة
                </div>
                <ul class="mt-2 space-y-1 text-xs text-rose-600 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- الخطوة 1: المستأجر --}}
        @if($step === 1)
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-black">اختيار المستأجر</h2>
                    <span class="text-rose-500 font-black">*</span>
                    <span class="text-xs text-slate-400">(إلزامي)</span>
                </div>

                <input wire:model.live.debounce.300ms="tenantSearch"
                    type="text"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300"
                    placeholder="ابحث باسم المستأجر أو الهوية أو الكود...">

                @if($tenant_id && $this->selectedTenant)
                    <div class="flex items-center gap-3 rounded-2xl border-2 border-emerald-400 bg-emerald-50 p-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold">✓</div>
                        <div>
                            <div class="font-bold text-emerald-900">{{ $this->selectedTenant->name }}</div>
                            <div class="text-xs text-emerald-600">{{ $this->selectedTenant->code }} · {{ $this->selectedTenant->mobile ?? 'بدون جوال' }}</div>
                        </div>
                        <button type="button" wire:click="$set('tenant_id', null)" class="mr-auto text-xs text-slate-400 hover:text-rose-500">تغيير</button>
                    </div>
                @endif

                <div class="max-h-64 overflow-y-auto space-y-2 rounded-2xl">
                    @forelse($this->tenants as $tenant)
                        {{-- الـ label مرتبط بالـ radio عبر for/id — الضغط على أي مكان في الكارد يختار المستأجر --}}
                        <label wire:key="tenant-{{ $tenant->id }}"
                            for="radio-tenant-{{ $tenant->id }}"
                            @class([
                                'flex w-full cursor-pointer items-center gap-3 rounded-2xl border-2 p-4 text-right transition',
                                'border-rose-700 bg-rose-50' => (int) $tenant_id === $tenant->id,
                                'border-slate-200 hover:border-slate-400' => (int) $tenant_id !== $tenant->id,
                            ])>
                            {{-- radio مخفي بصرياً لكن يعمل —wire:model.live يرسل القيمة لـ Livewire --}}
                            <input id="radio-tenant-{{ $tenant->id }}"
                                type="radio"
                                name="tenant_id"
                                wire:model.live="tenant_id"
                                value="{{ $tenant->id }}"
                                class="sr-only">
                            {{-- مؤشر مخصص يعكس حالة الاختيار من PHP --}}
                            <div @class([
                                'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition',
                                'border-rose-700 bg-rose-700' => (int) $tenant_id === $tenant->id,
                                'border-slate-300 bg-white'   => (int) $tenant_id !== $tenant->id,
                            ])>
                                @if((int) $tenant_id === $tenant->id)
                                    <div class="h-1.5 w-1.5 rounded-full bg-white"></div>
                                @endif
                            </div>
                            <div class="flex-1 text-right">
                                <div class="font-bold">{{ $tenant->name }}</div>
                                <div class="text-xs text-slate-400">{{ $tenant->code }} · {{ $tenant->mobile ?? 'بدون جوال' }} · {{ $tenant->type === 'company' ? 'شركة' : 'فرد' }}</div>
                            </div>
                        </label>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-400">لا يوجد مستأجرون مطابقون</p>
                    @endforelse
                </div>
                @error('tenant_id') <p class="text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
            </div>

        {{-- الخطوة 2: الوحدة --}}
        @elseif($step === 2)
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-black">اختيار الوحدة الشاغرة</h2>
                    <span class="text-rose-500 font-black">*</span>
                    <span class="text-xs text-slate-400">(إلزامي)</span>
                </div>

                <input wire:model.live.debounce.300ms="unitSearch"
                    type="text"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300"
                    placeholder="ابحث باسم الوحدة أو العقار أو الكود...">

                @if($unit_id && $this->selectedUnit)
                    <div class="flex items-center gap-3 rounded-2xl border-2 border-emerald-400 bg-emerald-50 p-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold">✓</div>
                        <div>
                            <div class="font-bold text-emerald-900">{{ $this->selectedUnit->name }}</div>
                            <div class="text-xs text-emerald-600">{{ $this->selectedUnit->property?->name }} · {{ $this->selectedUnit->code }}</div>
                        </div>
                        <button type="button" wire:click="$set('unit_id', null)" class="mr-auto text-xs text-slate-400 hover:text-rose-500">تغيير</button>
                    </div>
                @endif

                <div class="max-h-64 overflow-y-auto space-y-2 rounded-2xl">
                    @forelse($this->vacantUnits as $unit)
                        <label wire:key="unit-{{ $unit->id }}"
                            for="radio-unit-{{ $unit->id }}"
                            @class([
                                'flex w-full cursor-pointer items-center gap-3 rounded-2xl border-2 p-4 text-right transition',
                                'border-rose-700 bg-rose-50' => (int) $unit_id === $unit->id,
                                'border-slate-200 hover:border-slate-400' => (int) $unit_id !== $unit->id,
                            ])>
                            <input id="radio-unit-{{ $unit->id }}"
                                type="radio"
                                name="unit_id"
                                wire:model.live="unit_id"
                                value="{{ $unit->id }}"
                                class="sr-only">
                            <div @class([
                                'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition',
                                'border-rose-700 bg-rose-700' => (int) $unit_id === $unit->id,
                                'border-slate-300 bg-white'   => (int) $unit_id !== $unit->id,
                            ])>
                                @if((int) $unit_id === $unit->id)
                                    <div class="h-1.5 w-1.5 rounded-full bg-white"></div>
                                @endif
                            </div>
                            <div class="flex-1 text-right">
                                <div class="font-bold">{{ $unit->name }}</div>
                                <div class="text-xs text-slate-400">{{ $unit->property?->name }} · {{ $unit->code }}{{ $unit->area ? ' · '.$unit->area.' م²' : '' }}</div>
                            </div>
                        </label>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-400">لا توجد وحدات شاغرة</p>
                    @endforelse
                </div>
                @error('unit_id') <p class="text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
            </div>

        {{-- الخطوة 3: بيانات العقد --}}
        @elseif($step === 3)
            <div class="space-y-5">
                <h2 class="text-xl font-black">بيانات العقد</h2>
                <div class="grid gap-4 md:grid-cols-2">

                    <div class="md:col-span-2">
                        <label class="flex items-center gap-1 text-sm font-bold text-slate-700">
                            رقم عقد إيجار <span class="text-rose-500">*</span>
                        </label>
                        <input wire:model="ejar_number" placeholder="أدخل رقم العقد من منصة إيجار"
                            @class(['mt-2 w-full rounded-2xl border px-4 py-3 text-sm focus:outline-none focus:ring-2', 'border-rose-300 bg-rose-50 focus:ring-rose-300' => $errors->has('ejar_number'), 'border-slate-200 bg-slate-50 focus:ring-slate-300' => !$errors->has('ejar_number')])>
                        @error('ejar_number') <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-1 text-sm font-bold text-slate-700">
                            تاريخ البداية <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" wire:model.live="start_date"
                            @class(['mt-2 w-full rounded-2xl border px-4 py-3 text-sm focus:outline-none focus:ring-2', 'border-rose-300 bg-rose-50 focus:ring-rose-300' => $errors->has('start_date'), 'border-slate-200 bg-slate-50 focus:ring-slate-300' => !$errors->has('start_date')])>
                        @error('start_date') <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-1 text-sm font-bold text-slate-700">
                            تاريخ النهاية <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" wire:model.live="end_date"
                            @class(['mt-2 w-full rounded-2xl border px-4 py-3 text-sm focus:outline-none focus:ring-2', 'border-rose-300 bg-rose-50 focus:ring-rose-300' => $errors->has('end_date'), 'border-slate-200 bg-slate-50 focus:ring-slate-300' => !$errors->has('end_date')])>
                        @error('end_date') <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-1 text-sm font-bold text-slate-700">
                            دورة الفوترة <span class="text-rose-500">*</span>
                        </label>
                        <select wire:model.live="billing_cycle" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                            <option value="monthly">شهري</option>
                            <option value="two_months">كل شهرين</option>
                            <option value="quarterly">ربع سنوي</option>
                            <option value="semi_annually">نصف سنوي</option>
                            <option value="annually">سنوي</option>
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center gap-1 text-sm font-bold text-slate-700">
                            الإيجار السنوي (ر.س) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" wire:model.live="annual_rent" placeholder="0.00"
                            @class(['mt-2 w-full rounded-2xl border px-4 py-3 text-sm focus:outline-none focus:ring-2', 'border-rose-300 bg-rose-50 focus:ring-rose-300' => $errors->has('annual_rent'), 'border-slate-200 bg-slate-50 focus:ring-slate-300' => !$errors->has('annual_rent')])>
                        @error('annual_rent') <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700">نسبة الضريبة (%) <span class="text-slate-400 font-normal">(اختياري)</span></label>
                        <input type="number" step="0.01" wire:model.live="vat_rate" placeholder="15"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                    </div>

                    {{-- ملخص الحساب التلقائي --}}
                    @if($calculatedTotalAmount > 0)
                    <div class="md:col-span-2 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 space-y-2 text-sm">
                        <div class="font-bold text-emerald-800 mb-3 flex items-center gap-2">
                            <span>🧮</span> ملخص الحساب التلقائي
                        </div>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-slate-700">
                            <span class="text-slate-500">الإيجار السنوي</span>
                            <span class="font-semibold text-left">{{ number_format($annual_rent, 0) }} ر.س</span>

                            <span class="text-slate-500">مدة العقد</span>
                            <span class="font-semibold text-left">
                                {{ $contractDurationMonths }} شهر
                                @if($contractDurationMonths >= 12)
                                    ({{ number_format($contractDurationMonths / 12, 1) }} سنة)
                                @endif
                            </span>

                            <span class="text-slate-500">إجمالي قيمة العقد</span>
                            <span class="font-bold text-emerald-700 text-left">{{ number_format($calculatedTotalAmount, 0) }} ر.س</span>

                            @if($vat_rate > 0)
                            <span class="text-slate-500">الضريبة {{ $vat_rate }}%</span>
                            <span class="font-semibold text-left">{{ number_format($calculatedTotalAmount * $vat_rate / 100, 0) }} ر.س</span>
                            @endif

                            <span class="text-slate-500 font-bold">الإجمالي مع الضريبة</span>
                            <span class="font-black text-emerald-700 text-left text-base">{{ number_format($calculatedTotalAmount * (1 + $vat_rate / 100), 0) }} ر.س</span>
                        </div>
                        @if($installmentsPreviewCount > 0)
                        <div class="mt-3 pt-3 border-t border-emerald-200 flex items-center justify-between text-slate-600">
                            <span>عدد الأقساط</span>
                            <span class="font-black text-slate-800">{{ $installmentsPreviewCount }} قسط</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>قيمة كل قسط (شاملة الضريبة)</span>
                            <span class="font-black text-slate-800">{{ number_format($installmentsPreviewAmount, 0) }} ر.س</span>
                        </div>
                        @endif
                    </div>
                    @elseif($start_date && $end_date && $contractDurationMonths > 0)
                    <div class="md:col-span-2 rounded-2xl bg-slate-50 border border-slate-200 p-4 text-sm text-slate-500 flex items-center gap-2">
                        <span>ℹ️</span>
                        مدة العقد: {{ $contractDurationMonths }} شهر — أدخل الإيجار السنوي لحساب الإجمالي
                    </div>
                    @endif

                    <div class="md:col-span-2">
                        <label class="text-sm font-bold text-slate-700">ملاحظات <span class="text-slate-400 font-normal">(اختياري)</span></label>
                        <textarea wire:model="notes" rows="2"
                            class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300"
                            placeholder="أي ملاحظات إضافية..."></textarea>
                    </div>

                    {{-- رفع نسخة العقد --}}
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-1 text-sm font-bold text-slate-700">
                            نسخة العقد <span class="text-rose-500">*</span>
                        </label>
                        <div @class(['mt-2 rounded-2xl border-2 border-dashed p-6 text-center transition', 'border-rose-300 bg-rose-50' => $errors->has('contract_file'), 'border-slate-200 bg-slate-50 hover:border-slate-400' => !$errors->has('contract_file')])>
                            <input type="file" wire:model="contract_file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="contract_file_input">
                            @if($contract_file)
                                <div class="flex items-center justify-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-lg">✓</div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold text-emerald-700">{{ $contract_file->getClientOriginalName() }}</div>
                                        <div class="text-xs text-slate-400">{{ round($contract_file->getSize() / 1024, 1) }} KB</div>
                                    </div>
                                    <button type="button" wire:click="$set('contract_file', null)" class="mr-auto text-xs text-slate-400 hover:text-rose-500">حذف</button>
                                </div>
                            @else
                                <label for="contract_file_input" class="cursor-pointer">
                                    <div class="text-3xl mb-2">📎</div>
                                    <div class="text-sm font-bold text-slate-600">اضغط لرفع نسخة العقد</div>
                                    <div class="text-xs text-slate-400 mt-1">PDF أو صورة · حد أقصى 10MB</div>
                                </label>
                            @endif
                        </div>
                        @error('contract_file') <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

        {{-- الخطوة 4: المراجعة --}}
        @else
            <div class="space-y-4">
                <h2 class="text-xl font-black">مراجعة العقد قبل الإنشاء</h2>
                <div class="rounded-3xl bg-slate-50 p-6 space-y-3 text-sm">
                    <div class="flex justify-between border-b border-slate-200 pb-3">
                        <span class="text-slate-500">المستأجر</span>
                        <span class="font-bold">{{ $this->selectedTenant?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-3">
                        <span class="text-slate-500">الوحدة</span>
                        <span class="font-bold">{{ $this->selectedUnit?->name ?? '—' }} — {{ $this->selectedUnit?->property?->name ?? '' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-3">
                        <span class="text-slate-500">رقم عقد إيجار</span>
                        <span class="font-bold font-mono">{{ $ejar_number }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-3">
                        <span class="text-slate-500">مدة العقد</span>
                        <span class="font-bold">{{ $start_date }} ← {{ $end_date }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-3">
                        <span class="text-slate-500">دورة الفوترة</span>
                        <span class="font-bold">{{ ['monthly' => 'شهري', 'two_months' => 'كل شهرين', 'quarterly' => 'ربع سنوي', 'semi_annually' => 'نصف سنوي', 'annually' => 'سنوي'][$billing_cycle] ?? $billing_cycle }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-3">
                        <span class="text-slate-500">الإيجار السنوي</span>
                        <span class="font-bold">{{ number_format($annual_rent, 0) }} ر.س</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-3">
                        <span class="text-slate-500">مدة العقد</span>
                        <span class="font-bold">
                            {{ $contractDurationMonths }} شهر
                            @if($contractDurationMonths >= 12)
                                ({{ number_format($contractDurationMonths / 12, 1) }} سنة)
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-3">
                        <span class="text-slate-500">إجمالي قيمة العقد</span>
                        <span class="font-bold">{{ number_format($calculatedTotalAmount, 0) }} ر.س</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-3">
                        <span class="text-slate-500">الضريبة {{ $vat_rate }}%</span>
                        <span class="font-bold">{{ number_format($calculatedTotalAmount * $vat_rate / 100, 0) }} ر.س</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200 pb-3 pt-1">
                        <span class="font-black text-slate-900">الإجمالي مع الضريبة</span>
                        <span class="font-black text-lg text-emerald-700">{{ number_format($calculatedTotalAmount * (1 + $vat_rate / 100), 0) }} ر.س</span>
                    </div>
                    <div class="flex justify-between pt-1">
                        <span class="text-slate-500">{{ $installmentsPreviewCount }} قسط {{ ['monthly' => 'شهري', 'two_months' => 'كل شهرين', 'quarterly' => 'ربع سنوي', 'semi_annually' => 'نصف سنوي', 'annually' => 'سنوي'][$billing_cycle] ?? '' }}</span>
                        <span class="font-bold text-slate-700">{{ number_format($installmentsPreviewAmount, 0) }} ر.س / قسط</span>
                    </div>
                </div>

                @if($contract_file)
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                        <span class="text-xl">📎</span>
                        <span class="font-semibold text-slate-700">{{ $contract_file->getClientOriginalName() }}</span>
                        <span class="text-xs text-slate-400">{{ round($contract_file->getSize() / 1024, 1) }} KB</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- أزرار التنقل --}}
        <div class="mt-8 flex items-center justify-between">
            <button type="button" wire:click="previousStep"
                @class(['erp-btn-soft', 'opacity-40 cursor-not-allowed' => $step === 1])
                @disabled($step === 1)>
                ← السابق
            </button>
            @if($step < 4)
                <button type="button" wire:click="nextStep"
                    wire:loading.attr="disabled"
                    wire:target="nextStep"
                    class="erp-btn-primary">
                    <span wire:loading.remove wire:target="nextStep">التالي →</span>
                    <span wire:loading wire:target="nextStep">جارٍ التحقق...</span>
                </button>
            @else
                <button type="button" wire:click="createContract"
                    wire:loading.attr="disabled"
                    wire:target="createContract"
                    class="erp-btn-primary">
                    <span wire:loading.remove wire:target="createContract">إنشاء العقد ✓</span>
                    <span wire:loading wire:target="createContract">جارٍ الإنشاء...</span>
                </button>
            @endif
        </div>
    </div>
</div>
