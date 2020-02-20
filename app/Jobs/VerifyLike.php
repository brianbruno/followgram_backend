<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\UserLike;
use App\UserRequest;
use App\UserInstagram;
use Phpfastcache\Helper\Psr16Adapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Notifications\ErrorLog;

class VerifyLike implements ShouldQueue
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
        $likes = UserLike::where('status', 'pending')->limit(15)->get();
        $cache = new Psr16Adapter('Files_2');

        if (sizeof($likes) > 0) {

            echo 'Conectando com o Instagram'.PHP_EOL;
            $instagram = \InstagramScraper\Instagram::withCredentials('marketingfollowgram', 'marketing2020', $cache);
            $instagram->login();
            sleep(2);
            echo 'Conectado.'.PHP_EOL;

            echo 'Verificações pendentes: '.sizeof($likes).PHP_EOL;

            foreach ($likes as $like) {
                $likesMedia = [];

                $targetLike = UserInstagram::where('id', $like->insta_target)->first();
                $liking = UserInstagram::where('id', $like->insta_liking)->first();

                try {

                    $minutes = 15;
                    $requestUrlItem = UserRequest::where('id', $like->request_id)->first();
                    $users_requests = DB::table('user_requests')->select('post_url')->where('id', $like->request_id)->first();
                    $postUrl = $users_requests->post_url;

                    $media = Cache::remember('getMediaByUrl-'.$postUrl, $minutes*60, function () use ($postUrl, $instagram) {
                        $retorno = $instagram->getMediaByUrl($postUrl);
                        sleep(5);
                        return $retorno;
                    });

                    $minutes = 2;

                    $likesPost = Cache::remember('getMediaLikesByCode-'.$media->getShortCode(), $minutes*60, function () use ($media, $instagram) {
                        $retorno = $instagram->getMediaLikesByCode($media->getShortCode());
                        sleep(2);
                        return $retorno;
                    });

                    $liked = false;

                    foreach ($likesPost as $likePost) {
                        if($likePost->getUsername() == $liking->username) {
                            $liked = true;

                            $like->status = 'confirmed';
                            $like->save();

                            $descriptionIn = 'Você curtiu a foto de '. $targetLike->username.'.';
                            $descriptionOut = $liking->username . ' curtiu sua foto ('.$targetLike->username.').';
                            // credita os pontos
                            $liking->user()->first()->addPoints($like->points, $descriptionIn);
                            // debita os pontos
                            $targetLike->user()->first()->removePoints($like->points, $descriptionOut);

                            break;
                        }

                    }

                    if ($liked) {
                        echo $liking->username . ' liked ' . $targetLike->username.PHP_EOL;
                    } else {
                        $like->status = 'canceled';
                        $like->save();
                        echo $liking->username . ' dont liked ' . $targetLike->username.PHP_EOL;
                    }

                } catch (\InstagramScraper\Exception\InstagramException $e) {
                    // $like->status = 'canceled';
                    // $like->save();

                    $data = array(
                        'class'   => 'VerifyLike->InstagramException',
                        'line'    => $e->getLine(),
                        'message' => $e->getMessage()
                    );

                    $targetLike->notify(new ErrorLog($data));

                } catch (\Exception $e) {
                    // $like->status = 'canceled';
                    // $like->save();

                    $data = array(
                        'class'   => 'VerifyLike->Exception',
                        'line'    => $e->getLine(),
                        'message' => $e->getMessage()
                    );

                    $targetLike->notify(new ErrorLog($data));
                }

            }
        }

        echo 'Verificações de likes finalizada.'.PHP_EOL;
    }
}
