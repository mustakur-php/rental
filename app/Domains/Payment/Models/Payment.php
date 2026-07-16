<?php

namespace App\Domains\Payment\Models;

use App\Enums\PaymentStatus;
use App\Traits\LogsModelActivity;
use App\Domains\Attachment\Models\Attachment;
use App\Domains\Contract\Models\Contract;
use App\Domains\Property\Models\Property;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Unit\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payment extends Model
{
    use HasFactory, LogsModelActivity;

    protected string $activityLogName = 'payments';

    protected $fillable = [
        'code',
        'contract_id',
        'payment_schedule_id',
        'tenant_id',
        'unit_id',
        'property_id',
        'amount',
        'payment_date',
        'method',
        'reference_number',
        'notes',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'status' => PaymentStatus::class,
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function paymentSchedule(): BelongsTo
    {
        return $this->belongsTo(PaymentSchedule::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeRegistered($query)
    {
        return $query->where('status', PaymentStatus::Registered->value);
    }
}
