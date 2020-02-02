<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UserFollow;
use App\UserInstagram;
use Phpfastcache\Helper\Psr16Adapter;

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

                $account = $instagram->getAccount($targetfollow->username);   
                sleep(1);
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
                    $this->error($following->username . ' dont follow ' . $targetfollow->username);
                }
            }
        }
        
        $this->info('Verificações de seguidores finalizada.');
      
    }
}
