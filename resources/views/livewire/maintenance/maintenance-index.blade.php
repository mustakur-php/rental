<div class="erp-container">
    <x-page-header title="طلبات الصيانة" subtitle="إدارة ومتابعة طلبات الصيانة للعقارات والوحدات">
        <x-slot:actions>
            @can('maintenance.create')
            <button wire:click="openCreateModal" class="erp-btn-primary">+ طلب صيانة</button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- فلاتر --}}
    <div class="erp-card mb-6 p-4">
        <div class="grid gap-3 md:grid-cols-3">
            <input wire:model.live.debounce.300ms="search"
                class="erp-input"
                placeholder="بحث بالعنوان أو الكود...">
            <select wire:model.live="status" class="erp-select">
                <option value="">كل الحالات</option>
                <option value="new">جديد</option>
                <option value="in_progress">قيد التنفيذ</option>
                <option value="completed">مكتمل</option>
                <option value="cancelled">ملغي</option>
            </select>
            <select wire:model.live="priority" class="erp-select">
                <option value="">كل الأولويات</option>
                <option value="urgent">عاجل</option>
                <option value="high">عالي</option>
                <option value="medium">متوسط</option>
                <option value="low">منخفض</option>
            </select>
        </div>
    </div>

    @if($requests->isEmpty())
        <div class="erp-card p-16 text-center">
            <div class="mb-4 text-4xl">🔧</div>
            <div class="text-lg font-bold text-slate-700">لا توجد طلبات صيانة</div>
            <p class="mt-2 text-sm text-slate-400">ابدأ بإضافة أول طلب صيانة.</p>
            <button wire:click="openCreateModal" class="erp-btn-primary mt-6">+ طلب صيانة</button>
        </div>
    @else
        <div class="erp-card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-right text-xs font-bold text-slate-500">
                    <tr>
                        <th class="px-5 py-3">الكود</th>
                        <th class="px-5 py-3">العنوان</th>
                        <th class="px-5 py-3">العقار / الوحدة</th>
                        <th class="px-5 py-3">الأولوية</th>
                        <th class="px-5 py-3">تاريخ الطلب</th>
                        <th class="px-5 py-3">التكلفة</th>
                        <th class="px-5 py-3">الحالة</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($requests as $req)
                    @php
                        $statusVal = is_object($req->status) ? $req->status->value : $req->status;
                        $statusLabels  = ['new' => 'جديد', 'in_progress' => 'قيد التنفيذ', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
                        $statusClasses = ['new' => 'bg-sky-50 text-sky-700', 'in_progress' => 'bg-amber-50 text-amber-700', 'completed' => 'bg-emerald-50 text-emerald-700', 'cancelled' => 'bg-slate-100 text-slate-500'];
                        $priorityLabels  = ['urgent' => 'عاجل', 'high' => 'عالي', 'medium' => 'متوسط', 'low' => 'منخفض'];
                        $priorityClasses = ['urgent' => 'bg-rose-50 text-rose-700', 'high' => 'bg-orange-50 text-orange-700', 'medium' => 'bg-amber-50 text-amber-700', 'low' => 'bg-slate-100 text-slate-600'];
                    @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-mono text-xs text-slate-400">{{ $req->code }}</td>
                            <td class="px-5 py-4 font-bold text-slate-900">{{ $req->title }}</td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-700">{{ $req->property?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ $req->unit?->name ?? 'عام' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $priorityClasses[$req->priority] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $priorityLabels[$req->priority] ?? $req->priority }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $req->request_date?->format('Y/m/d') }}</td>
                            <td class="px-5 py-4 text-slate-700">
                                {{ $req->cost ? number_format($req->cost, 0) . ' ر.س' : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$statusVal] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabels[$statusVal] ?? $statusVal }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-left">
                                @can('maintenance.edit')
                                <button wire:click="openEditModal({{ $req->id }})" class="erp-btn-soft text-xs">تعديل</button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $requests->links() }}</div>
    @endif

    {{-- مودال الإضافة --}}
    @if($showCreateModal)
        <x-rental.modal>
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-lg font-bold">طلب صيانة جديد</h2>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-6">
                @include('livewire.maintenance.partials.maintenance-form')
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button wire:click="$set('showCreateModal', false)" class="erp-btn-soft">إلغاء</button>
                <button wire:click="createRequest" class="erp-btn-primary">حفظ الطلب</button>
            </div>
        </x-rental.modal>
    @endif

    {{-- مودال التعديل --}}
    @if($showEditModal)
        <x-rental.modal>
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-lg font-bold">تعديل طلب الصيانة</h2>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-6">
                @include('livewire.maintenance.partials.maintenance-form')
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button wire:click="$set('showEditModal', false)" class="erp-btn-soft">إلغاء</button>
                <button wire:click="updateRequest" class="erp-btn-primary">حفظ التعديلات</button>
            </div>
        </x-rental.modal>
    @endif
</div>
