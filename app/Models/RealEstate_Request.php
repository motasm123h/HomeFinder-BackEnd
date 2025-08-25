<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstate_Request extends Model
{
    protected $table = 'real_estate_requests';

    protected $fillable = [
        'title', 'phone_number', 'user_id', 'username', 'description',
    ];

    /**
     * Get the user that owns the RealEstate_Request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
