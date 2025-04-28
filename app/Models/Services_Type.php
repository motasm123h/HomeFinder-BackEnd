<?php

namespace App\Models;

use App\Models\Services;
use Illuminate\Database\Eloquent\Model;

class Services_Type extends Model
{
    protected $fillable = ['type'];

    public function servicesInfo(){
        return $this->hasMany(Services::class);
    }

}
