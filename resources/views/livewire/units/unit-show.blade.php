<div class="erp-container space-y-6">
    <x-page-header :title="$unit->name" subtitle="تفاصيل الوحدة">
        <x-slot:actions>
            <a href="{{ route('properties.show', $unit->property) }}" class="erp-btn-soft">← العقار</a>
            <a href="{{ route('units.index') }}" class="erp-btn-soft">كل الوحدات</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="erp-card p-6 lg:col-span-2">
            <h3 class="mb-4 text-lg font-bold">بيانات الوحدة</h3>

            <div class="grid gap-4 md:grid-cols-2 text-sm">
                <div><span class="text-slate-500">العقار:</span> <b>{{ $unit->property->name }}</b></div>
                <div><span class="text-slate-500">الكود:</span> <b>{{ $unit->code ?? '—' }}</b></div>
                <div><span class="text-slate-500">الرقم الداخلي:</span> <b>{{ $unit->internal_number ?? '—' }}</b></div>
                <div><span class="text-slate-500">النوع:</span> <b>{{ $unit->type ?? '—' }}</b></div>
                <div><span class="text-slate-500">الدور:</span> <b>{{ $unit->floor ?? '—' }}</b></div>
                <div><span class="text-slate-500">المساحة:</span> <b>{{ $unit->area ?? '—' }}</b></div>
                <div><span class="text-slate-500">عداد الكهرباء:</span> <b>{{ $unit->electricity_meter ?? '—' }}</b></div>
                <div><span class="text-slate-500">عداد الماء:</span> <b>{{ $unit->water_meter ?? '—' }}</b></div>
            </div>
        </div>

        <div class="erp-card p-6">
            <h3 class="mb-4 text-lg font-bold">الإجراءات</h3>

            <div class="space-y-3">
                @if(($unit->status?->value ?? $unit->status) === 'rented')
                    <a href="{{ route('payments.tenants') }}" class="erp-btn-primary block text-center">تسجيل دفعة</a>
                @else
                    <a href="{{ route('contracts.create', ['unit_id' => $unit->id]) }}" class="erp-btn-primary block text-center">إنشاء عقد</a>
                @endif

                <a href="{{ route('properties.show', $unit->property) }}" class="erp-btn-soft block text-center">فتح العقار</a>
            </div>
        </div>
    </div>

    @if($unit->activeContract)
        <div class="erp-card p-6">
            <h3 class="mb-4 text-lg font-bold">العقد الحالي</h3>
            <div class="grid gap-4 md:grid-cols-3 text-sm">
                <div><span class="text-slate-500">المستأجر:</span> <b>{{ $unit->activeContract->tenant->name ?? '—' }}</b></div>
                <div><span class="text-slate-500">الإجمالي:</span> <b>{{ number_format($unit->activeContract->total_amount ?? 0, 2) }}</b></div>
                <div>
                    <a href="{{ route('contracts.schedule', $unit->activeContract) }}" class="erp-btn-soft">جدول الدفعات</a>
                </div>
            </div>
        </div>
    @endif
</div>