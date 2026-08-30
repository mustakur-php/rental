<div class="space-y-3">
    {{-- حقل الرفع --}}
    <div>
        <label class="text-sm font-bold text-slate-700">نسخة عقد المالك</label>
        <input type="file" wire:model="file"
            class="mt-2 w-full rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm
                   {{ $errors->has('file') ? 'border-rose-300 bg-rose-50' : '' }}">
        <div wire:loading wire:target="file" class="mt-1 text-xs text-slate-400">
            جارٍ تحميل الملف...
        </div>
        @error('file')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- زر الرفع (يظهر فقط إذا اختار ملفاً في وضع التعديل) --}}
    @if($leaseId && $file)
    <button wire:click="save"
            wire:loading.attr="disabled"
            class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
        <span wire:loading.remove wire:target="save">رفع الملف</span>
        <span wire:loading wire:target="save">جارٍ الرفع...</span>
    </button>
    @endif

    {{-- الملف الحالي --}}
    @if($currentPath)
    <div class="flex items-center gap-3 rounded-2xl bg-indigo-50 px-4 py-3">
        <a href="{{ Storage::url($currentPath) }}" target="_blank"
           class="flex-1 text-sm font-semibold text-indigo-700 hover:underline truncate">
            📄 {{ basename($currentPath) }}
        </a>
        <button wire:click="deleteFile" wire:confirm="حذف الملف الحالي؟"
                class="text-xs font-semibold text-rose-500 hover:text-rose-700 shrink-0">
            حذف
        </button>
    </div>
    @endif

    {{-- رسالة وضع الإنشاء (ملف مُختار لكن لا يوجد lease بعد) --}}
    @if(!$leaseId && $file)
    <p class="text-xs text-slate-400">
        ⏳ الملف جاهز — سيُرفع تلقائياً بعد الحفظ
    </p>
    @endif
</div>
