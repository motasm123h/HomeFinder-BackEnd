<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Search_Log extends Model
{
    protected $table = 'search_logs';
    protected $fillable = ['key','value','user_id'];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
