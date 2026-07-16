<?php

namespace App\Domains\Unit\Models;

use App\Enums\UnitStatus;
use App\Traits\LogsModelActivity;
use App\Domains\Attachment\Models\Attachment;
use App\Domains\Contract\Models\Contract;
use App\Domains\Maintenance\Models\MaintenanceRequest;
use App\Domains\Map\Models\UnitMapMarker;
use App\Domains\Payment\Models\Payment;
use App\Domains\Property\Models\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Unit extends Model
{
    use HasFactory, LogsModelActivity;

    protected string $activityLogName = 'units';

    protected $fillable = [
        'property_id',
        'code',
        'name',
        'type',
        'internal_number',
        'area',
        'floor',
        'electricity_meter',
        'water_meter',
        'description',
        'status',
        'archived_at',
        'archived_reason',
        'archived_notes',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'status' => UnitStatus::class,
        'archived_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract()
    {
        return $this->hasOne(Contract::class)->notArchived()->where('status', 'active');
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function mapMarkers(): HasMany
    {
        return $this->hasMany(UnitMapMarker::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeVacant($query)
    {
        return $query->where('status', UnitStatus::Vacant->value);
    }

    public function scopeRented($query)
    {
        return $query->where('status', UnitStatus::Rented->value);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
}
