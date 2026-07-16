@props(['unit'])

<div class="rounded-[2rem] border border-slate-100 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="text-xs font-bold text-slate-400">{{ $unit->code }}</div>
            <h3 class="mt-1 text-xl font-black text-slate-900">{{ $unit->name }}</h3>
            <p class="mt-1 text-sm text-slate-500">
                رقم داخلي: {{ $unit->internal_number ?: '-' }}
            </p>
        </div>

        <x-rental.status-badge :status="$unit->status->value ?? $unit->status" />
    </div>

    <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
        <div class="rounded-2xl bg-slate-50 p-3">
            <div class="text-xs font-bold text-slate-400">النوع</div>
            <div class="mt-1 font-black text-slate-900">{{ $unit->type }}</div>
        </div>
        <div class="rounded-2xl bg-slate-50 p-3">
            <div class="text-xs font-bold text-slate-400">المساحة</div>
            <div class="mt-1 font-black text-slate-900">{{ $unit->area ?: '-' }}</div>
        </div>
        <div class="rounded-2xl bg-slate-50 p-3">
            <div class="text-xs font-bold text-slate-400">الدور</div>
            <div class="mt-1 font-black text-slate-900">{{ $unit->floor ?: '-' }}</div>
        </div>
        <div class="rounded-2xl bg-slate-50 p-3">
            <div class="text-xs font-bold text-slate-400">عداد الكهرباء</div>
            <div class="mt-1 truncate font-black text-slate-900">{{ $unit->electricity_meter ?: '-' }}</div>
        </div>
    </div>

    <div class="mt-5 flex gap-2">
        <button wire:click="openEditUnitModal({{ $unit->id }})"
            class="flex-1 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white">
            تعديل
        </button>

        @php $statusStr = $unit->status instanceof \BackedEnum ? $unit->status->value : $unit->status; @endphp
        @if($statusStr === 'vacant')
            <a href="{{ route('contracts.create', ['unit_id' => $unit->id]) }}"
               class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                إنشاء عقد
            </a>
        @else
            <a href="{{ route('units.show', $unit) }}"
                class="erp-btn-soft flex-1">
                    التفاصيل
            </a>
        @endif
    </div>
</div>
