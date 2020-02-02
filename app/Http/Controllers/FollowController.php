<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\UserFollow;
use App\UserInstagram;
use App\UserRequest;
use Illuminate\Support\Facades\Crypt;

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
            'idRequest'   => 'required',
            'idUserInsta' => 'required'
        ]);
      
        $result = array(
            'success' => true,
            'message' => ''
        );
      
        try {
          
            $user = Auth::user();
            $idFollowTarget = $request->idUserInsta;
            $idInstaRequest = $request->idRequest;

            $connection = UserFollow::where('insta_target', $idFollowTarget)->where('insta_following', $user->id)->first();
            $questRequest = UserRequest::where('id', $idInstaRequest)->first();
            
            if (empty($questRequest)) {
                throw new \Exception('Esta oferta não existe.');
            }
          
            if ($questRequest->active == 0) {
                throw new \Exception('Esta oferta não está mais ativa.');
            }
           
          
            // verifica se já existe uma quest de seguir essa pessoa.
            if (empty($connection)) {
                
                $userInstaTarget = UserInstagram::where('id', $idFollowTarget)->first();
              
                if ($userInstaTarget->user_id == $user->id) {
                    throw new \Exception('Não é permitido seguir a si mesmo.');
                }
              
                $userFollow = new UserFollow();
                $userFollow->insta_target = $idFollowTarget;
                $userFollow->insta_following = $user->id;
                $userFollow->points = $questRequest->points;
                $userFollow->save();
              
                $result['message'] = 'Operação realizada com sucesso.';
            } else {
                throw new \Exception('Vínculo já existente. Não é possível seguir a mesma pessoa duas vezes.');
            }            
          
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }    
      
        return response()->json($result, 200);
      
        
    }
}
