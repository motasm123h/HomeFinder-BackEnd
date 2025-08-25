<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Services_Type extends Model
{
    protected $table = 'services_types';

    protected $fillable = ['type'];

    public function servicesInfo()
    {
        return $this->hasMany(Services::class, 'services_type_id');
    }
}
