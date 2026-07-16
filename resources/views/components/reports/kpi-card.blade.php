@props(['title', 'value', 'suffix' => null])

<div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-100">
    <div class="text-sm text-slate-500">{{ $title }}</div>
    <div class="mt-3 flex items-baseline gap-2">
        <div class="text-2xl font-bold text-slate-900">{{ $value }}</div>
        @if($suffix)
            <div class="text-sm text-slate-500">{{ $suffix }}</div>
        @endif
    </div>
</div>
