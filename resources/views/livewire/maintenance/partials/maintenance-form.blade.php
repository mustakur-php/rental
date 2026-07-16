<div class="grid gap-4 md:grid-cols-2">
    {{-- العقار --}}
    <div>
        <label class="text-sm font-bold text-slate-700">العقار *</label>
        <select wire:model.live="form.property_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <option value="">اختر العقار</option>
            @foreach($this->properties as $property)
                <option value="{{ $property->id }}">{{ $property->name }}</option>
            @endforeach
        </select>
        @error('form.property_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    {{-- الوحدة --}}
    <div>
        <label class="text-sm font-bold text-slate-700">الوحدة (اختياري)</label>
        <select wire:model="form.unit_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <option value="">عام (بدون وحدة محددة)</option>
            @foreach($this->units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- العنوان --}}
    <div class="md:col-span-2">
        <label class="text-sm font-bold text-slate-700">عنوان الطلب *</label>
        <input wire:model="form.title" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm" placeholder="وصف موجز للمشكلة">
        @error('form.title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    {{-- النوع --}}
    <div>
        <label class="text-sm font-bold text-slate-700">نوع الصيانة</label>
        <select wire:model="form.type" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <option value="corrective">تصحيحية</option>
            <option value="preventive">وقائية</option>
            <option value="emergency">طارئة</option>
        </select>
    </div>

    {{-- الأولوية --}}
    <div>
        <label class="text-sm font-bold text-slate-700">الأولوية</label>
        <select wire:model="form.priority" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <option value="low">منخفضة</option>
            <option value="medium">متوسطة</option>
            <option value="high">عالية</option>
            <option value="urgent">عاجلة</option>
        </select>
    </div>

    {{-- الحالة --}}
    <div>
        <label class="text-sm font-bold text-slate-700">الحالة</label>
        <select wire:model="form.status" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <option value="new">جديد</option>
            <option value="in_progress">قيد التنفيذ</option>
            <option value="completed">مكتمل</option>
            <option value="cancelled">ملغي</option>
        </select>
    </div>

    {{-- تأثير الوحدة --}}
    <div>
        <label class="text-sm font-bold text-slate-700">تأثير على الوحدة</label>
        <select wire:model="form.unit_impact" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <option value="none">بدون تأثير</option>
            <option value="maintenance">تحت الصيانة</option>
            <option value="unavailable">غير متاحة</option>
        </select>
    </div>

    {{-- تاريخ الطلب --}}
    <div>
        <label class="text-sm font-bold text-slate-700">تاريخ الطلب *</label>
        <input type="date" wire:model="form.request_date" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
    </div>

    {{-- تاريخ الإتمام --}}
    <div>
        <label class="text-sm font-bold text-slate-700">تاريخ الإتمام</label>
        <input type="date" wire:model="form.completed_date" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
    </div>

    {{-- التكلفة --}}
    <div>
        <label class="text-sm font-bold text-slate-700">التكلفة (ر.س)</label>
        <input type="number" step="0.01" wire:model="form.cost" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm" placeholder="0.00">
    </div>

    {{-- الوصف --}}
    <div class="md:col-span-2">
        <label class="text-sm font-bold text-slate-700">الوصف التفصيلي</label>
        <textarea wire:model="form.description" rows="3" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm" placeholder="تفاصيل إضافية..."></textarea>
    </div>
</div>
