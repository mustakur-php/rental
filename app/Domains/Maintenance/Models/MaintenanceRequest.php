<?php

namespace App\Domains\Maintenance\Models;

use App\Enums\MaintenanceStatus;
use App\Domains\Attachment\Models\Attachment;
use App\Domains\Property\Models\Property;
use App\Domains\Unit\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'property_id',
        'unit_id',
        'type',
        'title',
        'description',
        'priority',
        'status',
        'request_date',
        'completed_date',
        'cost',
        'unit_impact',
    ];

    protected $casts = [
        'request_date' => 'date',
        'completed_date' => 'date',
        'cost' => 'decimal:2',
        'status' => MaintenanceStatus::class,
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            MaintenanceStatus::New->value,
            MaintenanceStatus::InProgress->value,
        ]);
    }
}
