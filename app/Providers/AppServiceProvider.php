<?php

namespace App\Providers;

use App\Livewire\Attachments\AttachmentManager;
use App\Domains\Map\Livewire\PropertyMapBoard;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // تسجيل مكوّنات Livewire الموجودة خارج App\Livewire
        Livewire::component('property-map-board', PropertyMapBoard::class);
        // AttachmentManager مُنقول إلى App\Livewire\Attachments — يُكتشف تلقائياً
        // نبقّي التسجيل اليدوي للـ alias attachment-manager للتوافق مع العرض الموجود
        Livewire::component('attachment-manager', AttachmentManager::class);
    }
}
