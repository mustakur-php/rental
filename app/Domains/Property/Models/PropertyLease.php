<?php

namespace App\Domains\Property\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domains\Property\Models\PropertyLeasePeriod;

class PropertyLease extends Model
{
    protected $fillable = [
        'property_id', 'owner_name', 'owner_mobile', 'owner_iban',
        'lease_contract_number', 'start_date', 'end_date',
        'total_amount', 'payment_cycle', 'installments_count',
        'status', 'notes', 'archived_at', 'archived_reason', 'archived_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'total_amount' => 'decimal:2',
        'archived_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PropertyLeaseSchedule::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(PropertyLeasePeriod::class)->orderBy('period_no');
    }

    public function activeSchedules(): HasMany
    {
        return $this->hasMany(PropertyLeaseSchedule::class)->where('status', '!=', 'paid');
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
