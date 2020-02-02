<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserInstagram extends Model
{
  
    protected $table = 'user_insta';
  
    protected $hidden = [
        'confirm_key'
    ];
    
    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }
  
       public function instagramFollowers()
    {
        return $this->hasMany('App\UserFollow', 'insta_target', 'id');
    }

}
