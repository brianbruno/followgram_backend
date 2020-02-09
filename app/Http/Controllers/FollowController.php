<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\UserFollow;
use App\UserInstagram;
use App\UserRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Notifications\UserAction;

class FollowController extends Controller
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
    
    public function addFollow(Request $request) {
        $request->validate([
            'idInstaFollowing' => 'required',
            'idQuest' => 'required'
        ]);
      
        $result = array(
            'success' => true,
            'message' => ''
        );
      
        try {
          
            $user = Auth::user();
            $idInstaFollowing = $request->idInstaFollowing;
            $idQuest = $request->idQuest;
          
            $questRequest = UserRequest::where('id', $idQuest)->first();
            //$questRequest = DB::table('user_requests')->select('insta_target', 'points', 'active')->where('id', $idQuest)->first();
            if (empty($questRequest)) {
                throw new \Exception('Esta oferta não existe.');
            }
            $idFollowTarget = $questRequest->insta_target;   

            $connection = UserFollow::where('insta_target', $idFollowTarget)->where('insta_following', $idInstaFollowing)->first();
          
            if ($questRequest->active == 0) {
                throw new \Exception('Esta oferta não está mais ativa.');
            }           
            
            $userInstaTarget = UserInstagram::where('id', $idFollowTarget)->first();
          
            // verifica se já existe uma quest de seguir essa pessoa.
            if (empty($connection)) {
              
                if ($userInstaTarget->user_id == $user->id) {
                    throw new \Exception('Não é permitido seguir a si mesmo.');
                }
              
                $userFollow = new UserFollow();
                $userFollow->insta_target = $idFollowTarget;
                $userFollow->insta_following = $idInstaFollowing;
                $userFollow->points = $questRequest->points;
                $userFollow->status = 'pending';
                $userFollow->save();
              
                $result['message'] = 'Operação realizada com sucesso.';
              
                $userNotify = array(
                    'username' => $user->name,
                    'ig' => $userInstaTarget->username,
                    'action' => 'follow'
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
                        'action' => 'follow'
                    );

                    $user->notify(new UserAction($userNotify));
                } else {
                    throw new \Exception('Você já realizou essa tarefa :(');  
                }
                
            }            
          
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }    
      
        return response()->json($result, 200);
      
        
    }
}
