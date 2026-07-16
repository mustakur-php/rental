<div class="inline-flex rounded-2xl bg-slate-100 p-1 text-sm font-semibold">
    <button wire:click="$set('viewMode','cards')" @class(['rounded-xl px-4 py-2 transition', 'bg-white shadow-sm text-slate-900' => $viewMode === 'cards', 'text-slate-500 hover:text-slate-700' => $viewMode !== 'cards'])>كروت</button>
    <button wire:click="$set('viewMode','table')" @class(['rounded-xl px-4 py-2 transition', 'bg-white shadow-sm text-slate-900' => $viewMode === 'table', 'text-slate-500 hover:text-slate-700' => $viewMode !== 'table'])>جدول</button>
    <button wire:click="$set('viewMode','map')"   @class(['rounded-xl px-4 py-2 transition', 'bg-white shadow-sm text-slate-900' => $viewMode === 'map',   'text-slate-500 hover:text-slate-700' => $viewMode !== 'map'])>خريطة</button>
</div>
