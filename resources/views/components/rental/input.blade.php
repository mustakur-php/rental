@props(['label', 'type' => 'text'])

<div>
    <label class="text-sm font-bold text-slate-700">{{ $label }}</label>
    <input {{ $attributes }}
        type="{{ $type }}"
        class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-slate-400 focus:ring-slate-400">
    @error($attributes->wire('model')->value())
        <div class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</div>
    @enderror
</div>
