<?php

namespace App\Models;

use App\Models\Services_Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    protected $fillable = ['title','description', 'service_type_id','user_id'];


    public function usersInfo(){
        return $this->belongsTo(User::class);
    }
    
    public function servicesType(){
        return $this->belongsTo(Services_Type::class);
    }

}
