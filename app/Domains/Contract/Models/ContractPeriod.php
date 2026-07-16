<?php

namespace App\Domains\Contract\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPeriod extends Model
{
    protected $fillable = [
        'contract_id',
        'period_no',
        'duration_months',
        'annual_amount',
        'increase_percentage',
    ];

    protected $casts = [
        'annual_amount'       => 'decimal:2',
        'increase_percentage' => 'decimal:2',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
