<div class="erp-container">
    <x-page-header title="التقارير" subtitle="ملخص مالي شامل — الوارد والصادر والصافي">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <select wire:model.live="propertyId" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">كل العقارات</option>
                    @foreach($properties as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="unitId" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">كل الوحدات</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">
                            {{ $unit->name }}
                            @if(! $propertyId)
                                - {{ $unit->property?->name }}
                            @endif
                        </option>
                    @endforeach
                </select>
                <input type="date" wire:model.live="dateFrom" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                <input type="date" wire:model.live="dateTo"   class="rounded-2xl border border-slate-200 px-3 py-2 text-sm">
                <div class="flex gap-2 border-r border-slate-200 pr-2 mr-1">
                    <button wire:click="exportExcel" class="rounded-2xl bg-rose-700 px-4 py-2 text-sm font-bold text-white hover:bg-rose-800 transition">
                        ⬇ Excel
                    </button>
                    <button wire:click="exportPdf" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                        ⬇ PDF
                    </button>
                </div>
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- تبويبات --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach([
            'net'         => '📊 الصافي',
            'income'      => '💰 الوارد',
            'outgoing'    => '💸 الصادر',
            'arrears'     => '⚠ المتأخرات',
            'occupancy'   => '🏠 الإشغال',
            'maintenance' => '🔧 الصيانة',
        ] as $tab => $label)
            <button wire:key="tab-btn-{{ $tab }}"
                wire:click="setTab('{{ $tab }}')"
                @class(['rounded-2xl px-5 py-2.5 text-sm font-bold transition',
                    'bg-rose-700 text-white shadow-sm'                                     => $activeTab === $tab,
                    'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'    => $activeTab !== $tab])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ───── تبويب الصافي ───── --}}
    @if($activeTab === 'net')
        <div class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="erp-card p-6 border-2 border-emerald-200 bg-emerald-50/50">
                    <div class="text-xs font-bold text-emerald-600 mb-3">💰 إجمالي الوارد</div>
                    <div class="text-3xl font-black text-emerald-700">{{ number_format($net['income_required'], 0) }}</div>
                    <div class="text-xs text-emerald-600 mt-1">مستحق · مدفوع: {{ number_format($net['income_paid'], 0) }} ر.س</div>
                    <div class="mt-3 text-xs text-emerald-600">نسبة التحصيل: <span class="font-black">{{ $net['income_rate'] }}%</span></div>
                </div>
                <div class="erp-card p-6 border-2 border-rose-200 bg-rose-50/50">
                    <div class="text-xs font-bold text-rose-600 mb-3">💸 إجمالي الصادر (ملاك)</div>
                    <div class="text-3xl font-black text-rose-700">{{ number_format($net['outgoing_required'], 0) }}</div>
                    <div class="text-xs text-rose-600 mt-1">مستحق · مدفوع: {{ number_format($net['outgoing_paid'], 0) }} ر.س</div>
                    @if($net['outgoing_overdue'] > 0)
                        <div class="mt-3 text-xs font-bold text-rose-700">⚠ متأخر: {{ number_format($net['outgoing_overdue'], 0) }} ر.س</div>
                    @endif
                </div>
                <div class="erp-card p-6 border-2 border-blue-200 bg-blue-50/50">
                    <div class="text-xs font-bold text-blue-600 mb-3">📊 الصافي</div>
                    <div class="text-3xl font-black {{ $net['net_required'] >= 0 ? 'text-blue-700' : 'text-rose-700' }}">
                        {{ number_format($net['net_required'], 0) }}
                    </div>
                    <div class="text-xs text-blue-600 mt-1">صافي مدفوع: {{ number_format($net['net_paid'], 0) }} ر.س</div>
                    <div class="mt-3 text-xs text-blue-600">هامش الربح: <span class="font-black">{{ $net['net_margin'] }}%</span></div>
                </div>
            </div>

            @if(count($netByProperty) > 0)
            <div class="erp-card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="font-bold text-slate-900">الصافي حسب العقار</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-right text-xs font-bold text-slate-500">
                        <tr>
                            <th class="px-5 py-3">العقار</th>
                            <th class="px-5 py-3">نوع الملكية</th>
                            <th class="px-5 py-3">الوارد المحصّل</th>
                            <th class="px-5 py-3">الصادر المدفوع</th>
                            <th class="px-5 py-3">الصافي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($netByProperty as $row)
                            <tr wire:key="net-prop-{{ $row['property_id'] }}" class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-bold text-slate-900">{{ $row['property_name'] }}</td>
                                <td class="px-5 py-4">
                                    @if($row['is_leased'])
                                        <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700">مستأجر · {{ $row['owner_name'] }}</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">ملك</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-bold text-emerald-700">{{ number_format($row['income_paid'], 0) }} ر.س</td>
                                <td class="px-5 py-4 font-bold text-rose-600">{{ number_format($row['outgoing_paid'], 0) }} ر.س</td>
                                <td class="px-5 py-4 font-black text-lg {{ $row['net'] >= 0 ? 'text-blue-700' : 'text-rose-700' }}">
                                    {{ number_format($row['net'], 0) }} ر.س
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    {{-- ───── تبويب الوارد ───── --}}
    @elseif($activeTab === 'income')
        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">المستحق</div><div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($income['required'], 0) }}</div><div class="text-xs text-slate-400">ر.س</div></div>
                <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">المحصّل</div><div class="text-2xl font-black text-emerald-700 mt-1">{{ number_format($income['paid'], 0) }}</div><div class="text-xs text-slate-400">ر.س</div></div>
                <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">المتبقي</div><div class="text-2xl font-black text-rose-600 mt-1">{{ number_format($income['remaining'], 0) }}</div><div class="text-xs text-slate-400">ر.س</div></div>
                <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">نسبة التحصيل</div><div class="text-2xl font-black text-blue-700 mt-1">{{ $income['collection_rate'] }}</div><div class="text-xs text-slate-400">%</div></div>
            </div>
            <div class="erp-card p-5">
                <div class="mb-2 flex justify-between text-sm font-bold">
                    <span>نسبة التحصيل</span><span>{{ $income['collection_rate'] }}%</span>
                </div>
                <div class="h-3 rounded-full bg-slate-100">
                    <div class="h-3 rounded-full bg-emerald-500 transition-all" style="width:{{ min($income['collection_rate'], 100) }}%"></div>
                </div>
            </div>
        </div>

    {{-- ───── تبويب الصادر ───── --}}
    @elseif($activeTab === 'outgoing')
        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">المستحق للملاك</div><div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($outgoing['required'], 0) }}</div><div class="text-xs text-slate-400">ر.س</div></div>
                <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">المدفوع</div><div class="text-2xl font-black text-emerald-700 mt-1">{{ number_format($outgoing['paid'], 0) }}</div><div class="text-xs text-slate-400">ر.س</div></div>
                <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">المتبقي</div><div class="text-2xl font-black text-rose-600 mt-1">{{ number_format($outgoing['remaining'], 0) }}</div><div class="text-xs text-slate-400">ر.س</div></div>
                <div class="erp-card p-5 text-center"><div class="text-xs text-rose-600 font-bold">متأخر</div><div class="text-2xl font-black text-rose-700 mt-1">{{ number_format($outgoing['overdue'], 0) }}</div><div class="text-xs text-slate-400">ر.س</div></div>
            </div>
            @if(count(array_filter($netByProperty, fn($r) => $r['is_leased'])) > 0)
            <div class="erp-card overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4"><h3 class="font-bold">دفعات الملاك حسب العقار</h3></div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-right text-xs font-bold text-slate-500">
                        <tr>
                            <th class="px-5 py-3">العقار</th>
                            <th class="px-5 py-3">المالك</th>
                            <th class="px-5 py-3">المستحق</th>
                            <th class="px-5 py-3">المدفوع</th>
                            <th class="px-5 py-3">المتبقي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach(array_filter($netByProperty, fn($r) => $r['is_leased']) as $row)
                            <tr wire:key="out-prop-{{ $row['property_id'] }}" class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-bold text-slate-900">{{ $row['property_name'] }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $row['owner_name'] }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ number_format($row['outgoing_required'] ?? 0, 0) }} ر.س</td>
                                <td class="px-5 py-4 font-bold text-emerald-700">{{ number_format($row['outgoing_paid'], 0) }} ر.س</td>
                                <td class="px-5 py-4 font-bold {{ ($row['outgoing_remaining'] ?? 0) > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                    {{ number_format($row['outgoing_remaining'] ?? 0, 0) }} ر.س
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <div class="erp-card p-12 text-center text-slate-400">لا توجد عقارات مستأجرة من ملاك</div>
            @endif
        </div>

    {{-- ───── تبويب المتأخرات ───── --}}
    @elseif($activeTab === 'arrears')
        <div class="grid gap-4 md:grid-cols-4">
            @foreach(['0_30' => '0-30 يوم', '31_60' => '31-60 يوم', '61_90' => '61-90 يوم', '90_plus' => '+90 يوم'] as $key => $label)
                <div wire:key="arrears-{{ $key }}" class="erp-card p-5 text-center">
                    <div class="text-xs text-slate-500">{{ $label }}</div>
                    <div class="text-2xl font-black {{ $arrearsAging[$key] > 0 ? 'text-rose-600' : 'text-slate-400' }} mt-1">{{ number_format($arrearsAging[$key], 0) }}</div>
                    <div class="text-xs text-slate-400">ر.س</div>
                </div>
            @endforeach
        </div>

    {{-- ───── تبويب الإشغال ───── --}}
    @elseif($activeTab === 'occupancy')
        <div class="grid gap-4 md:grid-cols-4">
            <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">إجمالي الوحدات</div><div class="text-2xl font-black mt-1">{{ $occupancy['total_units'] }}</div></div>
            <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">مؤجرة</div><div class="text-2xl font-black text-emerald-700 mt-1">{{ $occupancy['rented_units'] }}</div></div>
            <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">شاغرة</div><div class="text-2xl font-black text-amber-600 mt-1">{{ $occupancy['vacant_units'] }}</div></div>
            <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">نسبة الإشغال</div><div class="text-2xl font-black text-blue-700 mt-1">{{ $occupancy['occupancy_rate'] }}%</div></div>
        </div>

    {{-- ───── تبويب الصيانة ───── --}}
    @elseif($activeTab === 'maintenance')
        <div class="grid gap-4 md:grid-cols-4">
            <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">إجمالي الطلبات</div><div class="text-2xl font-black mt-1">{{ $maintenance['total_requests'] }}</div></div>
            <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">مفتوحة</div><div class="text-2xl font-black text-amber-600 mt-1">{{ $maintenance['open_requests'] }}</div></div>
            <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">مكتملة</div><div class="text-2xl font-black text-emerald-700 mt-1">{{ $maintenance['completed_requests'] }}</div></div>
            <div class="erp-card p-5 text-center"><div class="text-xs text-slate-500">إجمالي التكلفة</div><div class="text-2xl font-black mt-1">{{ number_format($maintenance['total_cost'], 0) }}</div><div class="text-xs text-slate-400">ر.س</div></div>
        </div>
    @endif
</div>
