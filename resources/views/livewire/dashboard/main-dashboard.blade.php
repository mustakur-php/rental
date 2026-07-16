<div class="erp-container">
    <x-page-header title="لوحة التحكم" subtitle="نظرة عامة على أداء العقارات والإيجارات" />

    {{-- KPI Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-kpi-card label="العقارات"       :value="$kpis['properties']"        icon="🏢" color="slate" />
        <x-kpi-card label="الوحدات"        :value="$kpis['units']"             icon="🏠" color="slate" />
        <x-kpi-card label="المؤجرة"        :value="$kpis['rented_units']"      icon="✓"  color="green" />
        <x-kpi-card label="الشاغرة"        :value="$kpis['vacant_units']"      icon="○"  color="slate" />
        <x-kpi-card label="العقود النشطة"  :value="$kpis['active_contracts']"  icon="📄" color="blue"  />
        <x-kpi-card label="المتأخرات"      :value="$kpis['overdue_schedules']" icon="!"  color="red"   />
        <x-kpi-card label="طلبات الصيانة" :value="$kpis['maintenance_open']"  icon="🔧" color="yellow"/>
    </div>

    {{-- الصف الثاني: الشارتات + التنبيهات --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-3">

        {{-- ═══ كارت الشارتات المزدوجة ═══ --}}
        <section class="erp-card p-6 lg:col-span-2">

            {{-- ── الدخل الشهري: دفعات المستأجرين ── --}}
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">دفعات المستأجرين</h2>
                    <p class="text-sm text-slate-400">المستحق مقابل المحصّل — آخر 6 أشهر</p>
                </div>
                <a href="{{ route('payments.tenants') }}" class="erp-btn-soft text-xs">عرض الكل ←</a>
            </div>

            <div
                wire:ignore
                x-data
                x-init="
                    $nextTick(() => {
                        new ApexCharts($el, {
                            chart: {
                                type: 'bar', height: 220,
                                fontFamily: 'Cairo, sans-serif',
                                toolbar: { show: false },
                                animations: { enabled: true, speed: 500 },
                            },
                            series: [
                                { name: 'المستحق', data: {{ Js::from($incomeChart['seriesDue']) }} },
                                { name: 'المحصّل', data: {{ Js::from($incomeChart['seriesPaid']) }} },
                            ],
                            xaxis: {
                                categories: {{ Js::from($incomeChart['labels']) }},
                                labels: { style: { fontFamily: 'Cairo, sans-serif', fontSize: '12px' } },
                            },
                            yaxis: {
                                labels: {
                                    style: { fontFamily: 'Cairo, sans-serif', fontSize: '11px' },
                                    formatter: val => new Intl.NumberFormat('ar-SA', { notation: 'compact' }).format(val),
                                },
                            },
                            colors: ['#e2e8f0', '#16a34a'],
                            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                            dataLabels: { enabled: false },
                            legend: { position: 'top', fontFamily: 'Cairo, sans-serif', fontSize: '12px' },
                            tooltip: {
                                y: { formatter: val => new Intl.NumberFormat('ar-SA').format(val) + ' ر.س' },
                                style: { fontFamily: 'Cairo, sans-serif' },
                            },
                            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                        }).render();
                    });
                "
                class="min-h-[220px]"
            ></div>

            {{-- فاصل --}}
            <div class="my-5 border-t border-slate-100"></div>

            {{-- ── دفعات الملاك ── --}}
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-800">دفعات الملاك</h2>
                    <p class="text-sm text-slate-400">المستحق مقابل المدفوع — آخر 6 أشهر</p>
                </div>
                <a href="{{ route('payments.leases') }}" class="erp-btn-soft text-xs">عرض الكل ←</a>
            </div>

            <div
                wire:ignore
                x-data
                x-init="
                    $nextTick(() => {
                        new ApexCharts($el, {
                            chart: {
                                type: 'bar', height: 220,
                                fontFamily: 'Cairo, sans-serif',
                                toolbar: { show: false },
                                animations: { enabled: true, speed: 500 },
                            },
                            series: [
                                { name: 'المستحق', data: {{ Js::from($leaseChart['seriesDue']) }} },
                                { name: 'المدفوع', data: {{ Js::from($leaseChart['seriesPaid']) }} },
                            ],
                            xaxis: {
                                categories: {{ Js::from($leaseChart['labels']) }},
                                labels: { style: { fontFamily: 'Cairo, sans-serif', fontSize: '12px' } },
                            },
                            yaxis: {
                                labels: {
                                    style: { fontFamily: 'Cairo, sans-serif', fontSize: '11px' },
                                    formatter: val => new Intl.NumberFormat('ar-SA', { notation: 'compact' }).format(val),
                                },
                            },
                            colors: ['#e2e8f0', '#be123c'],
                            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                            dataLabels: { enabled: false },
                            legend: { position: 'top', fontFamily: 'Cairo, sans-serif', fontSize: '12px' },
                            tooltip: {
                                y: { formatter: val => new Intl.NumberFormat('ar-SA').format(val) + ' ر.س' },
                                style: { fontFamily: 'Cairo, sans-serif' },
                            },
                            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                        }).render();
                    });
                "
                class="min-h-[220px]"
            ></div>

        </section>

        {{-- ═══ الشريط الجانبي: دوناتس + تنبيهات ═══ --}}
        <div class="flex flex-col gap-5">

            {{-- دوناتس الوحدات --}}
            <section class="erp-card p-5">
                <h2 class="mb-3 text-base font-bold text-slate-800">حالة الوحدات</h2>

                <div
                    wire:ignore
                    x-data
                    x-init="
                        $nextTick(() => {
                            const total  = {{ $unitsChart['rented'] + $unitsChart['vacant'] + $unitsChart['maintenance'] + $unitsChart['unavailable'] }};
                            const options = {
                                chart: {
                                    type: 'donut',
                                    height: 200,
                                    fontFamily: 'Cairo, sans-serif',
                                    toolbar: { show: false },
                                    animations: { enabled: true, speed: 500 },
                                },
                                series: [
                                    {{ $unitsChart['rented'] }},
                                    {{ $unitsChart['vacant'] }},
                                    {{ $unitsChart['maintenance'] }},
                                    {{ $unitsChart['unavailable'] }},
                                ],
                                labels: ['مؤجرة', 'شاغرة', 'صيانة', 'أخرى'],
                                colors: ['#be123c', '#94a3b8', '#f59e0b', '#cbd5e1'],
                                dataLabels: { enabled: false },
                                legend: {
                                    position: 'bottom',
                                    fontFamily: 'Cairo, sans-serif',
                                    fontSize: '12px',
                                },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: '68%',
                                            labels: {
                                                show: true,
                                                total: {
                                                    show: true,
                                                    label: 'الإجمالي',
                                                    fontFamily: 'Cairo, sans-serif',
                                                    fontSize: '13px',
                                                    color: '#64748b',
                                                    formatter: () => total,
                                                },
                                                value: {
                                                    fontFamily: 'Cairo, sans-serif',
                                                    fontSize: '18px',
                                                    fontWeight: '800',
                                                    color: '#0f172a',
                                                },
                                            },
                                        },
                                    },
                                },
                                tooltip: {
                                    y: { formatter: val => val + ' وحدة' },
                                    style: { fontFamily: 'Cairo, sans-serif' },
                                },
                            };

                            const chart = new ApexCharts($el, options);
                            chart.render();
                        });
                    "
                    class="min-h-[200px]"
                ></div>
            </section>

            {{-- التنبيهات حسب المدة --}}
            <section class="erp-card overflow-hidden">

                {{-- رأس --}}
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-800">التنبيهات القادمة</h2>
                    <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-rose-700 hover:underline">كل التنبيهات ←</a>
                </div>

                @php
                    $alertGroups = [
                        30 => ['label' => 'خلال 30 يوم', 'bg' => 'bg-rose-50',   'text' => 'text-rose-700',   'badge' => 'bg-rose-600',   'dot' => 'bg-rose-500'],
                        60 => ['label' => 'خلال 60 يوم', 'bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'badge' => 'bg-amber-500',  'dot' => 'bg-amber-400'],
                        90 => ['label' => 'خلال 90 يوم', 'bg' => 'bg-slate-50',  'text' => 'text-slate-600',  'badge' => 'bg-slate-500',  'dot' => 'bg-slate-400'],
                    ];
                @endphp

                <div class="divide-y divide-slate-100">
                    @foreach($alertGroups as $days => $style)
                        @php
                            $group   = $kpis['alerts'][$days];
                            $total   = $group['contracts'] + $group['tenant_payments'] + $group['lease_payments'];
                        @endphp

                        <div x-data="{ open: {{ $days === 30 ? 'true' : 'false' }} }">

                            {{-- رأس المجموعة --}}
                            <button @click="open = !open"
                                class="flex w-full items-center justify-between px-5 py-3.5 transition hover:bg-slate-50/80">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-2 w-2 rounded-full {{ $style['dot'] }}"></span>
                                    <span class="text-sm font-bold text-slate-700">{{ $style['label'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($total > 0)
                                        <span class="rounded-full {{ $style['badge'] }} px-2 py-0.5 text-xs font-bold text-white">{{ $total }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">لا شيء</span>
                                    @endif
                                    <svg class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                         fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            {{-- تفاصيل المجموعة --}}
                            <div x-show="open" x-transition class="{{ $style['bg'] }} px-5 pb-3 pt-1 space-y-2">

                                {{-- عقود تنتهي --}}
                                <div class="flex items-center justify-between rounded-xl bg-white/70 px-3 py-2.5 text-xs">
                                    <span class="flex items-center gap-1.5 {{ $style['text'] }} font-semibold">
                                        <span>📄</span> عقود تنتهي
                                    </span>
                                    <a href="{{ route('contracts.index') }}"
                                       class="font-black {{ $group['contracts'] > 0 ? $style['text'] : 'text-slate-400' }} hover:underline">
                                        {{ $group['contracts'] }}
                                    </a>
                                </div>

                                {{-- دفعات مستأجرين --}}
                                <div class="flex items-center justify-between rounded-xl bg-white/70 px-3 py-2.5 text-xs">
                                    <span class="flex items-center gap-1.5 {{ $style['text'] }} font-semibold">
                                        <span>💰</span> دفعات مستأجرين
                                    </span>
                                    <a href="{{ route('payments.tenants') }}"
                                       class="font-black {{ $group['tenant_payments'] > 0 ? $style['text'] : 'text-slate-400' }} hover:underline">
                                        {{ $group['tenant_payments'] }}
                                    </a>
                                </div>

                                {{-- دفعات ملاك --}}
                                <div class="flex items-center justify-between rounded-xl bg-white/70 px-3 py-2.5 text-xs">
                                    <span class="flex items-center gap-1.5 {{ $style['text'] }} font-semibold">
                                        <span>🏢</span> دفعات ملاك
                                    </span>
                                    <a href="{{ route('payments.leases') }}"
                                       class="font-black {{ $group['lease_payments'] > 0 ? $style['text'] : 'text-slate-400' }} hover:underline">
                                        {{ $group['lease_payments'] }}
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                @can('contracts.create')
                <div class="border-t border-slate-100 px-5 py-4">
                    <a href="{{ route('contracts.create') }}" class="erp-btn-primary block w-full text-center text-sm">
                        + إنشاء عقد جديد
                    </a>
                </div>
                @endcan

            </section>

        </div>
    </div>
</div>
