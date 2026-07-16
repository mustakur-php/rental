<?php

namespace App\Domains\Map\Models;

use App\Domains\Attachment\Models\Attachment;
use App\Domains\Property\Models\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PropertyMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'image_path',
        'map_type',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function markers(): HasMany
    {
        return $this->hasMany(UnitMapMarker::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
