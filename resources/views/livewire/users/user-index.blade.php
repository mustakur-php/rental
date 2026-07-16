<div class="erp-container space-y-6">

    {{-- رأس الصفحة --}}
    <div class="erp-card p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-black text-slate-900">المستخدمون</h1>
                <p class="mt-1 text-sm text-slate-500">إدارة حسابات الدخول والأدوار</p>
            </div>
            @can('users.create')
            <button wire:click="openCreateModal" class="erp-btn-primary">
                + مستخدم جديد
            </button>
            @endcan
        </div>
    </div>

    {{-- فلاتر البحث --}}
    <div class="erp-card p-5">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <input wire:model.live.debounce.300ms="search"
                type="text" placeholder="بحث بالاسم أو البريد..."
                class="erp-input">

            <select wire:model.live="roleFilter" class="erp-select">
                <option value="">كل الأدوار</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->description ?? $role->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="erp-select">
                <option value="">كل الحالات</option>
                <option value="active">نشط</option>
                <option value="inactive">معطّل</option>
            </select>
        </div>
    </div>

    {{-- جدول المستخدمين --}}
    <div class="erp-card overflow-hidden">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-bold text-slate-500">
                <tr>
                    <th class="px-5 py-3">المستخدم</th>
                    <th class="px-5 py-3">الدور</th>
                    <th class="hidden px-5 py-3 md:table-cell">آخر دخول</th>
                    <th class="px-5 py-3">الحالة</th>
                    <th class="px-5 py-3">إجراء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr class="transition hover:bg-slate-50">
                        {{-- المستخدم --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-sm font-black text-rose-700">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- الدور --}}
                        <td class="px-5 py-4">
                            @php $roleName = $user->roles->first()?->name; @endphp
                            @if($roleName)
                                @php
                                    $roleColor = match($roleName) {
                                        'super_admin'            => 'bg-rose-100 text-rose-700',
                                        'accountant'             => 'bg-emerald-100 text-emerald-700',
                                        'property_manager'       => 'bg-blue-100 text-blue-700',
                                        'maintenance_supervisor' => 'bg-amber-100 text-amber-700',
                                        default                  => 'bg-slate-100 text-slate-600',
                                    };
                                @endphp
                                <span class="inline-flex rounded-xl px-2.5 py-1 text-xs font-bold {{ $roleColor }}">
                                    {{ $user->roles->first()?->description ?? $roleName }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>

                        {{-- آخر دخول --}}
                        <td class="hidden px-5 py-4 text-xs text-slate-500 md:table-cell">
                            {{ $user->last_login_at?->diffForHumans() ?? 'لم يدخل بعد' }}
                        </td>

                        {{-- الحالة --}}
                        <td class="px-5 py-4">
                            <span @class([
                                'inline-flex items-center gap-1.5 rounded-xl px-2.5 py-1 text-xs font-bold',
                                'bg-emerald-100 text-emerald-700' => $user->status === 'active',
                                'bg-slate-100 text-slate-500'     => $user->status === 'inactive',
                            ])>
                                <span @class([
                                    'h-1.5 w-1.5 rounded-full',
                                    'bg-emerald-500' => $user->status === 'active',
                                    'bg-slate-400'   => $user->status === 'inactive',
                                ])></span>
                                {{ $user->status === 'active' ? 'نشط' : 'معطّل' }}
                            </span>
                        </td>

                        {{-- إجراءات --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                @can('users.edit')
                                <button wire:click="openEditModal({{ $user->id }})"
                                    class="erp-btn-soft erp-btn-sm">تعديل</button>
                                @endcan

                                @can('users.deactivate')
                                @if($user->id !== auth()->id())
                                    <button wire:click="toggleStatus({{ $user->id }})"
                                        @class([
                                            'erp-btn-sm rounded-xl px-3 py-1.5 text-xs font-semibold transition',
                                            'bg-slate-100 text-slate-600 hover:bg-rose-100 hover:text-rose-700' => $user->status === 'active',
                                            'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'               => $user->status === 'inactive',
                                        ])>
                                        {{ $user->status === 'active' ? 'تعطيل' : 'تفعيل' }}
                                    </button>

                                    <button wire:click="confirmDelete({{ $user->id }})"
                                        class="erp-btn-sm rounded-xl px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-100 transition">
                                        حذف
                                    </button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-400">لا توجد نتائج</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>


    {{-- ══════════════════════════════ مودال إنشاء / تعديل مستخدم ══════════════════════════════ --}}
    {{-- ══════════════════════════════ مودال تأكيد الحذف ══════════════════════════════ --}}
    @if($showDeleteConfirm)
        @php $deletingUser = $deletingId ? \App\Models\User::find($deletingId) : null; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-3xl bg-white shadow-2xl">
                <div class="p-6 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-rose-100">
                        <svg class="h-7 w-7 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-900">تأكيد الحذف</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        هل أنت متأكد من حذف المستخدم
                        <span class="font-bold text-slate-800">{{ $deletingUser?->name }}</span>؟
                    </p>
                    <p class="mt-1 text-xs text-rose-500">هذا الإجراء لا يمكن التراجع عنه</p>
                </div>
                <div class="flex gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="cancelDelete"
                        class="flex-1 rounded-2xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                        إلغاء
                    </button>
                    <button wire:click="deleteUser"
                        class="flex-1 rounded-2xl bg-rose-600 py-2.5 text-sm font-bold text-white hover:bg-rose-700 transition">
                        نعم، احذف
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl">

                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-extrabold text-slate-800">
                        {{ $editingId ? 'تعديل المستخدم' : 'مستخدم جديد' }}
                    </h2>
                    <button wire:click="closeModal"
                        class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="erp-label">الاسم</label>
                            <input wire:model="name" type="text" class="erp-input">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="erp-label">البريد الإلكتروني</label>
                            <input wire:model="email" type="email" class="erp-input" dir="ltr">
                            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="erp-label">رقم الجوال</label>
                            <input wire:model="phone" type="text" class="erp-input" dir="ltr">
                        </div>
                        <div>
                            <label class="erp-label">الدور</label>
                            <select wire:model="role" class="erp-select">
                                <option value="">اختر الدور</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->name }}">{{ $r->description ?? $r->name }}</option>
                                @endforeach
                            </select>
                            @error('role') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="erp-label">
                                كلمة المرور
                                @if($editingId) <span class="font-normal text-slate-400">(اتركها فارغة للإبقاء)</span> @endif
                            </label>
                            <input wire:model="password" type="password" class="erp-input" dir="ltr" autocomplete="new-password">
                            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="erp-label">تأكيد كلمة المرور</label>
                            <input wire:model="passwordConfirm" type="password" class="erp-input" dir="ltr" autocomplete="new-password">
                            @error('passwordConfirm') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="erp-label">الحالة</label>
                            <div class="flex gap-3">
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input type="radio" wire:model="status" value="active" class="accent-rose-700">
                                    <span class="text-sm font-semibold text-slate-700">نشط</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input type="radio" wire:model="status" value="inactive" class="accent-rose-700">
                                    <span class="text-sm font-semibold text-slate-700">معطّل</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 border-t border-slate-100 px-6 py-4">
                    <button type="button" wire:click="closeModal"
                        class="flex-1 rounded-2xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                        إلغاء
                    </button>
                    <button type="button"
                        wire:click="{{ $editingId ? 'updateUser' : 'createUser' }}"
                        class="flex-1 rounded-2xl bg-rose-700 py-2.5 text-sm font-bold text-white hover:bg-rose-800 transition">
                        {{ $editingId ? 'حفظ التعديلات' : 'إنشاء المستخدم' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
