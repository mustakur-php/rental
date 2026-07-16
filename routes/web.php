<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Archive\ArchiveIndex;
use App\Livewire\Dashboard\MainDashboard;
use App\Livewire\Properties\PropertyIndex;
use App\Livewire\Properties\PropertyShow;
use App\Livewire\Units\GlobalUnitsIndex;
use App\Livewire\Tenants\TenantIndex;
use App\Livewire\Contracts\ContractIndex;
use App\Livewire\Contracts\ContractSchedule;
use App\Livewire\Contracts\CreateContractWizard;
use App\Livewire\Maintenance\MaintenanceIndex;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Payments\TenantSchedulesIndex;
use App\Livewire\Payments\LeaseSchedulesIndex;
use App\Livewire\Reports\ReportsDashboard;
use App\Livewire\Companies\CompanyIndex;
use App\Livewire\Units\UnitShow;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\RoleIndex;
use App\Livewire\ActivityLog\ActivityLogIndex;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', MainDashboard::class)->name('dashboard');
    Route::get('/notifications', NotificationCenter::class)->middleware('permission:notifications.view')->name('notifications.index');

    // ── العقارات ──────────────────────────────────────
    Route::middleware('permission:properties.view')->group(function () {
        Route::get('/properties',            PropertyIndex::class)->name('properties.index');
        Route::get('/properties/{property}', PropertyShow::class)->name('properties.show');
    });

    // ── الوحدات ───────────────────────────────────────
    Route::middleware('permission:units.view')->group(function () {
        Route::get('/units',         GlobalUnitsIndex::class)->name('units.index');
        Route::get('/units/{unit}',  UnitShow::class)->name('units.show');
    });

    // ── المستأجرون ────────────────────────────────────
    Route::get('/tenants', TenantIndex::class)->middleware('permission:tenants.view')->name('tenants.index');

    // ── العقود ────────────────────────────────────────
    Route::middleware('permission:contracts.view')->group(function () {
        Route::get('/contracts',                     ContractIndex::class)->name('contracts.index');
        Route::get('/contracts/{contract}/schedule', ContractSchedule::class)->name('contracts.schedule');
    });
    Route::get('/contracts/create', CreateContractWizard::class)->middleware('permission:contracts.create')->name('contracts.create');

    // ── الصيانة ───────────────────────────────────────
    Route::get('/maintenance', MaintenanceIndex::class)->middleware('permission:maintenance.view')->name('maintenance.index');

    // ── الدفعات ───────────────────────────────────────
    Route::middleware('permission:payments.view')->group(function () {
        Route::get('/payments/tenants', TenantSchedulesIndex::class)->name('payments.tenants');
        Route::get('/payments/leases',  LeaseSchedulesIndex::class)->name('payments.leases');
    });

    // ── التقارير ──────────────────────────────────────
    Route::get('/reports', ReportsDashboard::class)->middleware('permission:reports.view')->name('reports.index');

    // ── الشركات ───────────────────────────────────────
    Route::get('/companies', CompanyIndex::class)->middleware('permission:companies.view')->name('companies.index');

    // ── الأرشيف ───────────────────────────────────────
    Route::get('/archive', ArchiveIndex::class)->middleware('permission:archive.view')->name('archive.index');

    // ── الإعدادات: مستخدمون / أدوار / سجل حركات ──────
    Route::get('/users',         UserIndex::class)->middleware('permission:users.view')->name('users.index');
    Route::get('/roles',         RoleIndex::class)->middleware('permission:roles.view')->name('roles.index');
    Route::get('/activity-log',  ActivityLogIndex::class)->middleware('permission:activity.view')->name('activity.index');
});
