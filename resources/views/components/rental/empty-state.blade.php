@props(['title', 'message'])

<div class="col-span-full rounded-[2rem] border border-dashed border-slate-300 bg-white p-12 text-center">
    <h3 class="text-lg font-black text-slate-900">{{ $title }}</h3>
    <p class="mt-2 text-sm text-slate-500">{{ $message }}</p>
</div>
