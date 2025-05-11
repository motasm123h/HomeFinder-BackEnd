<?php

namespace App\Models;

use App\Models\RealEstate_images;
use App\Models\RealEstate_Location;
use App\Models\RealEstate_properties;
use App\Models\RealEstate_View;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RealEstate extends Model
{
    protected $fillable = [
        'latitude', 'longitude', 'status', 'type',
        'price', 'hidden',
        'description', 'total_weight', 'kind',
        'user_id', 'real_estate_location_id'
    ];

    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RealEstate_images::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(RealEstate_Location::class, 'real_estate_location_id');
    }

    public function properties(): HasOne
    {
        return $this->hasOne(RealEstate_properties::class);
    }

    public function view(): HasMany
    {
        return $this->hasMany(RealEstate_View::class, 'real_estate_id');
    }

}
