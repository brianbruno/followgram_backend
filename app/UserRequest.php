<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRequest extends Model
{
  
    use SoftDeletes;
  
    protected $table = 'user_requests';

    public function targetUserInsta()
    {
        return $this->hasOne('App\UserInstagram', 'id', 'insta_target');
    }

}
