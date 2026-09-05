<?php

namespace App\Domains\Notification\Models;

use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'notifiable_source_type',
        'notifiable_source_id',
        'type',
        'severity',
        'title',
        'message',
        'trigger_date',
        'resolved_at',
        'status',
        'snoozed_until',
        'payload',
    ];

    protected $casts = [
        'trigger_date' => 'date',
        'resolved_at' => 'datetime',
        'snoozed_until' => 'datetime',
        'payload' => 'array',
        'status' => NotificationStatus::class,
    ];

    public function notifiableSource(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'notifiable_source_type', 'notifiable_source_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', NotificationStatus::Open->value);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', NotificationStatus::Resolved->value);
    }

    /**
     * التنبيهات المعروضة للمستخدم: مفتوحة وغير مؤجَّلة.
     *
     * scopeOpen يبقى كما هو عمداً — استعلامات التنظيف في NotificationSyncService
     * تعتمد عليه، ويجب أن تصل إلى المؤجَّلة أيضاً لتحذفها عند حل سببها.
     */
    public function scopeVisible($query)
    {
        return $query->open()->where(
            fn ($q) => $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now())
        );
    }

    public function scopeSnoozed($query)
    {
        return $query->open()->whereNotNull('snoozed_until')->where('snoozed_until', '>', now());
    }

    public function isSnoozed(): bool
    {
        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }

    /**
     * رابط الصفحة التي تُعالَج فيها المشكلة. يُقرأ من payload لا من العلاقة،
     * حتى لا يُنتج كل صف استعلاماً إضافياً في قائمة مرقّمة.
     */
    public function sourceUrl(): ?string
    {
        $payload = $this->payload ?? [];

        return match ($this->notifiable_source_type) {
            \App\Domains\Payment\Models\PaymentSchedule::class => isset($payload['contract_id'])
                ? route('contracts.schedule', $payload['contract_id'])
                : null,

            \App\Domains\Contract\Models\Contract::class => route('contracts.schedule', $this->notifiable_source_id),

            \App\Domains\Unit\Models\Unit::class => route('units.show', $this->notifiable_source_id),

            \App\Domains\Property\Models\PropertyLeaseSchedule::class,
            \App\Domains\Property\Models\PropertyLease::class => isset($payload['property_id'])
                ? route('properties.show', $payload['property_id'])
                : null,

            default => null,
        };
    }
}
