<div class="erp-container">
    <x-page-header title="كل الوحدات" subtitle="صفحة عامة للبحث والمتابعة على مستوى النظام بالكامل">
        <x-slot:actions>
            <x-view-toggle :view-mode="$viewMode" />
        </x-slot:actions>
    </x-page-header>

    <div class="erp-card mb-6 p-4">
        <div class="grid gap-3 md:grid-cols-3">
            <input
                wire:model.live.debounce.300ms="search"
                class="erp-input"
                placeholder="بحث باسم الوحدة أو الكود..."
            >
            <select wire:model.live="status" class="erp-select">
                <option value="">كل الحالات</option>
                <option value="rented">مؤجرة</option>
                <option value="vacant">شاغرة</option>
                <option value="maintenance">تحت الصيانة</option>
                <option value="reserved">محجوزة</option>
            </select>
            <select wire:model.live="propertyId" class="erp-select">
                <option value="">كل العقارات</option>
                @foreach($properties as $property)
                    <option value="{{ $property->id }}">{{ $property->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($viewMode === 'table')
        @include('livewire.units.partials.units-table', ['units' => $units])
    @elseif($viewMode === 'map')
        @include('livewire.units.partials.units-map', ['units' => $units])
    @else
        @if($units->isEmpty())
            <div class="erp-card p-12 text-center">
                <div class="text-slate-400 text-sm">لا توجد وحدات مطابقة للبحث.</div>
            </div>
        @else
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($units as $unit)
                    @include('livewire.units.partials.unit-card', ['unit' => $unit])
                @endforeach
            </div>
        @endif
    @endif

    <div class="mt-6">
        {{ method_exists($units, 'links') ? $units->links() : '' }}
    </div>
</div>
