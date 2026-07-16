<div
    x-data="{
        editMode:         @entangle('editMode'),
        draggingUnitId:   null,
        draggingMarkerId: null,
        dragOffsetX:      0,
        dragOffsetY:      0,
        activePopup:      null,

        /* ─── بدء سحب pin موجود — تسجيل إزاحة المؤشر عن نقطة الـ pin ─── */
        startDragMarker(event, markerId) {
            this.draggingMarkerId = markerId;
            this.draggingUnitId   = null;
            const pinEl   = event.currentTarget.closest('.map-pin');
            const pinRect = pinEl.getBoundingClientRect();
            // المسافة من المؤشر إلى نقطة الـ pin (أسفل المنتصف)
            this.dragOffsetX = event.clientX - (pinRect.left + pinRect.width  / 2);
            this.dragOffsetY = event.clientY - pinRect.bottom;
        },

        /* ─── معالج إفلات موحّد للخريطة (وحدة جديدة أو pin موجود) ─── */
        dropOnMap(event, mapBox) {
            if (! this.editMode) return;
            const rect = mapBox.getBoundingClientRect();

            if (this.draggingMarkerId !== null) {
                // تحريك pin موجود — تصحيح الموضع بالإزاحة المسجّلة
                const tipX = event.clientX - this.dragOffsetX;
                const tipY = event.clientY - this.dragOffsetY;
                $wire.updateMarkerPosition(
                    this.draggingMarkerId,
                    Math.max(0, Math.min(100, ((tipX - rect.left)  / rect.width)  * 100)),
                    Math.max(0, Math.min(100, ((tipY - rect.top)   / rect.height) * 100))
                );
                this.draggingMarkerId = null;
            } else if (this.draggingUnitId !== null) {
                // وضع وحدة جديدة من القائمة الجانبية
                $wire.addMarker(
                    this.draggingUnitId,
                    Math.max(0, Math.min(100, ((event.clientX - rect.left)  / rect.width)  * 100)),
                    Math.max(0, Math.min(100, ((event.clientY - rect.top)   / rect.height) * 100))
                );
                this.draggingUnitId = null;
            }
        },

        statusColor(status) {
            return {
                vacant:      'bg-emerald-500 ring-emerald-200',
                rented:      'bg-rose-600    ring-rose-200',
                maintenance: 'bg-amber-400   ring-amber-200',
                reserved:    'bg-blue-500    ring-blue-200',
            }[status] ?? 'bg-slate-500 ring-slate-200';
        },

        statusLabel(status) {
            return {
                vacant:      'شاغرة',
                rented:      'مؤجرة',
                maintenance: 'صيانة',
                reserved:    'محجوزة',
                unavailable: 'غير متاحة',
            }[status] ?? status;
        },
    }"
    class="space-y-0"
>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- شريط الأدوات                                                    --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-t-3xl border border-b-0 border-slate-200 bg-white px-5 py-3.5">

        {{-- يسار: رفع صورة + اختيار الخريطة --}}
        <div class="flex flex-wrap items-center gap-2">

            {{-- زر رفع صورة --}}
            <button wire:click="openUploadModal"
                class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:border-rose-300 hover:text-rose-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                {{ $maps->isEmpty() ? 'رفع مخطط / صورة جوية' : 'إضافة خريطة أخرى' }}
            </button>

            {{-- تبويبات الخرائط --}}
            @foreach($maps as $map)
                <div class="flex items-center gap-1">
                    <button wire:click="selectMap({{ $map->id }})"
                        @class([
                            'rounded-xl px-3 py-1.5 text-xs font-bold transition',
                            'bg-rose-700 text-white shadow'                        => $activeMap?->id === $map->id,
                            'bg-slate-100 text-slate-600 hover:bg-slate-200'       => $activeMap?->id !== $map->id,
                        ])>
                        {{ $map->name }}
                        <span class="mr-1 text-[10px] opacity-70">
                            {{ $map->map_type === 'satellite' ? '🛰️' : '🏗️' }}
                        </span>
                    </button>
                    @if($editMode && $activeMap?->id === $map->id)
                        {{-- استبدال الصورة (يحتفظ بالـ Pins) --}}
                        <button wire:click="openReplaceModal({{ $map->id }})"
                            class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-slate-500 hover:bg-blue-100 hover:text-blue-600 transition"
                            title="تغيير الصورة (تبقى مواقع الوحدات)">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                        {{-- حذف الخريطة كاملاً --}}
                        <button wire:click="deleteMap({{ $map->id }})"
                            wire:confirm="سيتم حذف هذه الخريطة ومواقع الوحدات عليها نهائياً. هل تريد المتابعة؟"
                            class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] text-slate-500 hover:bg-rose-100 hover:text-rose-600 transition"
                            title="حذف الخريطة كاملاً">✕</button>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- يمين: Legend + زر التعديل --}}
        <div class="flex items-center gap-4">
            {{-- مفتاح الألوان --}}
            @if($activeMap)
                <div class="hidden items-center gap-3 text-xs font-semibold md:flex">
                    <span class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200"></span>شاغرة
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-600 ring-2 ring-rose-200"></span>مؤجرة
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400 ring-2 ring-amber-200"></span>صيانة
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-blue-500 ring-2 ring-blue-200"></span>محجوزة
                    </span>
                </div>
            @endif

            {{-- زر وضع التعديل --}}
            @if($activeMap)
                <button wire:click="toggleEditMode" x-bind:class="editMode ? 'bg-rose-700' : 'bg-slate-800'"
                    class="inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-90">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    <span x-text="editMode ? 'إنهاء التعديل' : 'وضع التعديل'"></span>
                </button>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- المنطقة الرئيسية: خريطة + قائمة جانبية                         --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col overflow-hidden rounded-b-3xl border border-slate-200 bg-white lg:flex-row lg:min-h-[520px]">

        {{-- ── منطقة الخريطة ─────────────────────────────────────────── --}}
        @if($activeMap)
            <div
                class="relative min-h-[320px] flex-1 select-none overflow-hidden bg-slate-100 lg:min-h-0"
                x-ref="mapBox"
                @dragover.prevent
                @drop="dropOnMap($event, $refs.mapBox)"
            >
                {{-- صورة المخطط / الصورة الجوية --}}
                <img src="{{ Storage::url($activeMap->image_path) }}"
                    alt="{{ $activeMap->name }}"
                    class="h-full w-full object-contain"
                    draggable="false">

                {{-- Pins الوحدات --}}
                @foreach($activeMap->markers as $marker)
                    @php
                        $unit   = $marker->unit;
                        $status = $unit->status instanceof \App\Enums\UnitStatus
                                    ? $unit->status->value
                                    : (string) $unit->status;
                        $colors = match($status) {
                            'rented'      => 'bg-rose-600 text-white ring-rose-200',
                            'vacant'      => 'bg-emerald-500 text-white ring-emerald-200',
                            'maintenance' => 'bg-amber-400 text-slate-800 ring-amber-200',
                            'reserved'    => 'bg-blue-500 text-white ring-blue-200',
                            default       => 'bg-slate-600 text-white ring-slate-200',
                        };
                    @endphp
                    <div
                        class="absolute z-10 map-pin"
                        style="left: {{ $marker->x_coordinate }}%; top: {{ $marker->y_coordinate }}%; transform: translate(-50%, -100%);"
                        wire:key="marker-{{ $marker->id }}"
                    >
                        {{-- Pin label --}}
                        <div class="relative flex flex-col items-center">
                            <button
                                class="relative rounded-2xl px-3 py-1.5 text-xs font-black shadow-lg ring-2 ring-white transition-all
                                    {{ $colors }}
                                    {{ 'hover:scale-110 hover:shadow-xl' }}"
                                @if($editMode)
                                    draggable="true"
                                    @dragstart="startDragMarker($event, {{ $marker->id }})"
                                    title="اسحب لتغيير الموقع"
                                    style="cursor: grab"
                                @else
                                    @click="activePopup = activePopup === {{ $marker->id }} ? null : {{ $marker->id }}"
                                    style="cursor: pointer"
                                @endif
                            >
                                {{ $marker->label ?? $unit->internal_number ?? $unit->name }}

                                {{-- زر حذف في وضع التعديل --}}
                                <span
                                    x-show="editMode"
                                    @click.stop="$wire.removeMarker({{ $marker->id }})"
                                    class="absolute -left-1.5 -top-1.5 flex h-4 w-4 cursor-pointer items-center justify-center rounded-full bg-white text-[9px] font-black text-rose-600 shadow ring-1 ring-rose-200 hover:bg-rose-600 hover:text-white transition"
                                    title="إزالة من الخريطة"
                                >✕</span>
                            </button>

                            {{-- خط الـ Pin --}}
                            <div class="h-2 w-px {{ str_contains($colors, 'rose') ? 'bg-rose-600' : (str_contains($colors, 'emerald') ? 'bg-emerald-500' : (str_contains($colors, 'amber') ? 'bg-amber-400' : (str_contains($colors, 'blue') ? 'bg-blue-500' : 'bg-slate-600'))) }}"></div>
                            <div class="h-1.5 w-1.5 rounded-full bg-current ring-2 ring-white {{ str_contains($colors, 'rose') ? 'text-rose-600' : (str_contains($colors, 'emerald') ? 'text-emerald-500' : (str_contains($colors, 'amber') ? 'text-amber-400' : (str_contains($colors, 'blue') ? 'text-blue-500' : 'text-slate-600'))) }}"></div>

                            {{-- Popup تفاصيل الوحدة --}}
                            <div
                                x-show="activePopup === {{ $marker->id }} && !editMode"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-cloak
                                class="absolute bottom-full mb-2 z-30 w-48 rounded-2xl bg-white p-4 shadow-2xl ring-1 ring-slate-100 text-right"
                                style="left: 50%; transform: translateX(-50%);"
                                @click.outside="activePopup = null"
                            >
                                {{-- مثلث --}}
                                <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 h-3 w-3 rotate-45 bg-white ring-1 ring-slate-100 clip-none" style="clip-path: polygon(0 0, 100% 0, 100% 100%)"></div>

                                <div class="font-black text-slate-800 text-sm">{{ $unit->name }}</div>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full ring-1 {{ str_contains($colors,'rose') ? 'bg-rose-500 ring-rose-200' : (str_contains($colors,'emerald') ? 'bg-emerald-500 ring-emerald-200' : (str_contains($colors,'amber') ? 'bg-amber-400 ring-amber-200' : 'bg-blue-500 ring-blue-200')) }}"></span>
                                    <span class="text-xs font-semibold text-slate-500">
                                        {{ ['vacant'=>'شاغرة','rented'=>'مؤجرة','maintenance'=>'صيانة','reserved'=>'محجوزة'][$status] ?? $status }}
                                    </span>
                                </div>
                                <div class="mt-2.5 space-y-1 text-xs text-slate-500 border-t border-slate-100 pt-2.5">
                                    @if($unit->internal_number)
                                        <div>الرقم الداخلي: <span class="font-semibold text-slate-700">{{ $unit->internal_number }}</span></div>
                                    @endif
                                    @if($unit->floor)
                                        <div>الدور: <span class="font-semibold text-slate-700">{{ $unit->floor }}</span></div>
                                    @endif
                                    @if($unit->area)
                                        <div>المساحة: <span class="font-semibold text-slate-700">{{ $unit->area }} م²</span></div>
                                    @endif
                                </div>
                                <a href="{{ route('units.show', $unit) }}"
                                    class="mt-3 block w-full rounded-xl bg-rose-700 py-2 text-center text-xs font-bold text-white hover:bg-rose-800 transition">
                                    فتح الوحدة ←
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- تلميح وضع التعديل --}}
                <div
                    x-show="editMode"
                    x-transition
                    x-cloak
                    class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-2xl bg-slate-900/80 px-4 py-2 text-xs font-semibold text-white backdrop-blur-sm whitespace-nowrap"
                >
                    🖱️ اسحب الـ Pins لتغيير مواقعها · اسحب الوحدات من القائمة لإضافتها
                </div>
            </div>

        @else
            {{-- حالة عدم وجود خريطة --}}
            <div class="flex flex-1 flex-col items-center justify-center gap-4 bg-slate-50 py-20 text-center">
                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-200 text-4xl">🏗️</div>
                <div>
                    <p class="font-bold text-slate-700">لا توجد خريطة لهذا العقار</p>
                    <p class="mt-1 text-sm text-slate-400">ارفع مخططاً معمارياً أو صورة جوية لتبدأ بتحديد مواقع الوحدات</p>
                </div>
                <button wire:click="openUploadModal"
                    class="inline-flex items-center gap-2 rounded-2xl bg-rose-700 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-rose-800 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    رفع الخريطة الأولى
                </button>
            </div>
        @endif

        {{-- ── القائمة الجانبية: وحدات بدون موقع ──────────────────────── --}}
        @if($activeMap)
            <div class="flex flex-col border-t border-slate-200 lg:w-52 lg:shrink-0 lg:border-t-0 lg:border-r">

                {{-- رأس القائمة --}}
                <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-black uppercase tracking-wider text-slate-400">وحدات بدون موقع</p>
                    <p class="mt-0.5 text-[10px] text-slate-400">
                        @if($editMode)
                            اسحبها وأفلتها على الخريطة
                        @else
                            فعّل وضع التعديل للسحب
                        @endif
                    </p>
                </div>

                {{-- قائمة الوحدات --}}
                <div class="flex-1 divide-y divide-slate-50 overflow-y-auto p-1.5 max-h-48 lg:max-h-none">
                    @forelse($unplacedUnits as $unit)
                        @php
                            $uStatus = $unit->status instanceof \App\Enums\UnitStatus
                                ? $unit->status->value
                                : (string) $unit->status;
                            $badge = match($uStatus) {
                                'vacant'      => 'bg-emerald-100 text-emerald-700',
                                'rented'      => 'bg-rose-100 text-rose-700',
                                'maintenance' => 'bg-amber-100 text-amber-700',
                                'reserved'    => 'bg-blue-100 text-blue-700',
                                default       => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <div
                            draggable="true"
                            @dragstart="editMode && (draggingUnitId = {{ $unit->id }}, draggingMarkerId = null)"
                            @dragend="draggingUnitId = null"
                            x-bind:class="editMode ? 'cursor-grab hover:bg-rose-50' : 'cursor-default opacity-60'"
                            class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-sm transition select-none"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-[10px] font-black {{ $badge }}">
                                {{ mb_substr(['vacant'=>'شاغ','rented'=>'مؤج','maintenance'=>'صيا','reserved'=>'محج'][$uStatus] ?? 'X', 0, 2) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-xs font-bold text-slate-700">{{ $unit->name }}</div>
                                <div class="text-[10px] text-slate-400">
                                    {{ $unit->floor ? 'دور '.$unit->floor.' · ' : '' }}{{ $unit->area ? $unit->area.' م²' : $unit->code }}
                                </div>
                            </div>
                            <svg x-show="editMode" class="h-3.5 w-3.5 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/>
                            </svg>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="text-2xl">✅</div>
                            <p class="mt-2 text-xs font-semibold text-slate-400">كل الوحدات موضوعة على الخريطة</p>
                        </div>
                    @endforelse
                </div>

                {{-- زر حفظ --}}
                <div x-show="editMode" x-transition x-cloak class="border-t border-slate-100 p-2.5">
                    <button wire:click="toggleEditMode"
                        class="w-full rounded-2xl bg-emerald-600 py-2.5 text-xs font-black text-white hover:bg-emerald-700 transition">
                        ✓ تم · حفظ المواقع
                    </button>
                </div>
            </div>
        @endif
    </div>


    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- مودال رفع الخريطة                                               --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    @if($showUploadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl" x-data x-trap.noscroll="true">

                {{-- رأس المودال --}}
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-800">
                            {{ $replacingMapId ? 'تغيير صورة الخريطة' : 'رفع خريطة العقار' }}
                        </h2>
                        @if($replacingMapId)
                            <p class="mt-0.5 text-xs text-emerald-600 font-semibold">✓ مواقع الوحدات ستبقى كما هي</p>
                        @endif
                    </div>
                    <button wire:click="$set('showUploadModal', false)"
                        class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="uploadMap" class="p-6 space-y-5">

                    {{-- نوع الصورة --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">نوع الصورة</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label @class([
                                'flex cursor-pointer items-center gap-3 rounded-2xl border-2 p-4 transition',
                                'border-rose-600 bg-rose-50' => $mapType === 'floor_plan',
                                'border-slate-200 hover:border-slate-300' => $mapType !== 'floor_plan',
                            ])>
                                <input type="radio" wire:model.live="mapType" value="floor_plan" class="sr-only">
                                <div>
                                    <div class="font-bold text-sm">🏗️ مخطط معماري</div>
                                    <div class="text-xs text-slate-400">تصميم الطوابق</div>
                                </div>
                            </label>
                            <label @class([
                                'flex cursor-pointer items-center gap-3 rounded-2xl border-2 p-4 transition',
                                'border-rose-600 bg-rose-50' => $mapType === 'satellite',
                                'border-slate-200 hover:border-slate-300' => $mapType !== 'satellite',
                            ])>
                                <input type="radio" wire:model.live="mapType" value="satellite" class="sr-only">
                                <div>
                                    <div class="font-bold text-sm">🛰️ صورة جوية</div>
                                    <div class="text-xs text-slate-400">صورة الموقع</div>
                                </div>
                            </label>
                        </div>
                        @error('mapType') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- اسم الخريطة --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-slate-700">اسم الخريطة</label>
                        <input wire:model="mapName" type="text"
                            class="w-full rounded-2xl border border-slate-200 px-3 py-2.5 text-sm focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                            placeholder="مثال: مخطط الطابق الأول">
                        @error('mapName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- منطقة رفع الملف --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-slate-700">الملف <span class="text-rose-600">*</span></label>
                        <label class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-300 bg-slate-50 py-8 text-center transition cursor-pointer hover:border-rose-400 hover:bg-rose-50">
                            <input type="file" wire:model="mapImage" accept=".jpg,.jpeg,.png,.pdf" class="sr-only">
                            @if($mapImage)
                                <div class="text-2xl">📄</div>
                                <p class="mt-2 text-sm font-semibold text-emerald-600">{{ $mapImage->getClientOriginalName() }}</p>
                                <p class="text-xs text-slate-400">انقر للتغيير</p>
                            @else
                                <div class="text-3xl">📁</div>
                                <p class="mt-2 font-semibold text-slate-600">اسحب الملف هنا أو انقر للاختيار</p>
                                <p class="text-xs text-slate-400">JPG · PNG · PDF · حتى 20MB</p>
                            @endif
                        </label>
                        @error('mapImage') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- أزرار --}}
                    <div class="flex gap-3 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="$set('showUploadModal', false)"
                            class="flex-1 rounded-2xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                            إلغاء
                        </button>
                        <button type="submit"
                            class="flex-1 rounded-2xl bg-rose-700 py-2.5 text-sm font-bold text-white hover:bg-rose-800 transition">
                            <span wire:loading.remove wire:target="uploadMap">
                                {{ $replacingMapId ? 'تحديث الصورة' : 'رفع الخريطة' }}
                            </span>
                            <span wire:loading wire:target="uploadMap">جاري الرفع...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
