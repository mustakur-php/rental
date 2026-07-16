<?php

namespace App\Domains\Payment\Models;

use App\Enums\PaymentScheduleStatus;
use App\Domains\Contract\Models\Contract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'installment_no',
        'due_date',
        'base_amount',
        'vat_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'grace_period_days',
        'paid_at',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'date',
        'base_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'status' => PaymentScheduleStatus::class,
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeOverdue($query)
    {
        return $query
            ->whereIn('status', [
                PaymentScheduleStatus::Due->value,
                PaymentScheduleStatus::Partial->value,
                PaymentScheduleStatus::Overdue->value,
            ])
            ->whereDate('due_date', '<', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentScheduleStatus::Pending->value);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('remaining_amount', '>', 0);
    }

    public function refreshPaymentStatus(): void
    {
        $paidAmount = $this->payments()
            ->where('status', 'registered')
            ->sum('amount');

        $remainingAmount = max($this->total_amount - $paidAmount, 0);

        $status = match (true) {
            $remainingAmount <= 0 => 'paid',
            $paidAmount > 0 => 'partial',
            now()->toDateString() > $this->due_date->copy()->addDays($this->grace_period_days)->toDateString() => 'overdue',
            now()->toDateString() >= $this->due_date->toDateString() => 'due',
            default => 'pending',
        };

        $this->update([
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'paid_at' => $remainingAmount <= 0 ? now() : null,
            'status' => $status,
        ]);
    }
}
