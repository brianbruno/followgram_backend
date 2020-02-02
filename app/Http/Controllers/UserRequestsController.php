<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserRequest;
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
          
            foreach($user->instagramAccounts()->get() as $account) {
                $instaAccounts[] = $account->id;
            }

            $requests = UserRequest::whereNotIn('insta_target', $instaAccounts)->with('targetUserInsta')->get();
            $result['data'] = $requests->toArray();
          
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
            'points'        => 'required'
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
          
            foreach($user->instagramAccounts()->get() as $account) {
                $instaAccounts[] = $account->id;
            }
            
            $searched = UserRequest::where('insta_target', $idInstaTarget)->where('type', $type)->first(); 
          
            if (!empty($searched)) {
                throw new \Exception('Já existe um pedido com essas informações.');
            }
          
            $newRequest = new UserRequest();
            $newRequest->insta_target = $idInstaTarget;
            $newRequest->type = $type;
            $newRequest->points = $points;
            $newRequest->save();
            
          
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }    
      
        return response()->json($result, 200);
    }
}
