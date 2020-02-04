<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserLike extends Model
{
  
    protected $table = 'user_like';

    public function targetUserInsta()
    {
        return $this->hasOne('App\UserInstagram', 'id', 'insta_target');
    }
  
    public function request()
    {
        return $this->hasOne('App\UserRequest', 'id', 'request_id');
    }

}
