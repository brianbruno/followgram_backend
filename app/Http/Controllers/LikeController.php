<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\UserLike;
use App\UserInstagram;
use App\UserRequest;
use Illuminate\Support\Facades\Crypt;
use App\Notifications\UserAction;

class LikeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function photolikeAdd(Request $request) {
        
        $request->validate([
            'idQuest' => 'required',
            'idInstaLiking' => 'required'
        ]);
      
        $result = array(
            'success' => true,
            'message' => ''
        );
      
        try {
          
            $user = Auth::user();
            $idQuest = $request->idQuest;
            $idInstaLiking = $request->idInstaLiking;
          
            $questRequest = UserRequest::where('id', $idQuest)->first();
            if (empty($questRequest)) {
                throw new \Exception('Esta oferta não existe.');
            }
          
            $idLikeTarget = $questRequest->insta_target;
          
            $connection = UserLike::where('insta_target', $idLikeTarget)->where('insta_liking', $idInstaLiking)->where('request_id', $questRequest->id)->first();
            $questRequest = UserRequest::where('id', $idQuest)->first();
            
          
            if ($questRequest->active == 0) {
                throw new \Exception('Esta oferta não está mais ativa.');
            }           
          
            $userInstaTarget = UserInstagram::where('id', $idLikeTarget)->first();
          
            // verifica se já existe uma quest de seguir essa pessoa.
            if (empty($connection)) {
              
                if ($userInstaTarget->user_id == $user->id) {
                    throw new \Exception('Não é permitido curtir suas próprias fotos.');
                }
              
                $userFollow = new UserLike();
                $userFollow->request_id = $questRequest->id;
                $userFollow->insta_target = $idLikeTarget;
                $userFollow->insta_liking = $idInstaLiking;
                $userFollow->status = 'pending';
                $userFollow->points = $questRequest->points;
                $userFollow->save();
              
                $result['message'] = 'Operação realizada com sucesso.';
              
                $userNotify = array(
                    'username' => $user->name,
                    'ig' => $userInstaTarget->username,
                    'action' => 'like'
                );

                $user->notify(new UserAction($userNotify));
            } else {
              
                // verifica se a quest foi cancelada
                if ($connection->status == 'canceled') {
                    $connection->points = $questRequest->points;
                    $connection->status = 'pending';
                    $connection->save();
                  
                    $result['message'] = 'Operação realizada com sucesso.';
                  
                    $userNotify = array(
                        'username' => $user->name,
                        'ig' => $userInstaTarget->username,
                        'action' => 'like'
                    );

                    $user->notify(new UserAction($userNotify));
                } else {
                    throw new \Exception('Você já realizou essa tarefa :(');  
                }
                
            }            
          
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $result['stack'] = $e->getTraceAsString();
        }    
      
        return response()->json($result, 200);
      
        
    }
}
