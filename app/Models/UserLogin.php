<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLogin extends Model
{


    protected $fillable = [
        'user_id',
        'user_ip',
        'country',
        'country_code',
        'city',
        'latitude',
        'longitude',
        'browser',
        'os'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
