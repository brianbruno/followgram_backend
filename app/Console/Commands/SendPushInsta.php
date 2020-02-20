<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UserPush;
use App\UserInstagram;
use Illuminate\Support\Facades\Cache;
use App\Notifications\ErrorLog;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class SendPushInsta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manda uma mensagem aleatória para o Insta de alguém.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
      
        try {
          
            $date = \Carbon\Carbon::today()->subDays(3);
          
            $user = DB::table('user_insta')
                ->select('user_insta.username', 'user_insta.user_id') 
                ->join('user_points', 'user_insta.user_id', '=', 'user_points.id') 
                ->where('user_insta.confirmed', 1)
                ->where('user_points.points', '<', 0)
                ->whereRaw('user_insta.user_id NOT IN (SELECT user_id FROM user_push)')->inRandomOrder()->first();    
          
            if (empty($user)) {
                throw new \Exception("Nenhum usuário para enviar notificação");
            }
          
          
            $text = 'Oi! Sou do GanheSeguidores. Você está sem pontos.. Entra lá pra vc ganhar mais e voltar a ganhar seguidores. GanheSeguidores. com';
            $url = 'http://orcl1.brian.place/enviardirect';
            $requestBody = array();
            $requestBody['username'] = $user->username;
            $requestBody['text'] = $text;
          
            $client = new Client();
                        
            $response = $client->post($url,  ['json'=>$requestBody]);

            $resposta = $response->getBody()->getContents();
            $resposta = json_decode($resposta);

            if ($resposta->status) {
                $push = new UserPush();
                $push->user_id = $user->user_id;
                $push->message = $text;
                $push->save();
            } else {
                throw new \Exception($resposta->message);
            }
        } catch (\Exception $e) {
            $not = UserInstagram::where('user_id', 1)->first();
          
            $data = array(
                'class'   => 'SendPushInsta',
                'line'    => $e->getLine(),
                'message' => $e->getMessage()
            );

            $not->notify(new ErrorLog($data));
        }
        
    }
}
