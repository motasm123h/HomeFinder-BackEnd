<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPreferences extends Model
{
    protected $fillable = [
        'electricity_status',
        'user_id',
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
        'price',
        'total_weight'
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
