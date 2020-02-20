<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UserLike;
use App\UserRequest;
use App\UserInstagram;
use Phpfastcache\Helper\Psr16Adapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Notifications\ErrorLog;

class VerifyLike extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:like';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica se a quest de like foi concluída.';

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
        $likes = UserLike::where('status', 'pending')->limit(10)->get();
      
        if (sizeof($likes) > 0) {

            $this->line('Conectando com o Instagram');
            $instagram = \InstagramScraper\Instagram::withCredentials('marketingfollowgram', 'marketing2020', new Psr16Adapter('Files'));
            $instagram->login();
            sleep(2);
            $this->info('Conectado.');

            $this->line('Verificações pendentes: '.sizeof($likes));

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
                  
                  $this->line('Post URL: '.$postUrl);
                  
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
                      $this->info($liking->username . ' liked ' . $targetLike->username);
                  } else {
                      $like->status = 'canceled';
                      $like->save();
                      $this->error($liking->username . ' dont liked ' . $targetLike->username);
                  }
                  
                } catch (\InstagramScraper\Exception\InstagramException $e) {
                     $data = array(
                        'class'   => 'VerifyLike->InstagramException',
                        'line'    => $e->getLine(),
                        'message' => $e->getMessage()
                    );
                  
                    $targetLike->notify(new ErrorLog($data));
                  
                } catch (\Exception $e) {  
                  
                    if ($e->getMessage() == 'Media with given code does not exist or account is private.') {
                      
                        echo 'Media não existe.'.PHP_EOL;
                      
                        $requestUrlItem = UserRequest::where('id', $like->request_id)->first();
                        $requestUrlItem->active = 0;
                        $requestUrlItem->save();
                        $like->status = 'canceled';
                        $like->save();
                        
                        $data = array(
                            'class'   => 'VerifyLike->Exception',
                            'line'    => $e->getLine(),
                            'message' => 'Media inexistente. Quest foi desativada. Target: '.$targetLike->username
                        );

                        $targetLike->notify(new ErrorLog($data));
                    } else {
                        $data = array(
                            'class'   => 'VerifyLike->Exception',
                            'line'    => $e->getLine(),
                            'message' => $e->getMessage()
                        );

                        $targetLike->notify(new ErrorLog($data));
                    }
                }
                
            }
        }
        
        $this->info('Verificações de seguidores finalizada.');
      
    }
}
