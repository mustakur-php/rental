<div class="erp-container">
    <x-page-header title="المستأجرون" subtitle="إدارة بيانات المستأجرين الأفراد والشركات">
        <x-slot:actions>
            @can('tenants.create')
            <button wire:click="openCreateModal" class="erp-btn-primary">
                + إضافة مستأجر
            </button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- فلاتر البحث --}}
    <div class="erp-card mb-6 p-4">
        <div class="grid gap-3 md:grid-cols-3">
            <input
                wire:model.live.debounce.300ms="search"
                class="erp-input"
                placeholder="بحث بالاسم، الجوال، الهوية، الكود..."
            >
            <select wire:model.live="type" class="erp-select">
                <option value="">كل الأنواع</option>
                <option value="individual">فرد</option>
                <option value="company">شركة</option>
            </select>
            <select wire:model.live="status" class="erp-select">
                <option value="">كل الحالات</option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
                <option value="suspended">موقوف</option>
            </select>
        </div>
    </div>

    {{-- جدول المستأجرين --}}
    @if($tenants->isEmpty())
        <div class="erp-card p-16 text-center">
            <div class="mb-4 text-4xl">👤</div>
            <div class="text-lg font-bold text-slate-700">لا يوجد مستأجرون</div>
            <p class="mt-2 text-sm text-slate-400">ابدأ بإضافة أول مستأجر في النظام.</p>
            <button wire:click="openCreateModal" class="erp-btn-primary mt-6">+ إضافة مستأجر</button>
        </div>
    @else
        <div class="erp-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-right text-xs font-bold text-slate-500">
                    <tr>
                        <th class="px-5 py-3">الكود</th>
                        <th class="px-5 py-3">الاسم</th>
                        <th class="px-5 py-3">النوع</th>
                        <th class="px-5 py-3">الجوال</th>
                        <th class="px-5 py-3">العقود</th>
                        <th class="px-5 py-3">الحالة</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($tenants as $tenant)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-mono text-xs text-slate-400">{{ $tenant->code }}</td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900">{{ $tenant->name }}</div>
                                @if($tenant->email)
                                    <div class="text-xs text-slate-400">{{ $tenant->email }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $tenant->type === 'company' ? 'bg-purple-50 text-purple-700' : 'bg-sky-50 text-sky-700' }}">
                                    {{ $tenant->type === 'company' ? 'شركة' : 'فرد' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $tenant->mobile ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="font-bold {{ $tenant->active_contracts_count > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                                    {{ $tenant->active_contracts_count }} نشط
                                </span>
                                <span class="text-slate-300 mx-1">/</span>
                                <span class="text-slate-400">{{ $tenant->contracts_count }} إجمالي</span>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $statusLabels  = ['active' => 'نشط', 'inactive' => 'غير نشط', 'suspended' => 'موقوف'];
                                    $statusClasses = ['active' => 'bg-emerald-50 text-emerald-700', 'inactive' => 'bg-slate-100 text-slate-600', 'suspended' => 'bg-rose-50 text-rose-700'];
                                @endphp
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$tenant->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabels[$tenant->status] ?? $tenant->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-left">
                                @can('tenants.edit')
                                <button wire:click="openEditModal({{ $tenant->id }})" class="erp-btn-soft text-xs">
                                    تعديل
                                </button>
                                @endcan
                                @can('tenants.archive')
                                <button wire:click="archiveTenant({{ $tenant->id }})" wire:confirm="نقل المستأجر إلى الأرشيف؟" class="erp-btn-soft text-xs text-amber-700">
                                    أرشفة
                                </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tenants->links() }}</div>
    @endif

    {{-- مودال الإضافة --}}
    @if($showCreateModal)
        <x-rental.modal>
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-lg font-bold">إضافة مستأجر جديد</h2>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-6">
                @include('livewire.tenants.partials.tenant-form')
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button wire:click="$set('showCreateModal', false)" class="erp-btn-soft">إلغاء</button>
                <button wire:click="createTenant" class="erp-btn-primary">حفظ المستأجر</button>
            </div>
        </x-rental.modal>
    @endif

    {{-- مودال التعديل --}}
    @if($showEditModal)
        <x-rental.modal>
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-lg font-bold">تعديل بيانات المستأجر</h2>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-6">
                @include('livewire.tenants.partials.tenant-form')
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button wire:click="$set('showEditModal', false)" class="erp-btn-soft">إلغاء</button>
                <button wire:click="updateTenant" class="erp-btn-primary">حفظ التعديلات</button>
            </div>
        </x-rental.modal>
    @endif
</div>
