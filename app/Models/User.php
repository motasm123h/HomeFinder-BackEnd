<?php

namespace App\Models;

use App\Models\Address;
use App\Models\Contact;
use App\Models\RealEstate;
use App\Models\RealEstate_Request;
use App\Models\Search_Log;
use App\Models\Services;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'username',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function contact()
    {
        return $this->hasMany(Contact::class, 'user_id');
    }

    public function address()
    {
        return $this->hasOne(Address::class, 'user_id');
    }
    public function verification()
    {
        return $this->hasOne(Verification::class, 'user_id');
    }

    public function realEstate()
    {
        return $this->hasMany(RealEstate::class);
    }
    public function service()
    {
        return $this->hasMany(Services::class, 'user_id');
    }

    public function searchLog()
    {
        return $this->hasMany(Search_Log::class);
    }

    public function realEstateRequests(): HasMany
    {
        return $this->hasMany(RealEstate_Request::class, 'user_id');
    }

    public function sentRealEstateRequests(): HasMany
    {
        return $this->hasMany(RealEstate_Request::class, 'user_id');
    }

    public function getTokenAttribute()
    {
        return $this->createToken('secret')->plainTextToken;
    }
    // public function reviews()
    // {
    //     return $this->hasMany(Reviews::class);
    // }
}
