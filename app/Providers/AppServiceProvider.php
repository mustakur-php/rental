<?php

namespace App\Providers;

use App\Domains\Attachment\Livewire\AttachmentManager;
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
        Livewire::component('attachment-manager', AttachmentManager::class);
    }
}
