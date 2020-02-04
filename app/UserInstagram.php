<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserInstagram extends Model
{
  
    protected $appends = ['is_request_follow', 'is_request_like'];
  
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
  
    public function instagramFollowing()
    {
        return $this->hasMany('App\UserFollow', 'insta_following', 'id');
    }
  
    public function instagramLikes()
    {
        return $this->hasMany('App\UserLike', 'insta_liking', 'id');
    }
  
    public function instagramRequests()
    {
        return $this->hasMany('App\UserRequest', 'insta_target', 'id');
    }
  
    public function getIsRequestFollowAttribute()
    {
        $request = $this->instagramRequests->where('type', 'follow')->where('active', 1)->first();
      
        if (empty($request)) {
            return false;
        } else {
            return true;
        }
    }
  
    public function getIsRequestLikeAttribute()
    {
        $request = $this->instagramRequests->where('type', 'comment')->where('active', 1)->first();
      
        if (empty($request)) {
            return false;
        } else {
            return true;
        }
    }

}
