<?php

namespace App\Domains\Property\Models;

use App\Domains\Attachment\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Domains\Property\Models\PropertyLeasePeriod;
use Illuminate\Support\Facades\Storage;

class PropertyLease extends Model
{
    protected $fillable = [
        'property_id', 'owner_name', 'owner_mobile', 'owner_iban',
        'lease_contract_number', 'contract_file_path', 'start_date', 'end_date',
        'total_amount', 'vat_rate', 'vat_amount', 'total_with_vat',
        'payment_cycle', 'installments_count',
        'status', 'notes', 'archived_at', 'archived_reason', 'archived_notes',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'total_amount'   => 'decimal:2',
        'vat_rate'       => 'decimal:2',
        'vat_amount'     => 'decimal:2',
        'total_with_vat' => 'decimal:2',
        'archived_at'    => 'datetime',
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

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
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
