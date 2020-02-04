<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;
  
    protected $appends = ['new_followers', 'new_comments', 'new_likes', 'points', 'pending_points'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
  
    public function instagramAccounts()
    {
        return $this->hasMany('App\UserInstagram');
    }
  
    public function points()
    {
        return $this->hasMany('App\UserPoints');
    }
  
    public function getNewFollowersAttribute()
    {
        $instaAccounts = $this->instagramAccounts()->get();
        $confirmedFollowers = 0;
        // pega os ultimos 7 dias
        $date = \Carbon\Carbon::today()->subDays(7);

        foreach($instaAccounts as $account) {
            $confirmedFollowers += $account->instagramFollowers()->where('status', 'confirmed')
                      ->where('created_at', '>=', $date)->count();
        }
      
        return $confirmedFollowers;
    }
  
    public function getNewCommentsAttribute()
    {
        return 0;
    }
  
    public function getNewLikesAttribute()
    {
        $instaAccounts = $this->instagramAccounts()->get();
        $confirmedLikes = 0;
        // pega os ultimos 7 dias
        $date = \Carbon\Carbon::today()->subDays(7);

        foreach($instaAccounts as $account) {
            $confirmedLikes += $account->instagramLikes()->where('status', 'confirmed')
                      ->where('created_at', '>=', $date)->count();
        }
      
        return $confirmedLikes;
    }
  
    public function getPendingPointsAttribute()
    {
        $instaAccounts = $this->instagramAccounts()->get();
        $pending = 0;

        foreach($instaAccounts as $account) {
            $followers = $account->instagramFollowing()->where('status', 'pending')->get();
            foreach ($followers as $follow) {
                $pending = $pending + $follow->points; 
            }
          
            $likes = $account->instagramLikes()->where('status', 'pending')->get();
            foreach ($likes as $like) {
                $pending = $pending + $like->points; 
            }
        }
      
        return $pending;      
      
    }
  
    public function addPoints($value) {
        $points = $this->points()->first();
  
        if (empty($points)) {
            $points = new UserPoints();
            $points->user_id = $this->id;
            $points->points = $value;
        } else {
            $points->points = $points->points + $value;
        }
      
        $points->save();
    }
  
    public function removePoints($value) {
        $result = false;
      
        $points = $this->points()->first();
  
        if (empty($points)) {
            $points = new UserPoints();
            $points->user_id = $this->id;
            $points->points = 0 - $value;
        } else {
            $points->points = $points->points - $value;  
        }
        
        $points->save(); 

        $result = true;
      
        return $result;
    }
  
    public function getPointsAttribute()
    {
        $points = $this->points()->first();
        $total = 0;
        if (!empty($points)) {
            $total = $points->points;
        }
      
        return $total;
      
    }
}
