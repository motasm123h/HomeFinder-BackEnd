<?php

namespace App\Models;

use App\Models\RealEstate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstate_properties extends Model
{
    
    protected $fillable = [
        'electricity_status', 'water_status', 'transportation_status',
        'water_well', 'solar_energy', 'garage', 'room_no',
        'direction', 'space_status', 'elevator', 'floor', 'garden_status',
        'attired', 'ownership_type', 'price', 'total_weight','real_estate_id'
    ];


    /**
     * Get the RealEstate that owns the RealEstate_properties
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function RealEstate(): BelongsTo
    {
        return $this->belongsTo(RealEstate::class);
    }
}
