<?php

namespace App\Models;

use App\Models\Services_Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    protected $table = 'services'; 
    protected $fillable = ['title','description', 'services_type_id','user_id'];

    public function usersInfo(){
        return $this->belongsTo(User::class,'user_id');
    }
    
    public function servicesType(){
        return $this->belongsTo(Services_Type::class,'services_type_id');
    }

}
