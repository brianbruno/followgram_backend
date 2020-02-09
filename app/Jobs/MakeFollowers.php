<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\UserFollow;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use App\UserInstagram;

class MakeFollowers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $defaultPassword = 'marketing2020';
      
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
                    ->where('user_requests.type', 'follow')
                    ->where('user_requests.active', 1)
                    ->inRandomOrder()->limit(3)->get();
              
                echo "Requests: ". sizeof($userRequests) ."\n";

                if (sizeof($userRequests) > 0) {
                    
                    try {

                      foreach ($userRequests as $userRequest) { 

                            $instagramUserRequesting = UserInstagram::where('id', $userRequest->insta_target)->first();

                            echo "Username: ". $instagramUserRequesting->username ."\n";

                            $minutes = 15;

                            $url = "http://api.ganheseguidores.com/follow_user";
   
                            $requestBody = array();
                            $requestBody['username'] = "Claudio.passin";
                            $requestBody['password'] = $defaultPassword;
                            $requestBody['user_insta_target'] = "marketingfollowgram";
                        
                            $client = new Client();
                        
                            $response = $client->post($url,  ['json'=>$requestBody]);
                            //$response = $request->send(); 
                        
                            $resposta = $response->getBody()->getContents();
                        
                            dd($resposta);die;

                        

                            $userFollow = new UserFollow();
                            $userFollow->insta_target = $userRequest->insta_target;
                            $userFollow->insta_following = $instagramUser->id;
                            $userFollow->points = $userRequest->points;
                            $userFollow->status = 'pending';
                            $userFollow->save();
                            
                            sleep(rand(4, 9));

                      }
                    } catch (\Exception $e) {
                        echo "Ocorreu um erro na execução dessa task. Código: ".$userRequest->id."\n";
                        echo $e->getMessage()."\n";
                    } 
                }
    }
    }
}
