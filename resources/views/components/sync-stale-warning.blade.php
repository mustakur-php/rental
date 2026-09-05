@php
    use App\Domains\Notification\Services\NotificationSyncService;

    $lastSync = NotificationSyncService::lastSyncedAt();
@endphp

@if(NotificationSyncService::isStale())
    <div class="mb-6 rounded-3xl border-2 border-rose-200 bg-rose-50 px-6 py-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <p class="text-sm font-bold text-rose-800">
                        @if($lastSync)
                            لم تتم مزامنة التنبيهات منذ {{ $lastSync->diffForHumans(null, true) }}
                        @else
                            لم تتم أي مزامنة للتنبيهات
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-rose-600">
                        قد تكون الجدولة التلقائية متوقفة — الحالات والتنبيهات المعروضة قد لا تكون محدّثة.
                    </p>
                </div>
            </div>

            @isset($action)
                {{ $action }}
            @else
                <a href="{{ route('notifications.index') }}"
                   class="shrink-0 rounded-2xl bg-rose-700 px-4 py-2 text-sm font-bold text-white hover:bg-rose-800 transition">
                    فتح مركز التنبيهات
                </a>
            @endisset
        </div>
    </div>
@endif
