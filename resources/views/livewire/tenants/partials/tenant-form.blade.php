{{-- النوع --}}
<div class="mb-5">
    <label class="text-sm font-bold text-slate-700">نوع المستأجر</label>
    <div class="mt-2 flex gap-3">
        <label class="flex flex-1 cursor-pointer items-center gap-3 rounded-2xl border-2 p-4 transition {{ $form['type'] === 'individual' ? 'border-slate-900 bg-slate-50' : 'border-slate-200' }}">
            <input type="radio" wire:model.live="form.type" value="individual" class="accent-slate-900">
            <div>
                <div class="font-bold text-sm">فرد</div>
                <div class="text-xs text-slate-400">مستأجر شخصي</div>
            </div>
        </label>
        <label class="flex flex-1 cursor-pointer items-center gap-3 rounded-2xl border-2 p-4 transition {{ $form['type'] === 'company' ? 'border-slate-900 bg-slate-50' : 'border-slate-200' }}">
            <input type="radio" wire:model.live="form.type" value="company" class="accent-slate-900">
            <div>
                <div class="font-bold text-sm">شركة</div>
                <div class="text-xs text-slate-400">مستأجر تجاري</div>
            </div>
        </label>
    </div>
</div>

{{-- بيانات مشتركة --}}
<div class="grid gap-4 md:grid-cols-2">
    <x-rental.input label="الاسم الكامل *" wire:model="form.name" placeholder="اسم المستأجر" />
    <x-rental.input label="رقم الجوال" wire:model="form.mobile" placeholder="05xxxxxxxx" />
    <x-rental.input label="البريد الإلكتروني" type="email" wire:model="form.email" placeholder="example@email.com" />
    <x-rental.input label="العنوان" wire:model="form.address" placeholder="المدينة، الحي..." />
</div>

{{-- بيانات الفرد --}}
@if($form['type'] === 'individual')
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <x-rental.input label="رقم الهوية" wire:model="form.national_id" placeholder="1xxxxxxxxx" />
        <x-rental.input label="الجنسية" wire:model="form.nationality" placeholder="سعودي" />
        <x-rental.input label="تاريخ الميلاد" type="date" wire:model="form.birth_date" />
    </div>
@endif

{{-- بيانات الشركة --}}
@if($form['type'] === 'company')
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <x-rental.input label="اسم الشركة" wire:model="form.company_name" placeholder="شركة ..." />
        <x-rental.input label="السجل التجاري" wire:model="form.commercial_registration" placeholder="رقم السجل التجاري" />
        <x-rental.input label="اسم المفوض" wire:model="form.contact_person_name" placeholder="اسم الشخص المفوض" />
        <x-rental.input label="جوال المفوض" wire:model="form.contact_person_mobile" placeholder="05xxxxxxxx" />
    </div>
@endif

{{-- الحالة والملاحظات --}}
<div class="mt-4 grid gap-4 md:grid-cols-2">
    <div>
        <label class="text-sm font-bold text-slate-700">الحالة</label>
        <select wire:model="form.status" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <option value="active">نشط</option>
            <option value="inactive">غير نشط</option>
            <option value="suspended">موقوف</option>
        </select>
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700">ملاحظات</label>
        <textarea wire:model="form.notes" rows="3"
            class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-slate-400 focus:ring-slate-400"
            placeholder="ملاحظات إضافية..."></textarea>
    </div>
</div>
