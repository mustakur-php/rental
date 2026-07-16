<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="erp-title">{{ $title }}</h1>
        @isset($subtitle)
            <p class="erp-subtitle">{{ $subtitle }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
