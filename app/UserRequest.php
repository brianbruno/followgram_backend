<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
  
    protected $table = 'user_requests';

    public function targetUserInsta()
    {
        return $this->hasOne('App\UserInstagram', 'id', 'insta_target');
    }

}
