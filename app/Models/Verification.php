<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    protected $fillable = [
        'national_no',
        'identity_no',
        'identity_image',
        'activation',
        'user_id',
        'contract_image',
    ];

    public function usersInfo()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
