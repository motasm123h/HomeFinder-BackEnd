<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RealEstate_Location extends Model
{
    protected $table = 'real_estate_locations';

    protected $fillable = ['city', 'district'];

    protected $primaryKey = 'id';

    /**
     * Get all of the realEstate for the RealEstate_Location
     */
    public function realEstate(): HasMany
    {
        return $this->hasMany(RealEstate::class, 'real_estate_location_id');
    }
}
