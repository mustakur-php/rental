<div class="erp-container space-y-6">

    {{-- رأس الصفحة --}}
    <div class="erp-card p-6">
        <h1 class="text-3xl font-black text-slate-900">سجل الحركات</h1>
        <p class="mt-1 text-sm text-slate-500">تتبع جميع العمليات التي نُفِّذت في النظام</p>
    </div>

    {{-- فلاتر --}}
    <div class="erp-card p-5">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <input wire:model.live.debounce.300ms="search"
                type="text" placeholder="بحث في الوصف..."
                class="erp-input">

            <select wire:model.live="logName" class="erp-select">
                <option value="">كل المجالات</option>
                @foreach($logNames as $ln)
                    <option value="{{ $ln }}">{{ $this->logNameLabel($ln) }}</option>
                @endforeach
            </select>

            <select wire:model.live="causerId" class="erp-select">
                <option value="">كل المستخدمين</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <input wire:model.live="dateFrom" type="date" class="erp-input flex-1 text-sm" title="من تاريخ">
                <input wire:model.live="dateTo"   type="date" class="erp-input flex-1 text-sm" title="إلى تاريخ">
            </div>
        </div>
    </div>

    {{-- جدول السجلات --}}
    <div class="erp-card overflow-hidden">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-bold text-slate-500">
                <tr>
                    <th class="px-5 py-3">الحدث</th>
                    <th class="px-5 py-3">المجال</th>
                    <th class="px-5 py-3">المنفِّذ</th>
                    <th class="hidden px-5 py-3 md:table-cell">التفاصيل</th>
                    <th class="px-5 py-3">التاريخ</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    @php
                        $ev = $this->eventLabel($log->event ?? '');
                        $props = $log->properties ?? collect();
                        $hasChanges = $props->has('old') || $props->has('attributes');
                    @endphp
                    <tr class="transition hover:bg-slate-50">

                        {{-- الحدث --}}
                        <td class="px-5 py-4">
                            <span @class([
                                'inline-flex items-center rounded-xl px-2.5 py-1 text-xs font-bold',
                                'bg-emerald-100 text-emerald-700' => $ev['color'] === 'emerald',
                                'bg-blue-100 text-blue-700'       => $ev['color'] === 'blue',
                                'bg-rose-100 text-rose-700'       => $ev['color'] === 'rose',
                                'bg-slate-100 text-slate-600'     => $ev['color'] === 'slate',
                            ])>
                                {{ $ev['label'] }}
                            </span>
                        </td>

                        {{-- المجال --}}
                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-700">{{ $this->logNameLabel($log->log_name ?? '') }}</div>
                            <div class="text-[10px] text-slate-400">{{ $log->description }}</div>
                        </td>

                        {{-- المنفِّذ --}}
                        <td class="px-5 py-4">
                            @if($log->causer)
                                <div class="flex items-center gap-2">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-xs font-black text-rose-700">
                                        {{ mb_substr($log->causer->name, 0, 1) }}
                                    </div>
                                    <span class="font-semibold text-slate-700">{{ $log->causer->name }}</span>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">النظام</span>
                            @endif
                        </td>

                        {{-- تلميح بالتغييرات --}}
                        <td class="hidden px-5 py-4 md:table-cell">
                            @if($hasChanges)
                                @php
                                    $old  = collect($props->get('old', []));
                                    $new  = collect($props->get('attributes', []));
                                    $keys = $old->keys()->merge($new->keys())->unique()->take(2);
                                @endphp
                                <div class="space-y-1">
                                    @foreach($keys as $key)
                                        <div class="text-[10px]">
                                            <span class="font-semibold text-slate-500">{{ $key }}:</span>
                                            @if($old->has($key))
                                                <span class="text-rose-500 line-through">{{ \Illuminate\Support\Str::limit((string)$old[$key], 20) }}</span>
                                                →
                                            @endif
                                            <span class="text-emerald-600 font-semibold">{{ \Illuminate\Support\Str::limit((string)($new[$key] ?? '—'), 20) }}</span>
                                        </div>
                                    @endforeach
                                    @if($old->count() > 2)
                                        <div class="text-[10px] text-slate-400">+{{ $old->count() - 2 }} حقول أخرى</div>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-slate-300">—</span>
                            @endif
                        </td>

                        {{-- التاريخ --}}
                        <td class="px-5 py-4 text-xs text-slate-500" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                            {{ $log->created_at->diffForHumans() }}
                        </td>

                        {{-- تفاصيل --}}
                        <td class="px-5 py-4">
                            @if($hasChanges)
                                <button wire:click="openDetail({{ $log->id }})"
                                    class="rounded-xl bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">
                                    الفروقات
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-slate-400">لا توجد سجلات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>


    {{-- ══════════════════════════════ مودال الفروقات ══════════════════════════════ --}}
    @if($showDetail && $detail)
        @php
            $detailEv   = $this->eventLabel($detail->event ?? '');
            $detailProps = $detail->properties ?? collect();
            $old         = collect($detailProps->get('old', []));
            $attrs        = collect($detailProps->get('attributes', []));
            $allKeys      = $old->keys()->merge($attrs->keys())->unique();
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div class="flex max-h-[85vh] w-full max-w-2xl flex-col rounded-3xl bg-white shadow-2xl">

                <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-800">تفاصيل التغييرات</h2>
                        <p class="mt-0.5 text-xs text-slate-400">
                            {{ $this->logNameLabel($detail->log_name ?? '') }} ·
                            {{ $detail->created_at->format('Y-m-d H:i:s') }} ·
                            {{ $detail->causer?->name ?? 'النظام' }}
                        </p>
                    </div>
                    <button wire:click="closeDetail"
                        class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <table class="w-full text-right text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-bold text-slate-500">
                                <th class="pb-2 pr-0">الحقل</th>
                                <th class="pb-2 px-4 text-rose-600">القيمة القديمة</th>
                                <th class="pb-2 px-4 text-emerald-600">القيمة الجديدة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($allKeys as $key)
                                @php
                                    $oldVal  = $old->has($key)   ? (string)$old[$key]   : null;
                                    $newVal  = $attrs->has($key) ? (string)$attrs[$key] : null;
                                    $changed = $oldVal !== $newVal;
                                @endphp
                                <tr @class(['bg-amber-50/50' => $changed])>
                                    <td class="py-3 font-mono text-xs text-slate-500">{{ $key }}</td>
                                    <td class="px-4 py-3 text-rose-600">
                                        {{ $oldVal !== null ? \Illuminate\Support\Str::limit($oldVal, 80) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-emerald-700">
                                        {{ $newVal !== null ? \Illuminate\Support\Str::limit($newVal, 80) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="shrink-0 border-t border-slate-100 px-6 py-4">
                    <button wire:click="closeDetail"
                        class="w-full rounded-2xl bg-slate-800 py-2.5 text-sm font-bold text-white hover:bg-slate-900 transition">
                        إغلاق
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
