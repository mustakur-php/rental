<div class="erp-container">
    <x-page-header title="مركز التنبيهات" subtitle="تنبيهات تلقائية مرتبطة بأحداث النظام">
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-400">
                    @if($lastSync)
                        آخر مزامنة: {{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}
                    @else
                        لم تتم أي مزامنة بعد
                    @endif
                </span>
                <button wire:click="syncNow"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60 cursor-not-allowed"
                        class="erp-btn-soft">
                    <span wire:loading.remove wire:target="syncNow">🔄 تحديث الآن</span>
                    <span wire:loading wire:target="syncNow">جارٍ التحديث...</span>
                </button>
            </div>
        </x-slot:actions>
    </x-page-header>

    <x-sync-stale-warning>
        <x-slot:action>
            <button wire:click="syncNow"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-60 cursor-not-allowed"
                    class="shrink-0 rounded-2xl bg-rose-700 px-4 py-2 text-sm font-bold text-white hover:bg-rose-800 transition">
                <span wire:loading.remove wire:target="syncNow">🔄 تحديث الآن</span>
                <span wire:loading wire:target="syncNow">جارٍ التحديث...</span>
            </button>
        </x-slot:action>
    </x-sync-stale-warning>

    @php
        $periodTabs = [
            'all'     => ['label' => 'الكل',        'active' => 'bg-slate-700 text-white',  'idle' => 'bg-white text-slate-600 hover:bg-slate-50'],
            'overdue' => ['label' => 'متأخر',       'active' => 'bg-rose-700 text-white',   'idle' => 'bg-white text-rose-700 hover:bg-rose-50'],
            '30'      => ['label' => 'خلال 30 يوم', 'active' => 'bg-amber-500 text-white',  'idle' => 'bg-white text-amber-700 hover:bg-amber-50'],
            '60'      => ['label' => 'خلال 60 يوم', 'active' => 'bg-sky-500 text-white',    'idle' => 'bg-white text-sky-700 hover:bg-sky-50'],
            '90'      => ['label' => 'خلال 90 يوم', 'active' => 'bg-slate-500 text-white',  'idle' => 'bg-white text-slate-600 hover:bg-slate-50'],
            'snoozed' => ['label' => 'مؤجَّل',       'active' => 'bg-violet-600 text-white', 'idle' => 'bg-white text-violet-700 hover:bg-violet-50'],
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

        $hasFilters = $type || $severity || $property || $search || $period !== 'all';
    @endphp

    {{-- ═══ تبويبات الفترة (تعمل كفلتر وكإحصاء) ═══ --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach($periodTabs as $key => $tab)
            <button wire:click="setPeriod('{{ $key }}')"
                    class="flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold shadow-sm transition
                           {{ $period === $key ? $tab['active'] : $tab['idle'] }}">
                <span>{{ $tab['label'] }}</span>
                <span class="rounded-full px-2 py-0.5 text-xs font-black
                             {{ $period === $key ? 'bg-white/25' : 'bg-slate-100 text-slate-600' }}">
                    {{ $counts[$key] }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- ═══ شريط الفلاتر ═══ --}}
    <div class="mb-6 grid gap-3 md:grid-cols-4">
        <input type="search" wire:model.live.debounce.400ms="search"
               placeholder="🔍 بحث في التنبيهات..."
               class="rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm md:col-span-2">

        <select wire:model.live="type" class="rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm">
            <option value="">كل الأنواع</option>
            @foreach($typeLabels as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        <select wire:model.live="severity" class="rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm">
            <option value="">كل الدرجات</option>
            <option value="danger">عاجل</option>
            <option value="warning">تحذير</option>
            <option value="info">معلومة</option>
        </select>

        <select wire:model.live="property" class="rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm md:col-span-3">
            <option value="">كل العقارات</option>
            @foreach($properties as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>

        @if($hasFilters)
            <button wire:click="resetFilters" class="erp-btn-soft">إعادة تعيين الفلاتر</button>
        @endif
    </div>

    {{-- ═══ القائمة ═══ --}}
    <div class="space-y-3">
        @forelse($notifications as $notification)
            <div class="rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-100 {{ $severityBorder[$notification->severity] ?? '' }}">
                <div class="flex items-start gap-4">
                    <div class="mt-0.5 shrink-0 text-xl">
                        {{ $typeIcons[$notification->type] ?? '🔔' }}
                    </div>

                    <div class="min-w-0 flex-1">
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
                            @if($notification->isSnoozed())
                                <span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-bold text-violet-700">
                                    مؤجَّل حتى {{ $notification->snoozed_until->format('Y/m/d') }}
                                </span>
                            @endif
                        </div>

                        <p class="mt-1 text-sm {{ $severityText[$notification->severity] ?? 'text-slate-600' }}">
                            {{ $notification->message }}
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-400">
                            <span>📅 {{ $notification->trigger_date?->format('Y/m/d') ?? '—' }}</span>
                            @if($notification->trigger_date)
                                @if($notification->trigger_date->isPast())
                                    <span class="font-bold text-rose-500">متأخر {{ $notification->trigger_date->diffForHumans(null, true) }}</span>
                                @else
                                    <span>بعد {{ $notification->trigger_date->diffForHumans(null, true) }}</span>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-2">
                        @if($url = $notification->sourceUrl())
                            <a href="{{ $url }}"
                               class="rounded-2xl bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition">
                                عرض التفاصيل
                            </a>
                        @endif

                        @if($notification->isSnoozed())
                            <button wire:click="unsnooze({{ $notification->id }})"
                                    class="rounded-2xl bg-violet-50 px-4 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100 transition">
                                إلغاء التأجيل
                            </button>
                        @else
                            <button wire:click="snooze({{ $notification->id }})"
                                    class="rounded-2xl bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                                تأجيل {{ \App\Livewire\Notifications\NotificationCenter::SNOOZE_DAYS }} أيام
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-slate-100">
                <div class="mb-3 text-4xl">✅</div>
                <p class="text-sm font-semibold text-slate-500">
                    {{ $hasFilters ? 'لا توجد تنبيهات مطابقة للفلاتر' : 'لا توجد تنبيهات مفتوحة' }}
                </p>
                @if($hasFilters)
                    <button wire:click="resetFilters" class="erp-btn-soft mt-4">إعادة تعيين الفلاتر</button>
                @endif
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif

    <p class="mt-6 text-center text-xs text-slate-400">
        التنبيهات تختفي تلقائياً عند حل السبب (تسديد الدفعة / إنشاء عقد / تأجير الوحدة) —
        والمزامنة تعمل كل ساعة
    </p>
</div>
