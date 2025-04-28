<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    protected $fillable = ['title','Descripition','user_id'];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
