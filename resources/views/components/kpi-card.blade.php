@props(['label', 'value', 'icon' => null, 'color' => 'slate'])

@php
$colors = [
    'slate'  => 'bg-slate-100 text-slate-600',
    'green'  => 'bg-emerald-50 text-emerald-600',
    'red'    => 'bg-rose-50 text-rose-600',
    'yellow' => 'bg-amber-50 text-amber-600',
    'blue'   => 'bg-blue-50 text-blue-600',
];
$iconClass = $colors[$color] ?? $colors['slate'];
@endphp

<div class="erp-card p-5">
    <div class="flex items-center justify-between">
        <div>
            <div class="erp-muted text-sm">{{ $label }}</div>
            <div class="mt-2 text-3xl font-black text-slate-950">{{ $value }}</div>
        </div>
        @if($icon)
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $iconClass }} text-lg">
            {{ $icon }}
        </div>
        @endif
    </div>
</div>
