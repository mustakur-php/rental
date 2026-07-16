<div class="erp-card mt-6 p-5">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold">Map View</h3>
            <p class="erp-muted">صورة العقار مع مواقع الوحدات. اربطها لاحقاً بجدول property_maps و unit_map_markers.</p>
        </div>
        <span class="erp-btn-soft opacity-60">عرض فقط</span>
    </div>
    <div class="relative flex min-h-[520px] items-center justify-center overflow-hidden rounded-3xl bg-slate-100">
        <div class="absolute inset-0 grid place-items-center text-slate-400">Property Map Image</div>
        <div class="relative z-10 grid grid-cols-3 gap-8">
            @foreach($units->take(9) as $unit)
                <a href="{{ route('units.show', $unit) }}"
                    class="grid h-16 w-16 place-items-center rounded-2xl bg-white/90 text-sm font-black shadow-lg ring-1 ring-slate-200 hover:bg-indigo-50">
                        {{ $unit->internal_number ?? $loop->iteration }}
                </a>
            @endforeach
        </div>
    </div>
</div>
