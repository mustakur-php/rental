<div class="min-h-screen bg-slate-50 p-6 lg:p-8">

    {{-- ═══ رأس الصفحة ═══ --}}
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">الشركات</h1>
            <p class="mt-1 text-sm text-slate-500">إدارة الشركات الرئيسية والفرعية وربطها بالعقارات</p>
        </div>
        @can('companies.create')
        <button wire:click="openCreateModal()"
            class="inline-flex items-center gap-2 rounded-2xl bg-rose-700 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-rose-800 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            إضافة شركة رئيسية
        </button>
        @endcan
    </div>

    {{-- ═══ شريط البحث ═══ --}}
    <div class="mb-6 flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-64">
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="بحث بالاسم أو الكود أو السجل التجاري..."
                class="w-full rounded-2xl border border-slate-200 bg-white py-2.5 pr-9 pl-4 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
        </div>
    </div>

    {{-- ═══ قائمة الشركات ═══ --}}
    <div class="space-y-4">
        @forelse($companies as $company)
            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">

                {{-- الشركة الرئيسية --}}
                <div class="flex flex-wrap items-center gap-4 p-5">

                    {{-- أيقونة + اسم --}}
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-slate-800 text-base">{{ $company->name }}</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">{{ $company->code }}</span>
                                <span @class([
                                    'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-700' => $company->status === 'active',
                                    'bg-slate-100 text-slate-500'     => $company->status !== 'active',
                                ])>{{ $company->status === 'active' ? 'نشطة' : 'غير نشطة' }}</span>
                                <span class="rounded-full bg-rose-100 text-rose-700 px-2.5 py-0.5 text-xs font-semibold">رئيسية</span>
                            </div>
                            <div class="mt-1 flex flex-wrap gap-4 text-xs text-slate-500">
                                @if($company->commercial_registration)
                                    <span>س.ت: {{ $company->commercial_registration }}</span>
                                @endif
                                @if($company->phone)
                                    <span>📞 {{ $company->phone }}</span>
                                @endif
                                @if($company->email)
                                    <span>✉ {{ $company->email }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- إحصائيات --}}
                    <div class="flex shrink-0 gap-4 text-center text-xs">
                        <div class="flex flex-col items-center gap-0.5">
                            <span class="text-xl font-extrabold text-rose-700">{{ $company->subsidiaries_count }}</span>
                            <span class="text-slate-500">فرعية</span>
                        </div>
                        <div class="w-px bg-slate-200"></div>
                        <div class="flex flex-col items-center gap-0.5">
                            <span class="text-xl font-extrabold text-slate-700">{{ $company->properties_count }}</span>
                            <span class="text-slate-500">عقار</span>
                        </div>
                    </div>

                    {{-- أزرار الإجراءات --}}
                    <div class="flex shrink-0 items-center gap-2">
                        @can('companies.create')
                        <button wire:click="openCreateModal({{ $company->id }})"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition"
                            title="إضافة شركة فرعية">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            فرعية
                        </button>
                        @endcan
                        @can('companies.edit')
                        <button wire:click="openEditModal({{ $company->id }})"
                            class="rounded-xl border border-slate-200 bg-white p-1.5 text-slate-500 hover:border-slate-300 hover:text-slate-700 transition"
                            title="تعديل">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        @endcan
                        @can('companies.archive')
                        <button wire:click="openArchiveModal({{ $company->id }})"
                            class="rounded-xl border border-slate-200 bg-white p-1.5 text-slate-400 hover:border-rose-200 hover:text-rose-600 transition"
                            title="أرشفة">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </button>
                        @endcan
                    </div>
                </div>

                {{-- الشركات الفرعية --}}
                @if($company->subsidiaries->isNotEmpty())
                    <div class="border-t border-slate-100 bg-slate-50/60 divide-y divide-slate-100">
                        @foreach($company->subsidiaries as $sub)
                            <div class="flex flex-wrap items-center gap-4 px-5 py-3.5">
                                {{-- خط هرمي --}}
                                <div class="flex items-center gap-3 flex-1 min-w-0 mr-6">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-200 text-slate-500">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold text-slate-700 text-sm">{{ $sub->name }}</span>
                                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-500">{{ $sub->code }}</span>
                                            <span @class([
                                                'rounded-full px-2 py-0.5 text-xs font-semibold',
                                                'bg-emerald-100 text-emerald-700' => $sub->status === 'active',
                                                'bg-slate-100 text-slate-500'     => $sub->status !== 'active',
                                            ])>{{ $sub->status === 'active' ? 'نشطة' : 'غير نشطة' }}</span>
                                            <span class="rounded-full bg-slate-100 text-slate-500 px-2 py-0.5 text-xs font-semibold">فرعية</span>
                                        </div>
                                        <div class="mt-0.5 flex flex-wrap gap-3 text-xs text-slate-400">
                                            @if($sub->commercial_registration)
                                                <span>س.ت: {{ $sub->commercial_registration }}</span>
                                            @endif
                                            @if($sub->phone)
                                                <span>📞 {{ $sub->phone }}</span>
                                            @endif
                                            <span>{{ $sub->properties_count ?? 0 }} عقار</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    @can('companies.edit')
                                    <button wire:click="openEditModal({{ $sub->id }})"
                                        class="rounded-xl border border-slate-200 bg-white p-1.5 text-slate-500 hover:border-slate-300 hover:text-slate-700 transition"
                                        title="تعديل">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @endcan
                                    @can('companies.archive')
                                    <button wire:click="openArchiveModal({{ $sub->id }})"
                                        class="rounded-xl border border-slate-200 bg-white p-1.5 text-slate-400 hover:border-rose-200 hover:text-rose-600 transition"
                                        title="أرشفة">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                        </svg>
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white py-20 text-center">
                <svg class="mx-auto mb-4 h-12 w-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-slate-500 font-semibold">لا توجد شركات</p>
                <p class="mt-1 text-sm text-slate-400">ابدأ بإضافة شركة رئيسية</p>
            </div>
        @endforelse
    </div>

    {{-- ترقيم الصفحات --}}
    @if($companies->hasPages())
        <div class="mt-6">{{ $companies->links() }}</div>
    @endif


    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- مودال الإنشاء / التعديل                                --}}
    {{-- ═══════════════════════════════════════════════════════ --}}

    @if($showCreateModal || $showEditModal)
        @php $isEdit = $showEditModal; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl"
                 x-data x-trap.noscroll="true">

                {{-- رأس المودال --}}
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-extrabold text-slate-800">
                        @if($isEdit)
                            تعديل بيانات الشركة
                        @elseif($form['parent_id'])
                            إضافة شركة فرعية
                        @else
                            إضافة شركة رئيسية
                        @endif
                    </h2>
                    <button wire:click="{{ $isEdit ? '$set(\'showEditModal\', false)' : '$set(\'showCreateModal\', false)' }}"
                        class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="{{ $isEdit ? 'updateCompany' : 'createCompany' }}"
                      class="divide-y divide-slate-100 overflow-y-auto max-h-[80vh]">

                    {{-- نوع الشركة والشركة الأم --}}
                    <div class="px-6 py-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">نوع الشركة</label>
                                <select wire:model.live="form.type"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                    <option value="main">رئيسية</option>
                                    <option value="subsidiary">فرعية</option>
                                </select>
                                @error('form.type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            @if($form['type'] === 'subsidiary')
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">الشركة الأم <span class="text-rose-600">*</span></label>
                                <select wire:model="form.parent_id"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                    <option value="">اختر الشركة الأم</option>
                                    @foreach($mainCompanies as $mc)
                                        @if(!$isEdit || $mc->id !== $editingCompanyId)
                                            <option value="{{ $mc->id }}">{{ $mc->name }} ({{ $mc->code }})</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('form.parent_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- البيانات الأساسية --}}
                    <div class="px-6 py-5">
                        <p class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-400">البيانات الأساسية</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">الكود <span class="text-rose-600">*</span></label>
                                <input wire:model="form.code" type="text"
                                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                    placeholder="COMP-001">
                                @error('form.code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">اسم الشركة <span class="text-rose-600">*</span></label>
                                <input wire:model="form.name" type="text"
                                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                    placeholder="شركة النور العقارية">
                                @error('form.name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">السجل التجاري</label>
                                <input wire:model="form.commercial_registration" type="text"
                                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                    placeholder="1010XXXXXX">
                                @error('form.commercial_registration') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">الحالة</label>
                                <select wire:model="form.status"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                    <option value="active">نشطة</option>
                                    <option value="inactive">غير نشطة</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- بيانات التواصل --}}
                    <div class="px-6 py-5">
                        <p class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-400">بيانات التواصل</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">رقم الهاتف</label>
                                <input wire:model="form.phone" type="text"
                                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                    placeholder="05XXXXXXXX">
                                @error('form.phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">البريد الإلكتروني</label>
                                <input wire:model="form.email" type="email"
                                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                    placeholder="info@company.com">
                                @error('form.email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">العنوان</label>
                                <input wire:model="form.address" type="text"
                                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                    placeholder="الرياض، حي العليا">
                                @error('form.address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- الحساب البنكي --}}
                    <div class="px-6 py-5">
                        <p class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-400">الحساب البنكي</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">اسم البنك</label>
                                <input wire:model="form.bank_name" type="text"
                                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                    placeholder="البنك الأهلي السعودي">
                                @error('form.bank_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">رقم الآيبان (IBAN)</label>
                                <input wire:model="form.iban" type="text"
                                    class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm font-mono text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                    placeholder="SA00 0000 0000 0000 0000 0000">
                                @error('form.iban') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ملاحظات --}}
                    <div class="px-6 py-5">
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">ملاحظات</label>
                        <textarea wire:model="form.notes" rows="3"
                            class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                            placeholder="أي ملاحظات إضافية..."></textarea>
                    </div>

                    {{-- أزرار --}}
                    <div class="flex justify-end gap-3 px-6 py-4">
                        <button type="button"
                            wire:click="{{ $isEdit ? '$set(\'showEditModal\', false)' : '$set(\'showCreateModal\', false)' }}"
                            class="rounded-2xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                            إلغاء
                        </button>
                        <button type="submit"
                            class="rounded-2xl bg-rose-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-rose-800 transition">
                            <span wire:loading.remove wire:target="{{ $isEdit ? 'updateCompany' : 'createCompany' }}">
                                {{ $isEdit ? 'حفظ التغييرات' : 'إنشاء الشركة' }}
                            </span>
                            <span wire:loading wire:target="{{ $isEdit ? 'updateCompany' : 'createCompany' }}">جاري الحفظ...</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- مودال الأرشفة                                          --}}
    {{-- ═══════════════════════════════════════════════════════ --}}

    @if($showArchiveModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl" x-data x-trap.noscroll="true">
                <div class="px-6 py-5 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100">
                        <svg class="h-7 w-7 text-rose-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-extrabold text-slate-800">أرشفة الشركة</h3>
                    <p class="mt-2 text-sm text-slate-500">سيتم أرشفة الشركة وجميع الشركات الفرعية والعقارات التابعة لها.</p>
                </div>
                <div class="px-6 pb-5">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">سبب الأرشفة <span class="text-rose-600">*</span></label>
                    <input wire:model="archiveReason" type="text"
                        class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                        placeholder="مثال: إغلاق الشركة">
                    @error('archiveReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="$set('showArchiveModal', false)"
                        class="flex-1 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                        إلغاء
                    </button>
                    <button wire:click="archiveCompany"
                        class="flex-1 rounded-2xl bg-rose-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-rose-800 transition">
                        <span wire:loading.remove wire:target="archiveCompany">نعم، أرشفة</span>
                        <span wire:loading wire:target="archiveCompany">جاري...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
