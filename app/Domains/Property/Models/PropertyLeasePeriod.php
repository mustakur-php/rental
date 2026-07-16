<?php

namespace App\Domains\Property\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyLeasePeriod extends Model
{
    protected $fillable = [
        'property_lease_id',
        'period_no',
        'duration_months',
        'annual_amount',
        'increase_percentage',
    ];

    protected $casts = [
        'annual_amount'       => 'decimal:2',
        'increase_percentage' => 'decimal:2',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(PropertyLease::class, 'property_lease_id');
    }
}
