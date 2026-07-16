<?php

namespace App\Domains\Property\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyLeaseSchedule extends Model
{
    protected $fillable = [
        'property_lease_id', 'installment_no', 'due_date',
        'amount', 'paid_amount', 'remaining_amount',
        'status', 'paid_at', 'payment_method', 'reference_number', 'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at'  => 'date',
        'amount'          => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'remaining_amount'=> 'decimal:2',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(PropertyLease::class, 'property_lease_id');
    }
}
