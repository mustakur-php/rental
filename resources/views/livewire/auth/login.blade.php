<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- الشعار --}}
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-rose-800 shadow-lg">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-slate-900">أوج العقاريه</h1>
            <p class="mt-1 text-sm text-slate-500">نظام إدارة العقارات</p>
        </div>

        {{-- البطاقة --}}
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">

            <h2 class="mb-6 text-xl font-bold text-slate-900">تسجيل الدخول</h2>

            <form wire:submit="login" class="space-y-5">

                {{-- البريد الإلكتروني --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        البريد الإلكتروني
                    </label>
                    <input
                        wire:model="email"
                        type="email"
                        autocomplete="email"
                        autofocus
                        placeholder="example@domain.com"
                        @class([
                            'w-full rounded-2xl border px-4 py-3 text-sm transition focus:outline-none focus:ring-2',
                            'border-rose-300 bg-rose-50 focus:ring-rose-200' => $errors->has('email'),
                            'border-slate-200 bg-slate-50 focus:ring-slate-200' => !$errors->has('email'),
                        ])
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- كلمة المرور --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        كلمة المرور
                    </label>
                    <input
                        wire:model="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        @class([
                            'w-full rounded-2xl border px-4 py-3 text-sm transition focus:outline-none focus:ring-2',
                            'border-rose-300 bg-rose-50 focus:ring-rose-200' => $errors->has('password'),
                            'border-slate-200 bg-slate-50 focus:ring-slate-200' => !$errors->has('password'),
                        ])
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- تذكرني --}}
                <div class="flex items-center gap-3">
                    <input
                        wire:model="remember"
                        type="checkbox"
                        id="remember"
                        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                    >
                    <label for="remember" class="text-sm text-slate-600 cursor-pointer">
                        تذكرني
                    </label>
                </div>

                {{-- زر الدخول --}}
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full rounded-2xl bg-rose-700 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-rose-800 active:scale-95 disabled:opacity-60"
                >
                    <span wire:loading.remove>دخول</span>
                    <span wire:loading class="flex items-center justify-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        جاري التحقق...
                    </span>
                </button>

            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            Rental ERP &copy; {{ date('Y') }}
        </p>
    </div>
</div>

