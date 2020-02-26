<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UserFollow;
use App\UserInstagram;
use Phpfastcache\Helper\Psr16Adapter;
use Illuminate\Support\Facades\Cache;
use App\Notifications\ErrorLog;
use App\UserRequest;

class VerifyFollow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:follow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica se a quest de follow foi concluída.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $date = new \DateTime;
        $date->modify('-10 minutes');
        $formatted_date = $date->format('Y-m-d H:i:s');
        // 'updated_at', '>=', $formatted_date
        $follows = UserFollow::where('status', 'pending')->get();

        if (sizeof($follows) > 0) {

            $this->line('Conectando com o Instagram');
            $instagram = \InstagramScraper\Instagram::withCredentials('pedro_lacerda8', 'marketing2020', new Psr16Adapter('Files'));
            $instagram->login();
            sleep(2);
            $this->info('Conectado.');

            $this->line('Verificações pendentes: '.sizeof($follows));
  
            for ($i = 0; $i < 25; $i++) {
              
                $follow = UserFollow::where('status', 'pending')->first();   
                
                if (empty($follow)) 
                    break;

                $followersAccount = [];

                $targetfollow = UserInstagram::where('id', $follow->insta_target)->first();
                $following = UserInstagram::where('id', $follow->insta_following)->first();

                try {
                  $minutes = 15;
                  $account = Cache::remember('getAccountUsername-'.$following->username, $minutes*60, function () use ($following, $instagram) {
                      $retorno = $instagram->getAccount($following->username);
                      sleep(5);
                      return $retorno;
                  });
                  
                  $minutes = 2;     
                  $this->line('Conta Seguindo: '.$following->username);
                  $this->line('Conta Target: '.$targetfollow->username);
                  
                  /*$followersAccount = Cache::remember('getAccountFollowers-'.$targetfollow->username, $minutes*60, function () use ($account, $instagram) {
                      $followersAccount = $instagram->getFollowers($account->getId(), 1000, 100, true);
                      sleep(5);
                      return $followersAccount;
                  });*/
                  // 
                  
                  $followersAccount = Cache::remember('getAccountFollowing-'.$following->username, $minutes*60, function () use ($account, $instagram) {
                      $followersAccount = $instagram->getFollowing($account->getId(), 1000, 100, true);
                      sleep(5);
                      return $followersAccount;
                  });

                  $isFollowing = false;

                  foreach($followersAccount as $followAccount) {
                      if($followAccount['username'] == $targetfollow->username) {
                          $isFollowing = true;

                          $follow->status = 'confirmed';
                          $follow->save();

                          $descriptionIn = 'Você seguiu '. $targetfollow->username.'.';
                          $descriptionOut = $following->username . ' seguiu você ('. $targetfollow->username.').';
                          // credita os pontos
                          $following->user()->first()->addPoints($follow->points, $descriptionIn);

                          if ($following->user()->first()->is_vip) {
                              $descriptionIn = 'Bônus VIP.';
                              $following->user()->first()->addPoints(3, $descriptionIn);
                          }

                          // debita os pontos
                          $targetfollow->user()->first()->removePoints($follow->points, $descriptionOut);

                          break;
                      }
                  }

                  if ($isFollowing) {
                      $this->info($following->username . ' follows ' . $targetfollow->username);
                  } else {
                      $follow->status = 'canceled';
                      $follow->save();
                      $this->error($following->username . ' dont follow ' . $targetfollow->username);
                  }
                } catch (\Exception $e) {
                      if (strpos($e->getMessage(), 'Failed to get followers') !== false) {
                          // $userRequest = UserRequest::where('type', 'follow')->where('insta_target', $follow->insta_target)->first();
                          // $userRequest->active = 0;
                          // $userRequest->save();
                          $following->confirmed = 0;
                          $following->save();

                          $follow->status = 'canceled';
                          $follow->save();

                          $data = array(
                              'class'   => 'VerifyFollow',
                              'line'    => $e->getLine(),
                              'message' => $e->getMessage().'. Conta privada foi desabilitada. Seguindo: '.$following->username
                          );

                          $targetfollow->notify(new ErrorLog($data));

                      } else if(strpos($e->getMessage(), 'Account with given username') !== false) {
                          $userRequest = UserRequest::where('type', 'follow')->where('insta_target', $follow->insta_target)->first();
                          $userRequest->active = 0;
                          $userRequest->save();

                          $follow->status = 'canceled';
                          $follow->save();

                          $targetfollow->confirmed = 0;
                          $targetfollow->save();

                          $data = array(
                              'class'   => 'VerifyFollow',
                              'line'    => $e->getLine(),
                              'message' => $e->getMessage().'. A quest e a conta foram desabilitadas. Target: '.$targetfollow->username
                          );

                          $targetfollow->notify(new ErrorLog($data));

                      } else if(strpos($e->getMessage(), 'rate limited') !== false){
                            $data = array(
                                'class'   => 'VerifyFollow',
                                'line'    => $e->getLine(),
                                'message' => 'Blocked by Instagram. O sistema irá aguardar 5 minutos para tentar processar novamente.'
                            );

                            $targetfollow->notify(new ErrorLog($data));
                        
                            break;
                      } else {
                          $data = array(
                              'class'   => 'VerifyFollow',
                              'line'    => $e->getLine(),
                              'message' => $e->getMessage()
                          );

                          $targetfollow->notify(new ErrorLog($data));
                      }

                }

            }
        }

        $this->info('Verificações de seguidores finalizada.');

    }
}
