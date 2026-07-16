<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'اوج العقاريه' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="erp-page antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-72 bg-rose-950 p-4 lg:block">
            <div class="mb-8 rounded-3xl bg-rose-900/60 p-5 text-white">
                <div class="text-lg font-bold">اوج العقاريه</div>
                <div class="mt-1 text-xs text-rose-300">نظام ادارة العقارات</div>
            </div>
            <nav class="space-y-1">

                @php
                    $links = [
                        ['route' => 'dashboard',          'pattern' => 'dashboard',        'label' => 'لوحة التحكم',     'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'companies.index',    'pattern' => 'companies.*',      'label' => 'الشركات',         'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['route' => 'properties.index',   'pattern' => 'properties.*',     'label' => 'العقارات',        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['route' => 'units.index',        'pattern' => 'units.*',          'label' => 'الوحدات',         'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
                        ['route' => 'tenants.index',      'pattern' => 'tenants.*',        'label' => 'المستأجرون',      'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['route' => 'contracts.index',    'pattern' => 'contracts.*',      'label' => 'العقود',          'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['route' => 'maintenance.index',  'pattern' => 'maintenance.*',    'label' => 'الصيانة',         'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['route' => 'payments.tenants',   'pattern' => 'payments.tenants', 'label' => 'دفعات المستأجرين','icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'payments.leases',    'pattern' => 'payments.leases',  'label' => 'دفعات الملاك',    'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        ['route' => 'notifications.index','pattern' => 'notifications.*',  'label' => 'التنبيهات',       'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                        ['route' => 'reports.index',      'pattern' => 'reports.*',        'label' => 'التقارير',        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ];

                    $settingsLinks = [
                        ['route' => 'users.index',    'pattern' => 'users.*',    'label' => 'المستخدمون',   'permission' => 'users.view',    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['route' => 'roles.index',    'pattern' => 'roles.*',    'label' => 'الأدوار',      'permission' => 'roles.view',    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        ['route' => 'archive.index',  'pattern' => 'archive.*',  'label' => 'الأرشيف',      'permission' => null,            'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
                        ['route' => 'activity.index', 'pattern' => 'activity.*', 'label' => 'سجل الحركات', 'permission' => 'activity.view', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                    ];

                    // تفتح القائمة تلقائياً إذا كانت الصفحة الحالية إحدى روابط الإعدادات
                    $settingsOpen = collect($settingsLinks)->contains(
                        fn($l) => request()->routeIs($l['pattern'])
                    );
                @endphp

                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}"
                       class="erp-nav-link {{ request()->routeIs($link['pattern']) ? 'erp-nav-link-active' : '' }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
                        </svg>
                        {{ $link['label'] }}
                    </a>
                @endforeach

                {{-- ── قائمة الإعدادات المنسدلة ─────────────────── --}}
                @php
                    $hasAnySettingsAccess = collect($settingsLinks)->contains(
                        fn($l) => ! $l['permission'] || auth()->user()?->can($l['permission'])
                    );
                @endphp
                @if($hasAnySettingsAccess)
                <div x-data="{ open: {{ $settingsOpen ? 'true' : 'false' }} }" class="mt-1">

                    {{-- زر فتح/إغلاق --}}
                    <button @click="open = !open"
                        class="erp-nav-link w-full justify-between
                            {{ $settingsOpen ? 'bg-rose-900/50 text-white' : '' }}">
                        <span class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            الإعدادات
                        </span>
                        <svg class="h-3.5 w-3.5 shrink-0 text-rose-300 transition-transform duration-200"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- الروابط الداخلية --}}
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="mt-0.5 space-y-0.5 pr-3">
                        @foreach($settingsLinks as $link)
                            @if(! $link['permission'] || auth()->user()?->can($link['permission']))
                            <a href="{{ route($link['route']) }}"
                               class="erp-nav-link py-2 text-[13px]
                                   {{ request()->routeIs($link['pattern']) ? 'erp-nav-link-active' : '' }}">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
                                </svg>
                                {{ $link['label'] }}
                            </a>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

            </nav>

            {{-- معلومات المستخدم وتسجيل الخروج --}}
            <div class="mt-6 border-t border-rose-900 pt-4">
                <div class="mb-2 flex items-center gap-3 rounded-xl px-3 py-2">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-800 text-xs font-bold text-rose-100">
                        {{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-white">{{ auth()->user()->name ?? '' }}</div>
                        <div class="truncate text-xs text-rose-300">{{ auth()->user()->email ?? '' }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-rose-200 transition hover:bg-rose-800/50 hover:text-white">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </aside>
        <main class="flex-1 min-h-screen">
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js" defer></script>

    {{-- Toast Notifications --}}
    <div
        x-data="{
            toasts: [],
            add(msg) {
                const id = Date.now();
                this.toasts.push({ id, msg });
                setTimeout(() => this.remove(id), 4000);
            },
            remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
        }"
        x-on:notify.window="add($event.detail.message)"
        class="fixed bottom-6 left-6 z-[100] flex flex-col gap-2"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="flex items-center gap-3 rounded-2xl bg-slate-900 px-5 py-3.5 text-sm font-semibold text-white shadow-xl">
                <span class="text-emerald-400">✓</span>
                <span x-text="toast.msg"></span>
                <button @click="remove(toast.id)" class="mr-2 text-slate-400 hover:text-white">×</button>
            </div>
        </template>
    </div>
</body>
</html>
