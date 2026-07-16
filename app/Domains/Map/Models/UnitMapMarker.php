<?php

namespace App\Domains\Map\Models;

use App\Domains\Map\Models\PropertyMap;
use App\Domains\Unit\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitMapMarker extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_map_id',
        'unit_id',
        'label',
        'x_coordinate',
        'y_coordinate',
    ];

    protected $casts = [
        'x_coordinate' => 'decimal:4',
        'y_coordinate' => 'decimal:4',
    ];

    public function propertyMap(): BelongsTo
    {
        return $this->belongsTo(PropertyMap::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
