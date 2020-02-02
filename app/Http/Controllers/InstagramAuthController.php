<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\UserInstagram;
use Illuminate\Support\Facades\Crypt;

class InstagramAuthController extends Controller
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
    
    public function addUser(Request $request) {
        $request->validate([
            'username' => 'required|string'
        ]);
      
        $user = Auth::user();
        $username = $request->username;
      
        $userInsta = UserInstagram::where('username', $username)->where('user_id', $user->id)->first();
        $confirmKey = Crypt::encryptString('FollowGram');
        
        if (empty($userInsta)) {
            $userInsta = new UserInstagram();
            $userInsta->user_id = $user->id;
            $userInsta->username = $username;
        }
      
        $userInsta->confirm_key = $confirmKey;
        $userInsta->save();
      
        return response()->json([
            'success' => true,
            'confirmKey' => $confirmKey
        ], 200);
      
        
    }
  
    public function confirm(Request $request) {
      
        $request->validate([
            'username' => 'required|string'
        ]);
      
        $sucesso = false;
      
        $user = Auth::user();
        $username = $request->username;
      
        $userInsta = UserInstagram::where('username', $username)->where('user_id', $user->id)->first();
        
        $instagram = new \InstagramScraper\Instagram();
      
        $medias = $instagram->getMedias($userInsta->username, 1);
        
        if (sizeof($medias) > 0) {
            $shortCode = $medias[0]['shortCode'];
          
            $comments = $instagram->getMediaCommentsByCode($shortCode);
            // tem comentarios
            if (sizeof($comments) > 0) {
                // pega o ultimo comentario
                $lastComment = $comments[sizeof($comments) - 1];
                // compara com a chave de confirmacao
                if ($lastComment->getText() == $userInsta->confirm_key) {
                    $userInsta->confirmed = true;
                    $userInsta->save();
                    $sucesso = true;
                }
            }
        }
      
        return response()->json([
            'success' => $sucesso
        ], 200);
      
        
    }
  
    public function test() {
        // If account is public you can query Instagram without auth
        $instagram = new \InstagramScraper\Instagram();

        $media = $instagram->getMediaLikesByCode('B74PBBvlY6l');
        dd($media);die;
    }
}
