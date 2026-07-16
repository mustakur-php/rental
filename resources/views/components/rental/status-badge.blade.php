@props(['status'])

@php
    $labels = [
        'vacant' => 'شاغرة',
        'rented' => 'مؤجرة',
        'reserved' => 'محجوزة',
        'maintenance' => 'تحت الصيانة',
        'unavailable' => 'غير متاحة',
        'active' => 'نشط',
    ];

    $classes = [
        'vacant' => 'bg-slate-100 text-slate-700',
        'rented' => 'bg-emerald-50 text-emerald-700',
        'reserved' => 'bg-sky-50 text-sky-700',
        'maintenance' => 'bg-amber-50 text-amber-700',
        'unavailable' => 'bg-rose-50 text-rose-700',
        'active' => 'bg-emerald-50 text-emerald-700',
    ];

    $value = is_object($status) && property_exists($status, 'value') ? $status->value : $status;
@endphp

<span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $classes[$value] ?? 'bg-slate-100 text-slate-700' }}">
    {{ $labels[$value] ?? $value }}
</span>
