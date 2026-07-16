<div class="erp-container">
    <x-page-header title="الأرشيف" subtitle="العناصر المعطلة والمخفية من الواجهات اليومية" />

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach([
            'properties' => 'العقارات',
            'units' => 'الوحدات',
            'tenants' => 'المستأجرين',
            'contracts' => 'العقود',
        ] as $key => $label)
            <button
                wire:click="setTab('{{ $key }}')"
                @class([
                    'rounded-2xl px-5 py-2.5 text-sm font-bold transition',
                    'bg-rose-700 text-white shadow-sm' => $tab === $key,
                    'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' => $tab !== $key,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="erp-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-100 bg-slate-50 text-right text-xs font-bold text-slate-500">
                <tr>
                    <th class="px-5 py-3">الاسم</th>
                    <th class="px-5 py-3">التفاصيل</th>
                    <th class="px-5 py-3">تاريخ الأرشفة</th>
                    <th class="px-5 py-3">السبب</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @php
                    $items = match ($tab) {
                        'properties' => $properties,
                        'units' => $units,
                        'tenants' => $tenants,
                        'contracts' => $contracts,
                    };
                @endphp

                @forelse($items as $item)
                    @php
                        $type = match ($tab) {
                            'properties' => 'property',
                            'units' => 'unit',
                            'tenants' => 'tenant',
                            'contracts' => 'contract',
                        };
                        $name = match ($tab) {
                            'contracts' => $item->code,
                            default => $item->name,
                        };
                        $details = match ($tab) {
                            'properties' => trim(($item->city ?? '') . ' - ' . ($item->district ?? ''), ' -'),
                            'units' => ($item->property?->name ?? 'عقار مؤرشف') . ' / ' . ($item->internal_number ?? ''),
                            'tenants' => ($item->contracts_count ?? 0) . ' عقد',
                            'contracts' => ($item->tenant?->name ?? 'مستأجر مؤرشف') . ' / ' . ($item->unit?->name ?? 'وحدة مؤرشفة'),
                        };
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-bold text-slate-900">{{ $name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $details ?: '—' }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $item->archived_at?->format('Y/m/d H:i') }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $item->archived_reason ?? '—' }}</td>
                        <td class="px-5 py-4 text-left">
                            @php
                                $restorePermission = match($type) {
                                    'property' => 'properties.archive',
                                    'unit'     => 'units.archive',
                                    'tenant'   => 'tenants.archive',
                                    'contract' => 'contracts.terminate',
                                    default    => null,
                                };
                            @endphp
                            @if($restorePermission && auth()->user()?->can($restorePermission))
                            <button wire:click="restore('{{ $type }}', {{ $item->id }})" class="erp-btn-primary text-xs">
                                إعادة تفعيل
                            </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-400">لا توجد عناصر مؤرشفة هنا</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
