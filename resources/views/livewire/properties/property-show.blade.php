<div class="erp-container space-y-6">

    {{-- رأس الصفحة --}}
    <div class="erp-card p-6">
        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
            <div>
                <a href="{{ route('properties.index') }}" class="text-sm font-bold text-slate-500">← العودة للعقارات</a>
                <div class="mt-3 text-xs font-bold text-slate-400">{{ $property->code }}</div>
                <h1 class="mt-1 text-3xl font-black text-slate-900">{{ $property->name }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $property->city }} @if($property->district) - {{ $property->district }} @endif
                </p>
            </div>

            @can('units.create')
            <button wire:click="openCreateUnitModal" class="erp-btn-primary">
                + إضافة وحدة
            </button>
            @endcan
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <x-rental.kpi-card title="إجمالي الوحدات" :value="$property->units_count" />
            <x-rental.kpi-card title="المؤجرة" :value="$property->rented_units_count" />
            <x-rental.kpi-card title="الشاغرة" :value="$property->vacant_units_count" />
            <x-rental.kpi-card title="تحت الصيانة" :value="$property->maintenance_units_count" />
        </div>
    </div>

    {{-- شريط الفلترة والعرض --}}
    <div class="erp-card p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="grid flex-1 grid-cols-1 gap-3 md:grid-cols-2">
                <input wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="بحث في الوحدات..."
                    class="erp-input">

                <select wire:model.live="status" class="erp-select">
                    <option value="">كل الحالات</option>
                    <option value="vacant">شاغرة</option>
                    <option value="rented">مؤجرة</option>
                    <option value="reserved">محجوزة</option>
                    <option value="maintenance">تحت الصيانة</option>
                    <option value="unavailable">غير متاحة</option>
                </select>
            </div>

            <div class="inline-flex rounded-2xl bg-slate-100 p-1 text-sm font-semibold">
                <button wire:click="setViewMode('cards')"
                    @class(['rounded-xl px-4 py-2 transition', 'bg-white shadow text-slate-900' => $viewMode === 'cards', 'text-slate-500 hover:text-slate-700' => $viewMode !== 'cards'])>
                    كروت
                </button>
                <button wire:click="setViewMode('table')"
                    @class(['rounded-xl px-4 py-2 transition', 'bg-white shadow text-slate-900' => $viewMode === 'table', 'text-slate-500 hover:text-slate-700' => $viewMode !== 'table'])>
                    جدول
                </button>
                <button wire:click="setViewMode('map')"
                    @class(['rounded-xl px-4 py-2 transition', 'bg-white shadow text-slate-900' => $viewMode === 'map', 'text-slate-500 hover:text-slate-700' => $viewMode !== 'map'])>
                    خريطة
                </button>
            </div>
        </div>
    </div>

    {{-- محتوى العرض --}}
    @if($viewMode === 'cards')
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse($units as $unit)
                <x-rental.unit-card :unit="$unit" />
            @empty
                <x-rental.empty-state title="لا توجد وحدات" message="أضف أول وحدة داخل هذا العقار." />
            @endforelse
        </div>

    @elseif($viewMode === 'table')
        <div class="erp-card overflow-hidden">
            <table class="w-full text-right text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs font-bold text-slate-500">
                    <tr>
                        <th class="px-5 py-3">الوحدة</th>
                        <th class="px-5 py-3">النوع</th>
                        <th class="px-5 py-3">الرقم الداخلي</th>
                        <th class="px-5 py-3">الحالة</th>
                        <th class="px-5 py-3">عداد الكهرباء</th>
                        <th class="px-5 py-3">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($units as $unit)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-bold text-slate-900">{{ $unit->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $unit->type }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $unit->internal_number ?: '—' }}</td>
                            <td class="px-5 py-4"><x-rental.status-badge :status="$unit->status->value ?? $unit->status" /></td>
                            <td class="px-5 py-4 text-slate-600">{{ $unit->electricity_meter ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @can('units.edit')
                                <button wire:click="openEditUnitModal({{ $unit->id }})" class="erp-btn-soft erp-btn-sm">
                                    تعديل
                                </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">لا توجد وحدات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @else
        <livewire:property-map-board :property="$property" :key="'map-'.$property->id" />
    @endif

    {{-- مودال إضافة / تعديل الوحدة --}}
    @if($showCreateUnitModal || $showEditUnitModal)
        <div wire:key="unit-modal-{{ $showCreateUnitModal ? 'create' : 'edit-' . $editingUnitId }}">
        <div class="erp-modal-overlay">
            <div class="erp-modal-box">
                <div class="erp-modal-header">
                    <h2 class="text-xl font-black text-slate-900">
                        {{ $showCreateUnitModal ? 'إضافة وحدة جديدة' : 'تعديل الوحدة' }}
                    </h2>
                    <button wire:click="closeUnitModal"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition text-lg leading-none">
                        ×
                    </button>
                </div>

                <div class="erp-modal-body grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-rental.input label="رقم الوحدة" wire:model="unitForm.code" />
                    <x-rental.input label="اسم الوحدة" wire:model="unitForm.name" />

                    <div>
                        <label class="erp-label">نوع الوحدة</label>
                        <select wire:model="unitForm.type" class="erp-select">
                            <option value="shop">محل</option>
                            <option value="apartment">شقة</option>
                            <option value="villa">فيلا</option>
                            <option value="office">مكتب</option>
                            <option value="warehouse">مستودع</option>
                            <option value="room">غرفة</option>
                            <option value="land">أرض</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>

                    <div>
                        <label class="erp-label">الحالة</label>
                        <select wire:model="unitForm.status" class="erp-select">
                            <option value="vacant">شاغرة</option>
                            <option value="rented">مؤجرة</option>
                            <option value="reserved">محجوزة</option>
                            <option value="maintenance">تحت الصيانة</option>
                            <option value="unavailable">غير متاحة</option>
                        </select>
                    </div>

                    <x-rental.input label="الرقم الداخلي" wire:model="unitForm.internal_number" />
                    <x-rental.input label="المساحة" type="number" step="0.01" wire:model="unitForm.area" />
                    <x-rental.input label="الدور" wire:model="unitForm.floor" />
                    <x-rental.input label="عداد الكهرباء" wire:model="unitForm.electricity_meter" />
                    <x-rental.input label="عداد الماء" wire:model="unitForm.water_meter" />

                    <div class="md:col-span-2">
                        <label class="erp-label">الوصف</label>
                        <textarea wire:model="unitForm.description" rows="3" class="erp-textarea"></textarea>
                    </div>
                </div>

                <div class="erp-modal-footer">
                    <button type="button" wire:click="closeUnitModal" class="erp-btn-soft">إلغاء</button>
                    @if($showCreateUnitModal)
                        <button type="button" wire:click="createUnit" class="erp-btn-primary">حفظ الوحدة</button>
                    @else
                        <button type="button" wire:click="updateUnit" class="erp-btn-primary">حفظ التعديلات</button>
                    @endif
                </div>
            </div>
        </div>
        </div>
    @endif
</div>
