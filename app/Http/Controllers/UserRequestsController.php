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
            $instaAccounts = [];  
            $filteredRequests = [];
          
            foreach($user->instagramAccounts()->get() as $account) {
                $instaAccounts[] = $account->id;
            }

            $requests = UserRequest::whereNotIn('insta_target', $instaAccounts)->with('targetUserInsta')->get();
          
            foreach ($requests as $userInstaRequest) {
                $targetUser = $userInstaRequest->targetUserInsta()->first();
                $userSystemTarget = $targetUser->user()->first();
                
                if ($userSystemTarget->points >= -15) {
                    $filteredRequests[] = $userInstaRequest;
                }
              
            }
            // $result['data'] = $requests->toArray();
            $result['data'] = $filteredRequests;
          
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
                
            } else if ($type == 'like') {
                $post_url = $request->post_url;
                
                if (empty($post_url)) {
                    throw new \Exception('Post para curtidas inválido.');
                }
              
                $requestItem = UserRequest::where('insta_target', $idInstaTarget)->where('type', $type)->where('post_url', $post_url)->first(); 
            }
          
            if (empty($requestItem)) {
                $requestItem = new UserRequest();                
            } 

            $requestItem->insta_target = $idInstaTarget;
            $requestItem->type = $type;
            $requestItem->points = $points;
            $requestItem->post_url = empty($post_url) ? null : $post_url;
            $requestItem->active = $activate == true ? 1 : 0;
            $requestItem->save();            
            
          
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }    
      
        return response()->json($result, 200);
    }
}
