<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
  
    protected $table = 'user_follow';

    public function targetUserInsta()
    {
        return $this->hasOne('App\UserInstagram', 'id', 'insta_target');
    }

}
