<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstate_properties extends Model
{
    protected $fillable = [
        'electricity_status',
        'water_status',
        'transportation_status',
        'water_well',
        'solar_energy',
        'garage',
        'room_no',
        'direction',
        'space_status',
        'elevator',
        'floor',
        'garden_status',
        'attired',
        'ownership_type',
        'total_weight',
        'real_estate_id',
    ];

    /**
     * Get the RealEstate that owns the RealEstate_properties
     */
    public function RealEstate(): BelongsTo
    {
        return $this->belongsTo(RealEstate::class);
    }
}
