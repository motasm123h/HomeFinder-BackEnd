<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstate_View extends Model
{
    protected $table = 'real_estate_views';

    protected $fillable = ['counter', 'real_estate_id'];

    /**
     * Get the realEstate that owns the RealEstate_View
     */
    public function realEstate(): BelongsTo
    {
        return $this->belongsTo(RealEstate::class, 'real_estate_id');
    }
}
