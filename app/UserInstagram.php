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
  
    public function instagramLikesReceived()
    {
        return $this->hasMany('App\UserLike', 'insta_target', 'id');
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
        $request = $this->instagramRequests->where('type', 'like')->where('active', 1)->first();
      
        if (empty($request)) {
            return false;
        } else {
            return true;
        }
    }
  
    public function getQuestsMade() {
      
        $questsMade = [];

        foreach($this->instagramLikes()->get() as $accountLike) {
            if ($accountLike->status == 'confirmed' or $accountLike->status == 'pending') {
                $questsMade[] = $accountLike->request_id;  
            }                    
        }

        foreach($this->instagramFollowing()->get() as $accountFollow) {
            if ($accountFollow->status == 'confirmed' or $accountFollow->status == 'pending') {
                $followQuest = UserRequest::where('type', 'follow')->where('insta_target', $accountFollow->insta_target)->first();
                if (!empty($followQuest)) {
                    $questsMade[] = $followQuest->id;
                }
            }                    
        }
      
        return $questsMade;
    }

}
