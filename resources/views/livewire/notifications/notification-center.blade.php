<div class="erp-container">
    <x-page-header title="مركز التنبيهات" subtitle="تنبيهات تلقائية مرتبطة بأحداث النظام" />

    {{-- ═══ إحصاء سريع ═══ --}}
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        @php
            $total30 = $groups[30]->count();
            $total60 = $groups[60]->count();
            $total90 = $groups[90]->count();
        @endphp
        <div class="erp-card p-5 text-center">
            <div class="text-2xl font-black text-slate-700">{{ $totalOpen }}</div>
            <div class="mt-1 text-xs text-slate-500">إجمالي مفتوحة</div>
        </div>
        <div class="erp-card border-2 border-rose-100 p-5 text-center">
            <div class="text-2xl font-black text-rose-600">{{ $total30 }}</div>
            <div class="mt-1 text-xs text-slate-500">خلال 30 يوم</div>
        </div>
        <div class="erp-card border-2 border-amber-100 p-5 text-center">
            <div class="text-2xl font-black text-amber-600">{{ $total60 }}</div>
            <div class="mt-1 text-xs text-slate-500">خلال 60 يوم</div>
        </div>
        <div class="erp-card border-2 border-slate-200 p-5 text-center">
            <div class="text-2xl font-black text-slate-500">{{ $total90 }}</div>
            <div class="mt-1 text-xs text-slate-500">خلال 90 يوم</div>
        </div>
    </div>

    {{-- ═══ المجموعات ═══ --}}
    @php
        $groupConfig = [
            30 => [
                'label'       => 'خلال 30 يوم',
                'sublabel'    => 'تحتاج متابعة فورية',
                'dot'         => 'bg-rose-500',
                'headerBg'    => 'bg-rose-50 border-rose-200',
                'headerText'  => 'text-rose-800',
                'badge'       => 'bg-rose-600 text-white',
                'bodyBg'      => 'bg-rose-50/40',
            ],
            60 => [
                'label'       => 'خلال 60 يوم',
                'sublabel'    => 'تستحق الانتباه',
                'dot'         => 'bg-amber-500',
                'headerBg'    => 'bg-amber-50 border-amber-200',
                'headerText'  => 'text-amber-800',
                'badge'       => 'bg-amber-500 text-white',
                'bodyBg'      => 'bg-amber-50/40',
            ],
            90 => [
                'label'       => 'خلال 90 يوم',
                'sublabel'    => 'تخطيط مسبق',
                'dot'         => 'bg-slate-400',
                'headerBg'    => 'bg-slate-50 border-slate-200',
                'headerText'  => 'text-slate-700',
                'badge'       => 'bg-slate-500 text-white',
                'bodyBg'      => 'bg-slate-50/40',
            ],
        ];

        $typeLabels = [
            'payment_overdue'         => 'دفعة متأخرة',
            'payment_due'             => 'دفعة مستأجر',
            'lease_payment_due'       => 'دفعة ملاك',
            'contract_expiring'       => 'عقد مستأجر ينتهي',
            'property_lease_expiring' => 'عقد عقار ينتهي',
            'unit_vacant'             => 'وحدة شاغرة',
        ];
        $typeIcons = [
            'payment_overdue'         => '💰',
            'payment_due'             => '💳',
            'lease_payment_due'       => '🏢',
            'contract_expiring'       => '📄',
            'property_lease_expiring' => '🔑',
            'unit_vacant'             => '🏠',
        ];
        $severityBorder = [
            'danger'  => 'border-r-4 border-rose-400',
            'warning' => 'border-r-4 border-amber-400',
            'info'    => 'border-r-4 border-sky-300',
        ];
        $severityText = [
            'danger'  => 'text-rose-700',
            'warning' => 'text-amber-700',
            'info'    => 'text-sky-700',
        ];
    @endphp

    <div class="space-y-4">
        @foreach($groupConfig as $days => $cfg)
            @php $items = $groups[$days]; @endphp

            <div
                x-data="{ open: {{ $days === 30 ? 'true' : ($items->count() > 0 ? 'false' : 'false') }} }"
                class="overflow-hidden rounded-3xl border {{ $cfg['headerBg'] }}"
            >
                {{-- رأس المجموعة --}}
                <button
                    @click="open = !open"
                    class="flex w-full items-center justify-between px-6 py-4 transition hover:brightness-95 {{ $cfg['headerBg'] }}"
                >
                    <div class="flex items-center gap-3">
                        <span class="h-3 w-3 rounded-full {{ $cfg['dot'] }}"></span>
                        <div class="text-right">
                            <div class="text-base font-bold {{ $cfg['headerText'] }}">{{ $cfg['label'] }}</div>
                            <div class="text-xs text-slate-500">{{ $cfg['sublabel'] }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($items->count() > 0)
                            <span class="rounded-full {{ $cfg['badge'] }} px-3 py-1 text-sm font-black">
                                {{ $items->count() }}
                            </span>
                        @else
                            <span class="text-sm font-semibold text-slate-400">لا شيء ✓</span>
                        @endif
                        <svg class="h-4 w-4 text-slate-400 transition-transform duration-200"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                {{-- قائمة التنبيهات --}}
                <div x-show="open" x-transition class="{{ $cfg['bodyBg'] }}">
                    @if($items->isEmpty())
                        <div class="px-6 py-8 text-center">
                            <div class="mb-2 text-3xl">✅</div>
                            <p class="text-sm font-semibold text-slate-500">لا توجد تنبيهات في هذه الفترة</p>
                        </div>
                    @else
                        <div class="divide-y divide-white/60 px-4 py-3 space-y-2">
                            @foreach($items as $notification)
                                <div class="rounded-2xl bg-white px-4 py-4 shadow-sm {{ $severityBorder[$notification->severity] ?? '' }}">
                                    <div class="flex items-start gap-3">

                                        {{-- أيقونة النوع --}}
                                        <div class="mt-0.5 shrink-0 text-xl">
                                            {{ $typeIcons[$notification->type] ?? '🔔' }}
                                        </div>

                                        {{-- المحتوى --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-bold text-slate-900">{{ $notification->title }}</span>
                                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-500">
                                                    {{ $typeLabels[$notification->type] ?? $notification->type }}
                                                </span>
                                                @if($notification->severity === 'danger')
                                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700">عاجل</span>
                                                @elseif($notification->severity === 'warning')
                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">تحذير</span>
                                                @endif
                                            </div>

                                            <p class="mt-1 text-sm {{ $severityText[$notification->severity] ?? 'text-slate-600' }}">
                                                {{ $notification->message }}
                                            </p>

                                            <div class="mt-2 flex items-center gap-3 text-xs text-slate-400">
                                                <span>📅 {{ $notification->trigger_date?->format('Y/m/d') }}</span>
                                                @if($notification->trigger_date?->isPast())
                                                    <span class="font-bold text-rose-500">
                                                        متأخر {{ $notification->trigger_date->diffForHumans() }}
                                                    </span>
                                                @else
                                                    <span>بعد {{ $notification->trigger_date->diffForHumans() }}</span>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- تنبيهات خارج نطاق 90 يوم --}}
    @php
        $beyond = $totalOpen - $total30 - $total60 - $total90;
    @endphp
    @if($beyond > 0)
        <p class="mt-4 text-center text-xs text-slate-400">
            + {{ $beyond }} تنبيه خارج نطاق الـ 90 يوم القادمة
        </p>
    @endif

    <p class="mt-4 text-center text-xs text-slate-400">
        التنبيهات تختفي تلقائياً عند حل السبب (تسديد الدفعة / إنشاء عقد / تأجير الوحدة)
    </p>
</div>
