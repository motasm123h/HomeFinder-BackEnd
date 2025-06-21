<?php

namespace App\Models;

use App\Models\RealEstate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstate_images extends Model
{
    protected $fillable = ['name', 'real_estate_id', 'type'];


    /**
     * Get the RealEstate that owns the RealEstate_images
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function RealEstate(): BelongsTo
    {
        return $this->belongsTo(RealEstate::class, 'real_estate_id');
    }
}
