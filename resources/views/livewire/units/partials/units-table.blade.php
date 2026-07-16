<div class="erp-card mt-6 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-100 text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="px-5 py-4 text-right">الوحدة</th>
                <th class="px-5 py-4 text-right">الحالة</th>
                <th class="px-5 py-4 text-right">المستأجر الحالي</th>
                <th class="px-5 py-4 text-right">عداد الكهرباء</th>
                <th class="px-5 py-4 text-right">إجراء</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @foreach($units as $unit)
                <tr>
                    <td class="px-5 py-4 font-bold">{{ $unit->name }}</td>
                    <td class="px-5 py-4"><x-unit-status :status="$unit->status" :label="$unit->status_label ?? $unit->status" /></td>
                    <td class="px-5 py-4">{{ $unit->activeContract->tenant->name ?? '—' }}</td>
                    <td class="px-5 py-4">{{ $unit->electricity_meter ?? '—' }}</td>
                    <td class="px-5 py-4"><a href="{{ route('properties.show', $unit->property) }}" class="erp-btn-soft">فتح</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
