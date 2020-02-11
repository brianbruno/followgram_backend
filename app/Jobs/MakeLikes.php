<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Phpfastcache\Helper\Psr16Adapter;
use App\UserRequest;
use App\UserInstagram;
use App\UserLike;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use App\Notifications\BotInformation;
use App\User;

class MakeLikes implements ShouldQueue
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
    public function handle() {
      
        $defaultPassword = 'marketing2020';
        $cache = new Psr16Adapter('Files');
      
        $bots = DB::table('user_insta')->select('id', 'username')->where('user_id', 2)->where('confirmed', 1)->inRandomOrder()->limit(10)->get();

        foreach ($bots as $bot) {  
          
                $instagramUser = UserInstagram::where('id', $bot->id)->first();
                $questsMade = $instagramUser->getQuestsMade();

                $userRequests = DB::table('user_requests')
                    ->select('user_requests.post_url', 'user_requests.id', 'user_requests.insta_target', 'user_requests.points', 'user_points.points as user_points')
                    ->join('user_insta', 'user_requests.insta_target', '=', 'user_insta.id')   
                    ->join('user_points', 'user_insta.user_id', '=', 'user_points.id')   
                    ->where('user_points.points', '>', 0)
                    ->whereNotIn('user_requests.id', $questsMade)
                    ->where('user_requests.type', 'like')
                    ->where('user_requests.active', 1)
                    ->inRandomOrder()->limit(3)->get();
              
                echo "Bot: ". $bot->username ."\n";
                echo "Requests: ". sizeof($userRequests) ."\n";
                $requestsDone = 0;

                if (sizeof($userRequests) > 0) {
                  
                    try {

                      foreach ($userRequests as $userRequest) { 

                            $instagramUserRequesting = UserInstagram::where('id', $userRequest->insta_target)->first();

                            echo "Username: ". $instagramUserRequesting->username ."\n";
                        
                            // $media = $instagram->getMediaByUrl('https://www.instagram.com/p/B6d8xc6o6DS/');                          
                            $url = "http://api.ganheseguidores.com/like_post";    
                        
                            $requestBody = array();
                            $requestBody['username'] = $bot->username;
                            $requestBody['password'] = $defaultPassword;
                            $requestBody['media_link'] = $userRequest->post_url;
                        
                            $client = new Client();
                        
                            $response = $client->post($url,  ['json'=>$requestBody]);
                        
                            $resposta = $response->getBody()->getContents();
                            $resposta = json_decode($resposta);
                        
                            if ($resposta->status) {
                                $userLike = new UserLike();
                                $userLike->request_id = $userRequest->id;
                                $userLike->insta_target = $userRequest->insta_target;
                                $userLike->insta_liking = $instagramUser->id;
                                $userLike->status = 'pending';
                                $userLike->points = $userRequest->points;
                                $userLike->save();
                                sleep(rand(4, 9));
                              
                                echo "Publicação curtida com sucesso. \n";
                                
                                $requestsDone++;
                            } else {
                                echo "Erro ao curtir publicação. \n";
                            }                            

                      }
                    } catch (\Exception $e) {
                        echo "Ocorreu um erro na execução dessa task. Código: ".$userRequest->id."\n";
                        echo $e->getMessage();
                    } 
                  
                    $userNotify = array(
                        'botId' => $instagramUser->id,
                        'username' => $instagramUser->username,
                        'action' => 'like',
                        'made' => $requestsDone,
                        'notMade' => sizeof($userRequests)-$requestsDone,
                        'questsMade' => sizeof($questsMade) 
                    );
                    
                    $instagramUser->notify(new BotInformation($userNotify));
                }                    
            
        }
      
    }
}
