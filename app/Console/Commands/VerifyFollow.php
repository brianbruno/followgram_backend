<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UserFollow;
use App\UserInstagram;
use Phpfastcache\Helper\Psr16Adapter;
use Illuminate\Support\Facades\Cache;

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
            $instagram = \InstagramScraper\Instagram::withCredentials('marketingfollowgram', 'marketing2020', new Psr16Adapter('Files'));
            $instagram->login();
            sleep(2);
            $this->info('Conectado.');

            $this->line('Verificações pendentes: '.sizeof($follows));

            foreach ($follows as $follow) {
                $followersAccount = [];

                $targetfollow = UserInstagram::where('id', $follow->insta_target)->first();
                $following = UserInstagram::where('id', $follow->insta_following)->first();
              
                try {
                  $minutes = 15;
                  $account = Cache::remember('getAccountUsername-'.$targetfollow->username, $minutes*60, function () use ($targetfollow, $instagram) {
                      $retorno = $instagram->getAccount($targetfollow->username);
                      sleep(5);
                      return $retorno;
                  });
                  
                  $followersAccount = $instagram->getFollowers($account->getId(), 1000, 100, true);

                  $isFollowing = false;

                  foreach($followersAccount as $followAccount) {
                      if($followAccount['username'] == $following->username) {
                          $isFollowing = true;

                          $follow->status = 'confirmed';
                          $follow->save();

                          // credita os pontos
                          $following->user()->first()->addPoints($follow->points);
                          // debita os pontos
                          $targetfollow->user()->first()->removePoints($follow->points);

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
                } catch (\InstagramScraper\Exception\InstagramException $e) {
                      $follow->status = 'canceled';
                      $follow->save();
                      // penaliza usuário responsavel por problemas em 3 pontos.
                      $targetfollow->user()->first()->removePoints(3);
                      $accountsInsta = $targetfollow->user()->first()->instagramAccounts()->get();
                  
                      foreach ($accountsInsta as $accountInsta) {
                          $requestsInsta = $accountInsta->instagramRequests()->get();      
                          foreach ($requestsInsta as $requestInsta) {
                              $requestInsta->active = 0;
                              $requestInsta->save();
                          }
                      }
                  
                  
                }
                
            }
        }
        
        $this->info('Verificações de seguidores finalizada.');
      
    }
}
