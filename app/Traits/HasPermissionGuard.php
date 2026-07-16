<?php

namespace App\Traits;

/**
 * يُضاف لمكوّنات Livewire لحماية الأفعال بفحص الصلاحيات.
 * إذا لم يملك المستخدم الصلاحية يُرسل إشعاراً ويوقف التنفيذ.
 */
trait HasPermissionGuard
{
    /**
     * يتحقق من الصلاحية — إذا لم تتوفر يُرسل إشعاراً ويعيد false.
     * الاستخدام: if (!$this->can('xxx.yyy')) return;
     */
    protected function requirePermission(string $permission): bool
    {
        if (! auth()->user()?->can($permission)) {
            $this->dispatch('notify', message: 'ليس لديك صلاحية لهذه العملية');
            return false;
        }
        return true;
    }
}
