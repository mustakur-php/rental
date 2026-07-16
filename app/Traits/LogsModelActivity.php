<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait يُضاف للموديلات لتسجيل جميع التعديلات تلقائياً مع الفروقات.
 * يمكن override  getActivitylogOptions() لتخصيص السلوك.
 */
trait LogsModelActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->activityLogName ?? class_basename(static::class))
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم الإنشاء',
                'updated' => 'تم التعديل',
                'deleted' => 'تم الحذف',
                default   => $event,
            });
    }
}
