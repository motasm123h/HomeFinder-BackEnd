<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPreferences extends Model
{
    protected $fillable = [
        'electricity_status', 'water_status', 'transportation_status',
        'water_well', 'solar_energy', 'garage', 'room_no',
        'direction', 'space_status', 'elevator', 'floor', 'garden_status',
        'attired', 'ownership_type', 'price', 'total_weight'
    ];
}
