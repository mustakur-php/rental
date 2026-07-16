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
        'payload',
    ];

    protected $casts = [
        'trigger_date' => 'date',
        'resolved_at' => 'datetime',
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
}
