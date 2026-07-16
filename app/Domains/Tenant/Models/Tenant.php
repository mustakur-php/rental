<?php

namespace App\Domains\Tenant\Models;

use App\Traits\LogsModelActivity;
use App\Domains\Attachment\Models\Attachment;
use App\Domains\Contract\Models\Contract;
use App\Domains\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Tenant extends Model
{
    use HasFactory, LogsModelActivity;

    protected string $activityLogName = 'tenants';

    protected $fillable = [
        'code',
        'type',
        'name',
        'mobile',
        'email',
        'address',
        'notes',
        'status',
        'national_id',
        'nationality',
        'birth_date',
        'company_name',
        'commercial_registration',
        'contact_person_name',
        'contact_person_mobile',
        'archived_at',
        'archived_reason',
        'archived_notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'archived_at' => 'datetime',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContracts(): HasMany
    {
        return $this->hasMany(Contract::class)->notArchived()->where('status', 'active');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeIndividuals($query)
    {
        return $query->where('type', 'individual');
    }

    public function scopeCompanies($query)
    {
        return $query->where('type', 'company');
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
}
