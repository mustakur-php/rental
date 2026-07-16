<div class="space-y-6">
    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <h3 class="text-lg font-bold text-slate-900">المرفقات</h3>
        <p class="mt-1 text-sm text-slate-500">رفع ومعاينة الملفات المرتبطة بهذا السجل.</p>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <select wire:model="category" class="rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <option value="other">أخرى</option>
                <option value="property_image">صورة عقار</option>
                <option value="map_image">خريطة / مخطط</option>
                <option value="tenant_identity">هوية مستأجر</option>
                <option value="commercial_registration">سجل تجاري</option>
                <option value="contract_pdf">عقد PDF</option>
                <option value="payment_receipt">إيصال / حوالة</option>
                <option value="maintenance_before">صيانة قبل</option>
                <option value="maintenance_after">صيانة بعد</option>
                <option value="invoice">فاتورة</option>
            </select>

            <input type="file" wire:model="file" class="md:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm">
        </div>

        <button wire:click="upload" class="mt-4 rounded-2xl bg-rose-700 px-5 py-3 text-sm font-semibold text-white shadow-sm">
            رفع المرفق
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($attachments as $attachment)
            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $attachment->original_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $attachment->category }} · {{ $attachment->mime_type }}</p>
                    </div>
                    <button wire:click="delete({{ $attachment->id }})" class="text-sm text-rose-500">حذف</button>
                </div>

                <div class="mt-5 flex gap-2">
                    <button wire:click="preview({{ $attachment->id }})" class="flex-1 rounded-2xl bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700">
                        معاينة
                    </button>
                    <a href="{{ Storage::url($attachment->path) }}" download class="flex-1 rounded-2xl bg-slate-100 px-4 py-2 text-center text-sm font-semibold text-slate-700">
                        تحميل
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-white p-8 text-center text-slate-500 shadow-sm ring-1 ring-slate-100 md:col-span-2 xl:col-span-3">
                لا توجد مرفقات بعد.
            </div>
        @endforelse
    </div>

    @if($previewAttachment)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" wire:click.self="closePreview">
            <div class="max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="font-bold text-slate-900">{{ $previewAttachment->original_name }}</h3>
                    <button wire:click="closePreview" class="text-slate-400">✕</button>
                </div>

                <div class="max-h-[78vh] overflow-auto bg-slate-50 p-4">
                    @if(str_starts_with($previewAttachment->mime_type, 'image/'))
                        <img src="{{ Storage::url($previewAttachment->path) }}" class="mx-auto max-h-[72vh] rounded-2xl" alt="Attachment preview">
                    @elseif($previewAttachment->mime_type === 'application/pdf')
                        <iframe src="{{ Storage::url($previewAttachment->path) }}" class="h-[72vh] w-full rounded-2xl bg-white"></iframe>
                    @else
                        <div class="rounded-2xl bg-white p-8 text-center text-slate-500">
                            المعاينة غير متاحة لهذا النوع. يمكنك تحميل الملف.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

