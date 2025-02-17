<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use App\UserInstagram;
use App\UserRewards;
use Carbon\Carbon;


class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $appends = ['new_followers', 'new_comments', 'new_likes', 'points', 'pending_points', 'is_vip', 'is_admin', 'insta_username_active', 'insta_picture_active'];

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
  
    public function rewards()
    {
        return $this->hasMany('App\UserRewards', 'user_id', 'id');
    }

    public function instagramAccounts()
    {
        return $this->hasMany('App\UserInstagram');
    }

    public function points()
    {
        return $this->hasMany('App\UserPoints');
    }

    public function extract()
    {
        return $this->hasMany('App\UserExtract', 'user_id', 'id');
    }

    public function userVips()
    {
        return $this->hasMany('App\UserVIP', 'user_id', 'id');
    }
  
    public function getIsAdminAttribute()
    {
        $isAdmin = false;

        if ($this->id == 1 or $this->id == 6) {
            $isAdmin = true;
        }

        return $isAdmin;

    }

    public function getIsVipAttribute()
    {
        $isVip = false;
        $vips = $this->userVips()->where('end_date', '>', DB::raw('NOW()'))->first();

        if (!empty($vips)) {
            $isVip = true;
        }

        return $isVip;

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
  
    public function getInstaPictureActiveAttribute()
    {
        $insta = UserInstagram::where('id', $this->insta_id_active)->first();
      
        if (empty($insta))
            return false;
        else {
          return $insta->profile_pic_url;
        }           
            
    }

    public function getInstaUsernameActiveAttribute()
    {
        $insta = UserInstagram::where('id', $this->insta_id_active)->first();
      
        if (empty($insta))
            return false;
        else {
          return $insta->username;
        }           
            
    }
  
    public function getNewLikesAttribute()
    {
        $instaAccounts = $this->instagramAccounts()->get();
        $confirmedLikes = 0;
        // pega os ultimos 7 dias
        $date = \Carbon\Carbon::today()->subDays(7);

        foreach($instaAccounts as $account) {
            $confirmedLikes += $account->instagramLikesReceived()->where('status', 'confirmed')
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

    public function addPoints($value, $description) {
        $points = $this->points()->first();

        if (empty($points)) {
            $points = new UserPoints();
            $points->user_id = $this->id;
            $points->points = $value;
        } else {
            $points->points = $points->points + $value;
        }

        $points->save();

        $extract = new UserExtract();
        $extract->user_id = $this->id;
        $extract->description = $description;
        $extract->type = 'positive';
        $extract->points = $value;
        $extract->save();
    }

    public function removePoints($value, $description) {
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

        $extract = new UserExtract();
        $extract->user_id = $this->id;
        $extract->description = $description;
        $extract->type = 'negative';
        $extract->points = $value;
        $extract->save();

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
  
    public function gerarReward() {
      
        $carbonHoje = Carbon::now();
      
        // Busca para ver se ja retirou o premio
        $reward = UserRewards::where('user_id', $this->id)
          ->whereDay('reward_date', $carbonHoje->day)
          ->first();
      
        if (!empty($reward)) {
            throw new \Exception('Você já retirou o prêmio de hoje. Volte amanhã.');
        } 
      
        // Caso não tenha retirado ainda
        
        $rewardOntem = UserRewards::where('user_id', $this->id)
          ->whereDay('reward_date', Carbon::now()->subDay())
          ->first();
      
        if (empty($rewardOntem)) {
            // Volta para primeiro dia
            $this->darReward(1);
        } else {
            // Está pelo menos no segundo dia
            $days = 2;

            // Verifica se fez o segundo dia
            $rewardAnteriores = UserRewards::where('user_id', $this->id)
              ->whereDay('reward_date', Carbon::now()->subDays($days))
              ->first();
          
            // Verifica se fez ate o setimo dia ou se já fez nos últimos 7 dias
            while (!empty($rewardAnteriores) and $days != 8) {
                $days++;
                  
                $rewardAnteriores = UserRewards::where('user_id', $this->id)
                  ->whereDay('reward_date', Carbon::now()->subDays($days))
                  ->first();
            }
          
            if ($days <= 7) {
                // Dar a recompensa de acordo com o dia
                $this->darReward($days);
            } else {
                throw new \Exception('Você já retirou o prêmio nos últimos 7 dias! Espere até amanhã para recomeçar.');
            }  
        }
      
        
    }
  
    private function darReward($day) {
        $premios = array(
            '1' => array('type' => 'points', 'value' => 15),
            '2' => array('type' => 'points', 'value' => 60),
            '3' => array('type' => 'points', 'value' => 80),
            '4' => array('type' => 'points', 'value' => 100),
            '5' => array('type' => 'points', 'value' => 150),
            '6' => array('type' => 'points', 'value' => 180),
            '7' => array('type' => 'vip', 'value' => 7)          
        );
      
        $premioDia = $premios[$day];
        
        if ($premioDia['type'] == 'points') {
            $newReward = new UserRewards();
            $newReward->user_id = $this->id;
            $newReward->day_sequence = $day;
            $newReward->reward = $premioDia['value']. ' pontos';
            $newReward->reward_date = date("Y-m-d H:i:s");
            $newReward->save();            
          
            $description = 'Recompensa Diária. Dia: '.$day;
            $this->addPoints($premioDia['value'], $description);
        } else if ($premioDia['type'] == 'vip') {
            $newReward = new UserRewards();
            $newReward->user_id = $this->id;
            $newReward->day_sequence = $day;
            $newReward->reward = $premioDia['value']. ' dias VIP';
            $newReward->reward_date = date("Y-m-d H:i:s");
            $newReward->save();            
        }
      
    }

    public function routeNotificationForSlack($notification)
    {
        return 'https://hooks.slack.com/services/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';
    }
}
