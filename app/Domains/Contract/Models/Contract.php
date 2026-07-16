<?php

namespace App\Domains\Contract\Models;

use App\Enums\ContractStatus;
use App\Traits\LogsModelActivity;
use App\Domains\Attachment\Models\Attachment;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentSchedule;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Unit\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contract extends Model
{
    use HasFactory, LogsModelActivity;

    protected string $activityLogName = 'contracts';

    protected $fillable = [
        'tenant_id',
        'unit_id',
        'previous_contract_id',
        'code',
        'start_date',
        'end_date',
        'total_contract_amount',
        'vat_rate',
        'vat_amount',
        'total_with_vat',
        'deposit_amount',
        'currency',
        'payment_cycle',
        'installments_count',
        'status',
        'termination_date',
        'termination_reason',
        'termination_notes',
        'notes',
        'ejar_number',
        'contract_file_path',
        'archived_at',
        'archived_reason',
        'archived_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'termination_date' => 'date',
        'total_contract_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_with_vat' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'status' => ContractStatus::class,
        'archived_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function previousContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'previous_contract_id');
    }

    public function renewedContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'previous_contract_id');
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(PaymentSchedule::class);
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
        return $query->where('status', ContractStatus::Active->value);
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeExpiringWithin($query, int $days)
    {
        return $query
            ->where('status', ContractStatus::Active->value)
            ->whereDate('end_date', '>=', now())
            ->whereDate('end_date', '<=', now()->addDays($days));
    }

    public function getRemainingBalanceAttribute(): float
    {
        $required = $this->paymentSchedules()->sum('total_amount');
        $paid = $this->payments()->where('status', 'registered')->sum('amount');

        return round(max($required - $paid, 0), 2);
    }
}
