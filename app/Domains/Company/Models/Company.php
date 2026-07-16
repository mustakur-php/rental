<?php

namespace App\Domains\Company\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Property\Models\Property;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'type',
        'code',
        'name',
        'commercial_registration',
        'phone',
        'email',
        'address',
        'iban',
        'bank_name',
        'status',
        'notes',
        'archived_at',
        'archived_reason',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function subsidiaries(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    // ─── Scopes ───────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
