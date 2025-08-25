<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Search_Log extends Model
{
    protected $table = 'search_logs';

    protected $fillable = ['key', 'value', 'user_id'];
}
