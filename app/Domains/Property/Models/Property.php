<?php

namespace App\Domains\Property\Models;

use App\Domains\Attachment\Models\Attachment;
use App\Domains\Company\Models\Company;
use App\Domains\Maintenance\Models\MaintenanceRequest;
use App\Domains\Map\Models\PropertyMap;
use App\Domains\Payment\Models\Payment;
use App\Domains\Unit\Models\Unit;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Property extends Model
{
    use HasFactory, LogsModelActivity;

    protected string $activityLogName = 'properties';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'ownership_type',
        'city',
        'district',
        'address',
        'description',
        'status',
        'archived_at',
        'archived_reason',
        'archived_notes',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(PropertyLease::class);
    }

    public function activeLease(): HasOne
    {
        return $this->hasOne(PropertyLease::class)->notArchived()->where('status', 'active')->latest();
    }

    public function maps(): HasMany
    {
        return $this->hasMany(PropertyMap::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function getOccupancyRateAttribute(): float
    {
        $total = $this->units()->notArchived()->count();
        if ($total === 0) return 0;
        $rented = $this->units()->notArchived()->where('status', 'rented')->count();
        return round(($rented / $total) * 100, 2);
    }
}
