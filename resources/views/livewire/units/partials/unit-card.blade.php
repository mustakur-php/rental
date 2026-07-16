@php
    $status = $unit->status?->value ?? $unit->status;
@endphp
<div class="erp-card erp-card-hover p-5">
    <div class="flex items-start justify-between">
        <div>
            <h3 class="text-lg font-black">{{ $unit->name }}</h3>
            <p class="erp-muted">{{ $unit->internal_number ?? '—' }}</p>
        </div>
        <x-unit-status :status="$unit->status" :label="$unit->status_label ?? $unit->status" />
    </div>

    <div class="mt-5 space-y-3 text-sm">
        @if($status === 'rented' && $unit->activeContract)
            <div class="flex justify-between"><span class="text-slate-500">المستأجر</span><span class="font-bold">{{ $unit->activeContract->tenant->name ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">قيمة العقد</span><span class="font-bold">{{ number_format($unit->activeContract->total_contract_amount ?? 0) }} ر.س</span></div>
            <a href="{{ route('contracts.schedule', $unit->activeContract) }}"
               class="block w-full rounded-2xl bg-rose-50 p-3 text-center text-xs font-bold text-rose-700 hover:bg-rose-100 transition">
                عرض جدول الدفع ←
            </a>
        @elseif($status === 'vacant')
            <div class="rounded-2xl bg-slate-50 p-4 text-center font-semibold text-slate-500">الوحدة شاغرة وجاهزة للتأجير</div>
        @else
            <div class="rounded-2xl bg-sky-50 p-4 text-center font-semibold text-sky-700">تحتاج متابعة حسب الحالة الحالية</div>
        @endif
    </div>

    <div class="mt-5 flex gap-2">
        <a href="{{ route('units.show', $unit) }}" class="erp-btn-soft flex-1">تفاصيل</a>
        @if($status === 'rented')
            @can('payments.create')
            <a href="{{ route('payments.tenants') }}" class="erp-btn-primary flex-1">تسجيل دفعة</a>
            @endcan
        @else
            @can('contracts.create')
            <a href="{{ route('contracts.create', ['unit_id' => $unit->id]) }}" class="erp-btn-primary flex-1">إنشاء عقد</a>
            @endcan
        @endif
        @can('units.archive')
        @if($status !== 'rented')
            <button wire:click="archiveUnit({{ $unit->id }})" wire:confirm="نقل الوحدة إلى الأرشيف؟" class="erp-btn-soft text-amber-700">أرشفة</button>
        @endif
        @endcan
    </div>
</div>
