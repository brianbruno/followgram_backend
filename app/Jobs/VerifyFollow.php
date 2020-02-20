<?php

namespace App\Jobs;

use App\Notifications\ErrorLog;
use App\UserFollow;
use App\UserInstagram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Phpfastcache\Helper\Psr16Adapter;

class VerifyFollow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $date = new \DateTime;
        $date->modify('-10 minutes');
        $formatted_date = $date->format('Y-m-d H:i:s');
        // 'updated_at', '>=', $formatted_date
        $follows = UserFollow::where('status', 'pending')->get();
        $cache = new Psr16Adapter('Files_1');

        if (sizeof($follows) > 0) {

            echo 'Conectando com o Instagram'.PHP_EOL;
            $instagram = \InstagramScraper\Instagram::withCredentials('marketingfollowgram', 'marketing2020', $cache);
            $instagram->login();
            sleep(2);
            echo 'Conectado.'.PHP_EOL;

            echo 'Verificações pendentes: '.sizeof($follows).PHP_EOL;

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

                            $descriptionIn = 'Você seguiu '. $targetfollow->username.'.';
                            $descriptionOut = $following->username . ' seguiu você ('. $targetfollow->username.').';
                            // credita os pontos
                            $following->user()->first()->addPoints($follow->points, $descriptionIn);
                            // debita os pontos
                            $targetfollow->user()->first()->removePoints($follow->points, $descriptionOut);

                            break;
                        }
                    }

                    if ($isFollowing) {
                        echo $following->username . ' follows ' . $targetfollow->username.PHP_EOL;
                    } else {
                        $follow->status = 'canceled';
                        $follow->save();
                        echo $following->username . ' dont follow ' . $targetfollow->username.PHP_EOL;
                    }
                } catch (\Exception $e) {
                    // $follow->status = 'canceled';
                    // $follow->save();

                    $data = array(
                        'class'   => 'VerifyFollow',
                        'line'    => $e->getLine(),
                        'message' => $e->getMessage()
                    );

                    $targetfollow->notify(new ErrorLog($data));
                }

            }
        }

        echo 'Verificações de seguidores finalizada.'.PHP_EOL;
    }
}
