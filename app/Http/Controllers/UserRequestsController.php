<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserRequest;
use App\UserInstagram;
use Illuminate\Support\Facades\Auth;

class UserRequestsController extends Controller
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

    public function getResquests()
    {      
        $result = array(
            'success' => true,
            'message' => '',
            'data'    => []
        );
      
        try {
            
            $user = Auth::user();
            $instaAccountsFollowing = [];  
            $instaAccountsLiking = [];
            $questsMade = [];
            $filteredRequests = [];
          
            foreach($user->instagramAccounts()->get() as $account) {
                $instaAccounts[] = $account->id;
              
                $questsMade = array_merge($questsMade, $account->getQuestsMade());              
            }     
          
            /*$userRequests = DB::table('user_requests')
                    ->select('user_requests.post_url', 'user_requests.id', 'user_requests.insta_target', 'user_requests.points', 'user_points.points as user_points')
                    ->join('user_insta', 'user_requests.insta_target', '=', 'user_insta.id')   
                    ->join('user_points', 'user_insta.user_id', '=', 'user_points.id')   
                    ->where('user_points.points', '>', 0)
                    ->whereNotIn('user_requests.id', $questsMade)
                    ->whereNotIn('user_requests.insta_target', $instaAccounts)
                    ->where('user_requests.active', 1)
                    ->inRandomOrder()->limit(6)->get();*/

            $requests = UserRequest::whereNotIn('insta_target', $instaAccounts)
                ->whereNotIn('id', $questsMade)
                ->where('active', 1)
                ->with('targetUserInsta')->get();
          
            foreach ($requests as $userInstaRequest) {
                $targetUser = $userInstaRequest->targetUserInsta()->first();
                $userSystemTarget = $targetUser->user()->first();
                
                if ($userSystemTarget->points >= -15 && $targetUser->is_private == 0) {
                    $filteredRequests[] = $userInstaRequest;
                }
              
            }
            
            $result['data'] = $filteredRequests;
          
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }    
      
        return response()->json($result, 200);
    }
  
    public function deleteLikeRequest(Request $request)
    {      
        $result = array(
            'success' => true,
            'message' => '',
            'data'    => []
        );
      
        $request->validate([
            'idRequest' => 'required'
        ]);
      
        try {
            
            $user = Auth::user();
            
            $requestLike = UserRequest::where('id', $request->idRequest)->first();
            $requestLike->delete();
            
            $result['message'] = 'Operação realizada com sucesso.';
          
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }    
      
        return response()->json($result, 200);
    }
  
    public function addRequest(Request $request)
    {      
        $request->validate([
            'idInstaTarget' => 'required',
            'type'          => 'required',
            'points'        => 'required',
            'activate'      => 'required'
        ]);
      
        $result = array(
            'success' => true,
            'message' => 'Operação realizada com sucesso.',
            'data'    => []
        );
      
        try {
            
            $user = Auth::user();
            $instaAccounts = [];
          
            $idInstaTarget = $request->idInstaTarget;
            $type = $request->type;
            $points = $request->points;
            $activate = $request->activate;
          
            foreach($user->instagramAccounts()->get() as $account) {
                $instaAccounts[] = $account->id;
            }
          
            $accountInfo = UserInstagram::where('id', $idInstaTarget)->first();

            if ($accountInfo->is_private == 1) {
                throw new \Exception('Não é possível realizar esse procedimento para contas privadas. Você pode ser penalizado caso persista. Caso libere sua conta, aguarde até 1 hora.');
            }
          
            if ($type == 'follow') {
                  
                $requestItem = UserRequest::where('insta_target', $idInstaTarget)->where('type', $type)->first();            
              
                if (empty($requestItem)) {
                    $requestItem = new UserRequest();                
                } 

                $requestItem->insta_target = $idInstaTarget;
                $requestItem->type = $type;
                $requestItem->points = $points;
                $requestItem->post_url = empty($post_url) ? null : $post_url;
                $requestItem->active = $activate == true ? 1 : 0;
                $requestItem->save();   
              
                if ($activate == 0) {
                    $result['message'] = "Operação realizada com sucesso. Não se esqueça de ativar a promoção para ganhar seguidores.";
                }
                
            } else if ($type == 'like') {
                $post_url = $request->post_url;
                $post_img = $request->post_img;
                
                if (empty($post_url)) {
                    throw new \Exception('Post para curtidas inválido.');
                }
              
                $requestItem = UserRequest::where('insta_target', $idInstaTarget)->where('type', $type)->where('post_url', $post_url)->first(); 
              
                if (empty($requestItem)) {
                    $requestItem = new UserRequest();                
                } 
              
                if ($activate) {
                    $requestItem->insta_target = $idInstaTarget;
                    $requestItem->type = $type;
                    $requestItem->points = $points;
                    $requestItem->post_url = empty($post_url) ? null : $post_url;
                    $requestItem->post_img = empty($post_img) ? null : $post_img;
                    $requestItem->active = $activate == true ? 1 : 0;
                    $requestItem->save();   
                } else {
                    $requestsFollowInsta = $accountInfo->instagramRequests()->where('type', $type)->get();
                    foreach ($requestsFollowInsta as $itemRequest) {
                        $itemRequest->active = 0;
                        $itemRequest->save();
                    }
                  
                    $result['message'] = "Todas os pedidos de curtida foram cancelados.";
                }
      
               
            }
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }    
      
        return response()->json($result, 200);
    }
}
