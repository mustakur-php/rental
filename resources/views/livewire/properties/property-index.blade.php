<div class="erp-container">

    {{-- Header --}}
    <x-page-header title="العقارات" subtitle="إدارة العقارات المملوكة والمستأجرة مع إحصائياتها">
        <x-slot:actions>
            @can('properties.create')
            <button wire:click="openCreateModal" class="erp-btn-primary">+ إضافة عقار</button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- فلاتر + تبديل العرض --}}
    <div class="erp-card mb-6 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <input wire:model.live.debounce.300ms="search"
                class="erp-input flex-1"
                placeholder="ابحث باسم العقار، الرقم، المدينة، الحي...">
            <select wire:model.live="status" class="erp-select w-auto">
                <option value="">كل الحالات</option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
                <option value="maintenance">تحت الصيانة</option>
            </select>
            {{-- تبديل العرض --}}
            <div class="flex rounded-2xl border border-slate-200 overflow-hidden">
                <button wire:click="setViewMode('cards')"
                    @class(['px-4 py-2.5 text-sm font-bold transition', 'bg-rose-700 text-white' => $viewMode === 'cards', 'bg-white text-slate-600 hover:bg-slate-50' => $viewMode !== 'cards'])>
                    كروت
                </button>
                <button wire:click="setViewMode('table')"
                    @class(['px-4 py-2.5 text-sm font-bold transition', 'bg-rose-700 text-white' => $viewMode === 'table', 'bg-white text-slate-600 hover:bg-slate-50' => $viewMode !== 'table'])>
                    جدول
                </button>
            </div>
        </div>
    </div>

    {{-- عرض الكروت --}}
    @if($viewMode === 'cards')
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse($properties as $property)
                <div class="erp-card p-5 transition hover:-translate-y-1 hover:shadow-xl">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-400">{{ $property->code }}</span>
                                @if(($property->ownership_type ?? 'owned') === 'leased')
                                    <span class="rounded-full bg-purple-50 px-2 py-0.5 text-xs font-bold text-purple-700">مستأجر</span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700">ملك</span>
                                @endif
                            </div>
                            <h2 class="mt-1 text-lg font-black text-slate-900">{{ $property->name }}</h2>
                            @if($property->city || $property->district)
                                <p class="text-sm text-slate-500">{{ $property->city }}{{ $property->district ? ' - '.$property->district : '' }}</p>
                            @endif
                            @if($property->address)
                                <a href="https://maps.google.com/?q={{ urlencode($property->address) }}" target="_blank"
                                    class="mt-1 inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                    📍 {{ Str::limit($property->address, 40) }}
                                </a>
                            @endif
                        </div>
                        <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $property->status === 'active', 'bg-slate-100 text-slate-600' => $property->status !== 'active'])>
                            {{ ['active' => 'نشط', 'inactive' => 'غير نشط', 'maintenance' => 'صيانة'][$property->status] ?? $property->status }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <x-rental.stat-mini label="الوحدات"  :value="$property->units_count" />
                        <x-rental.stat-mini label="مؤجرة"   :value="$property->rented_units_count" />
                        <x-rental.stat-mini label="شاغرة"   :value="$property->vacant_units_count" />
                    </div>

                    @php $occ = $property->units_count ? round(($property->rented_units_count / $property->units_count) * 100) : 0; @endphp
                    <div class="mt-4">
                        <div class="mb-1 flex justify-between text-xs text-slate-500">
                            <span>الإشغال</span><span>{{ $occ }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-rose-600 transition-all" style="width:{{ $occ }}%"></div>
                        </div>
                    </div>

                    @if(($property->ownership_type ?? 'owned') === 'leased' && $property->activeLease)
                        <div class="mt-3 rounded-2xl bg-purple-50 px-4 py-3 text-xs text-purple-700">
                            <span class="font-bold">مالك:</span> {{ $property->activeLease->owner_name }}
                            · ينتهي {{ $property->activeLease->end_date->format('Y/m/d') }}
                        </div>
                    @endif

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('properties.show', $property) }}" class="flex-1 rounded-2xl bg-rose-700 py-2.5 text-center text-sm font-bold text-white">فتح</a>
                        @can('properties.edit')
                        <button wire:click="openEditModal({{ $property->id }})" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700">تعديل</button>
                        @endcan
                        @can('properties.archive')
                        <button wire:click="archiveProperty({{ $property->id }})" wire:confirm="نقل العقار ووحداته إلى الأرشيف؟" class="rounded-2xl border border-amber-200 px-4 py-2.5 text-sm font-bold text-amber-700">أرشفة</button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="col-span-full erp-card p-16 text-center">
                    <div class="text-4xl mb-4">🏢</div>
                    <div class="text-lg font-bold text-slate-700">لا توجد عقارات</div>
                    <button wire:click="openCreateModal" class="erp-btn-primary mt-5">+ إضافة عقار</button>
                </div>
            @endforelse
        </div>

    {{-- عرض الجدول --}}
    @else
        @if($properties->isEmpty())
            <div class="erp-card p-16 text-center">
                <div class="text-4xl mb-4">🏢</div>
                <div class="text-lg font-bold text-slate-700">لا توجد عقارات</div>
                <button wire:click="openCreateModal" class="erp-btn-primary mt-5">+ إضافة عقار</button>
            </div>
        @else
            <div class="erp-card overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-right text-xs font-bold text-slate-500">
                        <tr>
                            <th class="px-5 py-3">الكود</th>
                            <th class="px-5 py-3">الاسم</th>
                            <th class="px-5 py-3">النوع</th>
                            <th class="px-5 py-3">العنوان</th>
                            <th class="px-5 py-3">الوحدات</th>
                            <th class="px-5 py-3">الإشغال</th>
                            <th class="px-5 py-3">الملكية</th>
                            <th class="px-5 py-3">الحالة</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($properties as $property)
                        @php $occ = $property->units_count ? round(($property->rented_units_count / $property->units_count) * 100) : 0; @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-5 py-4 font-mono text-xs text-slate-400">{{ $property->code }}</td>
                                <td class="px-5 py-4 font-bold text-slate-900">{{ $property->name }}</td>
                                <td class="px-5 py-4 text-slate-600 text-xs">
                                    {{ ['commercial_complex' => 'مجمع تجاري', 'residential_building' => 'عمارة سكنية', 'villas' => 'فلل', 'shops' => 'محلات', 'offices' => 'مكاتب', 'warehouses' => 'مستودعات'][$property->type] ?? $property->type }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($property->address)
                                        <a href="https://maps.google.com/?q={{ urlencode($property->city . ' ' . $property->district . ' ' . $property->address) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-blue-600 hover:underline text-xs">
                                            📍 {{ $property->city }}{{ $property->district ? ' - '.$property->district : '' }}
                                            @if($property->address)
                                                <br><span class="text-slate-400">{{ Str::limit($property->address, 35) }}</span>
                                            @endif
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-xs">{{ $property->city }}{{ $property->district ? ' - '.$property->district : '' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="font-bold">{{ $property->rented_units_count }}</span>
                                    <span class="text-slate-400">/{{ $property->units_count }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-16 rounded-full bg-slate-100">
                                            <div class="h-1.5 rounded-full bg-rose-600" style="width:{{ $occ }}%"></div>
                                        </div>
                                        <span class="text-xs text-slate-500">{{ $occ }}%</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @if(($property->ownership_type ?? 'owned') === 'leased')
                                        <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700">مستأجر</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">ملك</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $property->status === 'active', 'bg-slate-100 text-slate-600' => $property->status !== 'active'])>
                                        {{ ['active' => 'نشط', 'inactive' => 'غير نشط', 'maintenance' => 'صيانة'][$property->status] ?? $property->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-left">
                                    <div class="flex gap-2">
                                        <a href="{{ route('properties.show', $property) }}" class="erp-btn-primary text-xs">فتح</a>
                                        @can('properties.edit')
                                        <button wire:click="openEditModal({{ $property->id }})" class="erp-btn-soft text-xs">تعديل</button>
                                        @endcan
                                        @can('properties.archive')
                                        <button wire:click="archiveProperty({{ $property->id }})" wire:confirm="نقل العقار ووحداته إلى الأرشيف؟" class="erp-btn-soft text-xs text-amber-700">أرشفة</button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    <div class="mt-4">{{ $properties->links() }}</div>

    {{-- مودال الإضافة / التعديل --}}
    @if($showCreateModal || $showEditModal)
        <x-rental.modal>
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-lg font-bold">{{ $showCreateModal ? 'إضافة عقار جديد' : 'تعديل العقار' }}</h2>
            </div>
            <div class="max-h-[75vh] overflow-y-auto p-6 space-y-5">

                {{-- بيانات أساسية --}}
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-sm font-bold text-slate-700">الشركة *</label>
                        <select wire:model="form.company_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            <option value="">اختر الشركة</option>
                            @foreach($companies as $co)
                                <option value="{{ $co->id }}">{{ $co->name }} ({{ $co->code }})</option>
                            @endforeach
                        </select>
                        @error('form.company_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <x-rental.input label="رقم العقار *" wire:model="form.code" />
                    <x-rental.input label="اسم العقار *" wire:model="form.name" />
                    <div>
                        <label class="text-sm font-bold text-slate-700">نوع العقار *</label>
                        <select wire:model="form.type" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            <option value="commercial_complex">مجمع تجاري</option>
                            <option value="residential_building">عمارة سكنية</option>
                            <option value="villas">فلل</option>
                            <option value="shops">محلات</option>
                            <option value="offices">مكاتب</option>
                            <option value="warehouses">مستودعات</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-slate-700">الحالة</label>
                        <select wire:model="form.status" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                            <option value="maintenance">تحت الصيانة</option>
                        </select>
                    </div>
                    <x-rental.input label="المدينة" wire:model="form.city" />
                    <x-rental.input label="الحي" wire:model="form.district" />
                    <div class="md:col-span-2">
                        <x-rental.input label="العنوان" wire:model="form.address" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-bold text-slate-700">الوصف</label>
                        <textarea wire:model="form.description" rows="2" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm"></textarea>
                    </div>
                </div>

                {{-- نوع الملكية --}}
                <div>
                    <label class="text-sm font-bold text-slate-700">نوع الملكية *</label>
                    <div class="mt-2 flex gap-3">
                        <label @class(['flex flex-1 cursor-pointer items-center gap-3 rounded-2xl border-2 p-4 transition', 'border-slate-900 bg-slate-50' => $form['ownership_type'] === 'owned', 'border-slate-200' => $form['ownership_type'] !== 'owned'])>
                            <input type="radio" wire:model.live="form.ownership_type" value="owned" class="accent-slate-900">
                            <div>
                                <div class="font-bold text-sm">🏢 ملك</div>
                                <div class="text-xs text-slate-400">العقار مملوك للشركة</div>
                            </div>
                        </label>
                        <label @class(['flex flex-1 cursor-pointer items-center gap-3 rounded-2xl border-2 p-4 transition', 'border-purple-600 bg-purple-50' => $form['ownership_type'] === 'leased', 'border-slate-200' => $form['ownership_type'] !== 'leased'])>
                            <input type="radio" wire:model.live="form.ownership_type" value="leased" class="accent-purple-600">
                            <div>
                                <div class="font-bold text-sm">🔑 مستأجر</div>
                                <div class="text-xs text-slate-400">العقار مستأجر من مالك</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- بيانات عقد الإيجار (تظهر فقط إذا مستأجر) --}}
                @if($form['ownership_type'] === 'leased')
                    <div class="rounded-3xl border-2 border-purple-200 bg-purple-50/50 p-5 space-y-4">
                        <h3 class="font-black text-purple-900">بيانات عقد الإيجار مع المالك</h3>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-rental.input label="اسم المالك *" wire:model="form.owner_name" />
                            <x-rental.input label="جوال المالك" wire:model="form.owner_mobile" />
                            <x-rental.input label="IBAN المالك" wire:model="form.owner_iban" />
                            <x-rental.input label="رقم عقد الإيجار" wire:model="form.lease_contract_number" />
                            <div>
                                <label class="text-sm font-bold text-slate-700">تاريخ البداية *</label>
                                <input type="date" wire:model.live="form.lease_start_date" class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm">
                                @error('form.lease_start_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-bold text-slate-700">تاريخ النهاية *</label>
                                <input type="date" wire:model.live="form.lease_end_date" class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm">
                                @error('form.lease_end_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-bold text-slate-700">الإيجار السنوي (ر.س) *</label>
                                <input type="number" step="0.01" wire:model.live="form.lease_annual_rent"
                                    class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm {{ $errors->has('form.lease_annual_rent') ? 'border-rose-300 bg-rose-50' : '' }}"
                                    placeholder="0.00">
                                @error('form.lease_annual_rent') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-bold text-slate-700">دورة الدفع للمالك</label>
                                <select wire:model.live="form.lease_payment_cycle" class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm">
                                    <option value="monthly">شهري</option>
                                    <option value="two_months">كل شهرين</option>
                                    <option value="quarterly">ربع سنوي</option>
                                    <option value="semi_annually">نصف سنوي</option>
                                    <option value="annually">سنوي</option>
                                </select>
                            </div>

                            {{-- تصاعد الإيجار --}}
                            <div class="md:col-span-2">
                                <div class="flex items-center justify-between rounded-2xl border border-purple-200 bg-white px-5 py-4">
                                    <div>
                                        <div class="text-sm font-bold text-slate-700">تصاعد الإيجار</div>
                                        <div class="text-xs text-slate-400 mt-0.5">تحديد فترات بإيجار متصاعد مع كل فترة</div>
                                    </div>
                                    <button type="button" wire:click="$toggle('has_lease_escalation')"
                                        @class([
                                            'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                                            'bg-purple-600' => $has_lease_escalation,
                                            'bg-slate-200'  => !$has_lease_escalation,
                                        ])>
                                        <span @class([
                                            'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            'translate-x-5' => $has_lease_escalation,
                                            'translate-x-0' => !$has_lease_escalation,
                                        ])></span>
                                    </button>
                                </div>
                            </div>

                            @if($has_lease_escalation)
                            <div class="md:col-span-2 space-y-3">
                                <div class="overflow-x-auto rounded-2xl border border-purple-200">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-purple-50 text-purple-700 text-right">
                                                <th class="px-4 py-3 font-bold">#</th>
                                                <th class="px-4 py-3 font-bold">المدة (شهر)</th>
                                                <th class="px-4 py-3 font-bold">نسبة الزيادة %</th>
                                                <th class="px-4 py-3 font-bold">الإيجار السنوي</th>
                                                <th class="px-4 py-3 font-bold">إجمالي الفترة</th>
                                                <th class="px-4 py-3"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($lease_periods as $i => $period)
                                            <tr wire:key="lp-{{ $i }}">
                                                <td class="px-4 py-3 font-bold text-slate-400">{{ $i + 1 }}</td>
                                                <td class="px-4 py-3">
                                                    <input type="number" min="1"
                                                        wire:model.live="lease_periods.{{ $i }}.duration_months"
                                                        class="w-24 rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 text-center">
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if($i === 0)
                                                        <span class="text-slate-400 text-xs px-3">—</span>
                                                    @else
                                                        <div class="flex items-center gap-1">
                                                            <input type="number" min="0" step="0.01"
                                                                wire:model.live="lease_periods.{{ $i }}.increase_pct"
                                                                class="w-24 rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 text-center">
                                                            <span class="text-slate-400 text-xs">%</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 font-semibold text-slate-700">
                                                    {{ number_format($period['annual_amount'] ?? 0, 0) }} ر.س
                                                </td>
                                                <td class="px-4 py-3 text-purple-700 font-bold">
                                                    {{ number_format(($period['annual_amount'] ?? 0) * ((int)($period['duration_months'] ?? 0) / 12), 0) }} ر.س
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if(count($lease_periods) > 1)
                                                    <button type="button" wire:click="removeLeasePeriod({{ $i }})"
                                                        class="rounded-full p-1 text-rose-400 hover:bg-rose-50 hover:text-rose-600 transition">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" wire:click="addLeasePeriod"
                                    class="flex items-center gap-2 rounded-2xl border border-dashed border-purple-300 bg-purple-50 px-5 py-3 text-sm font-bold text-purple-700 hover:bg-purple-100 transition w-full justify-center">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    إضافة فترة جديدة
                                </button>
                                @error('lease_periods')
                                    <p class="text-xs font-bold text-rose-600 flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            @endif

                            {{-- ملخص الحساب التلقائي --}}
                            @if($computedLeaseTotalAmount > 0)
                            <div class="md:col-span-2 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 space-y-1.5 text-sm">
                                <div class="font-bold text-emerald-800 mb-2 flex items-center gap-2">
                                    <span>🧮</span> ملخص الحساب التلقائي
                                </div>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-slate-700">
                                    <span class="text-slate-500">الإيجار السنوي</span>
                                    <span class="font-semibold text-left">{{ number_format((float)$form['lease_annual_rent'], 0) }} ر.س</span>

                                    <span class="text-slate-500">مدة العقد</span>
                                    <span class="font-semibold text-left">
                                        {{ $leaseDurationMonths }} شهر
                                        @if($leaseDurationMonths >= 12)
                                            ({{ number_format($leaseDurationMonths / 12, 1) }} سنة)
                                        @endif
                                    </span>

                                    <span class="text-slate-500 font-bold">إجمالي قيمة العقد</span>
                                    <span class="font-black text-emerald-700 text-left">{{ number_format($computedLeaseTotalAmount, 0) }} ر.س</span>
                                </div>
                                @if($leaseInstallmentsPreviewCount > 0)
                                <div class="mt-2 pt-2 border-t border-emerald-200 grid grid-cols-2 gap-x-4 text-slate-600 text-xs">
                                    <span>عدد الأقساط</span>
                                    <span class="font-black text-slate-800 text-left">{{ $leaseInstallmentsPreviewCount }} قسط</span>
                                    <span>قيمة كل قسط</span>
                                    <span class="font-black text-slate-800 text-left">{{ number_format($leaseInstallmentsPreviewAmount, 0) }} ر.س</span>
                                </div>
                                @endif
                            </div>
                            @elseif($form['lease_start_date'] && $form['lease_end_date'] && $leaseDurationMonths > 0)
                            <div class="md:col-span-2 rounded-2xl bg-slate-50 border border-slate-200 p-3 text-xs text-slate-500 flex items-center gap-2">
                                <span>ℹ️</span>
                                مدة العقد: {{ $leaseDurationMonths }} شهر — أدخل الإيجار السنوي لحساب الإجمالي
                            </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        <ul class="text-xs text-rose-600 space-y-1 list-disc list-inside">
                            @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button wire:click="$set('{{ $showCreateModal ? 'showCreateModal' : 'showEditModal' }}', false)" class="erp-btn-soft">إلغاء</button>
                <button wire:click="{{ $showCreateModal ? 'createProperty' : 'updateProperty' }}" class="erp-btn-primary">حفظ</button>
            </div>
        </x-rental.modal>
    @endif
</div>
