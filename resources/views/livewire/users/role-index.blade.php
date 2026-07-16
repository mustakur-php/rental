<div class="erp-container space-y-6">

    {{-- رأس الصفحة --}}
    <div class="erp-card p-6">
        <h1 class="text-3xl font-black text-slate-900">الأدوار والصلاحيات</h1>
        <p class="mt-1 text-sm text-slate-500">تحديد ما يستطيع كل دور رؤيته وتعديله</p>
    </div>

    {{-- بطاقات الأدوار --}}
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach($roles as $role)
            @php
                $icon = match($role->name) {
                    'super_admin'            => ['🔑', 'bg-rose-100',   'text-rose-700'],
                    'accountant'             => ['💰', 'bg-emerald-100','text-emerald-700'],
                    'property_manager'       => ['🏢', 'bg-blue-100',   'text-blue-700'],
                    'maintenance_supervisor' => ['🔧', 'bg-amber-100',  'text-amber-700'],
                    'viewer'                 => ['👁️',  'bg-slate-100',  'text-slate-600'],
                    default                  => ['👤', 'bg-slate-100',  'text-slate-600'],
                };
            @endphp
            <div class="erp-card p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-xl {{ $icon[1] }}">
                            {{ $icon[0] }}
                        </div>
                        <div>
                            <div class="font-black text-slate-900">{{ $role->description ?? $role->name }}</div>
                            <div class="mt-0.5 text-xs text-slate-400 font-mono">{{ $role->name }}</div>
                        </div>
                    </div>
                    @can('roles.edit')
                    <button wire:click="openEditModal({{ $role->id }})"
                        class="erp-btn-soft erp-btn-sm">
                        {{ $role->name === 'super_admin' ? 'عرض' : 'تعديل' }}
                    </button>
                    @endcan
                </div>

                <div class="mt-4 flex items-center gap-4 border-t border-slate-100 pt-4">
                    <div class="text-center">
                        <div class="text-2xl font-black text-slate-800">{{ $role->permissions_count }}</div>
                        <div class="text-[10px] font-semibold text-slate-400">صلاحية</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-black text-slate-800">{{ $role->users_count }}</div>
                        <div class="text-[10px] font-semibold text-slate-400">مستخدم</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>


    {{-- ══════════════════════════════ مودال الصلاحيات ══════════════════════════════ --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div class="flex max-h-[90vh] w-full max-w-2xl flex-col rounded-3xl bg-white shadow-2xl">

                {{-- رأس المودال --}}
                @php $editingRole = \Spatie\Permission\Models\Role::find($editingRoleId); @endphp
                <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-800">
                            صلاحيات: {{ $editingRole?->description ?? $editingRoleName }}
                        </h2>
                        @if($editingRoleName === 'super_admin')
                            <p class="mt-0.5 text-xs text-rose-600">مدير النظام لديه جميع الصلاحيات دائماً</p>
                        @endif
                    </div>
                    <button wire:click="closeModal"
                        class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- محتوى الصلاحيات --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-5">
                    @foreach($groupedPerms as $domain => $group)
                        @php
                            $domainPerms    = collect($group['permissions'])->pluck('name')->toArray();
                            $allChecked     = count(array_intersect($domainPerms, $selectedPermissions)) === count($domainPerms);
                            $partialChecked = !$allChecked && count(array_intersect($domainPerms, $selectedPermissions)) > 0;
                        @endphp
                        <div class="rounded-2xl border border-slate-200 overflow-hidden">
                            {{-- رأس المجموعة --}}
                            <div class="flex items-center justify-between bg-slate-50 px-4 py-2.5">
                                <span class="font-bold text-slate-700">{{ $group['label'] }}</span>
                                @if($editingRoleName !== 'super_admin')
                                    <button wire:click="toggleAll('{{ $domain }}')"
                                        class="text-xs font-semibold {{ $allChecked ? 'text-rose-600' : 'text-slate-500' }} hover:text-rose-700 transition">
                                        {{ $allChecked ? 'إلغاء الكل' : 'تحديد الكل' }}
                                    </button>
                                @endif
                            </div>
                            {{-- الصلاحيات --}}
                            <div class="flex flex-wrap gap-2 p-4">
                                @foreach($group['permissions'] as $perm)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm transition
                                        {{ in_array($perm['name'], $selectedPermissions) ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                                        <input type="checkbox"
                                            wire:model.live="selectedPermissions"
                                            value="{{ $perm['name'] }}"
                                            {{ $editingRoleName === 'super_admin' ? 'disabled' : '' }}
                                            class="accent-rose-700">
                                        <span class="font-semibold">{{ $perm['action'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- أزرار --}}
                @if($editingRoleName !== 'super_admin')
                    <div class="shrink-0 flex gap-3 border-t border-slate-100 px-6 py-4">
                        <button type="button" wire:click="closeModal"
                            class="flex-1 rounded-2xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                            إلغاء
                        </button>
                        <button type="button" wire:click="saveRole"
                            class="flex-1 rounded-2xl bg-rose-700 py-2.5 text-sm font-bold text-white hover:bg-rose-800 transition">
                            حفظ الصلاحيات
                        </button>
                    </div>
                @else
                    <div class="shrink-0 border-t border-slate-100 px-6 py-4">
                        <button type="button" wire:click="closeModal"
                            class="w-full rounded-2xl bg-slate-800 py-2.5 text-sm font-bold text-white hover:bg-slate-900 transition">
                            إغلاق
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
