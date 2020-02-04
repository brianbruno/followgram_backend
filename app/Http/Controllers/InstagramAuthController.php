<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\UserInstagram;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

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
        $confirmKey = Crypt::encryptString('FollowGram'.$username.$user->id);
        
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
      
        $retorno = array(
            'success' => false
        );
      
        $user = Auth::user();
        $username = $request->username;
      
        $userInsta = UserInstagram::where('username', $username)->where('user_id', $user->id)->first();
        
        $instagram = new \InstagramScraper\Instagram();
      
        $medias = $instagram->getMedias($userInsta->username, 1);
        
        if (sizeof($medias) > 0) {
            $shortCode = $medias[0]['shortCode'];
          
            $comments = $instagram->getMediaCommentsByCode($shortCode);
            sleep(5);
            // tem comentarios
            if (sizeof($comments) > 0) {
                // pega o ultimo comentario
                $lastComment = $comments[sizeof($comments) - 1];
                // compara com a chave de confirmacao
                if ($lastComment->getText() == $userInsta->confirm_key) {
                    $userInsta->confirmed = true;
                    $accountInsta = $instagram->getAccount($userInsta->username);   
                    $userInsta->profile_pic_url = $accountInsta->getProfilePicUrl();
                    $userInsta->external_url = $accountInsta->getExternalUrl();
                    $userInsta->full_name = $accountInsta->getFullName();
                    $userInsta->biography = $accountInsta->getBiography();                  
                    $userInsta->is_private = $accountInsta->isPrivate();
                    $userInsta->is_verified = $accountInsta->isVerified(); 
                    $userInsta->save();
                    $retorno['success'] = true;
                } else {
                    $retorno['message'] = "Ainda não conseguimos verificar sua conta! Confirme o código e tente novamente. Contas privadas não são aceitas.";
                }
            }
        }
      
        return response()->json($retorno, 200);
      
        
    }
  
    public function getAccounts() {
      
        $retorno = array(
          'success' => true,
          'data'    => []
        );
      
        try {
          
          $user = Auth::user();
          $accounts = $user->instagramAccounts()->where('confirmed', 1)->get();
          $retorno['data'] = $accounts;
          
        } catch(\Exception $e) {
            $retorno['success'] = false;
            $retorno['message'] = $e->getMessage();
        }
      
        return response()->json($retorno, 200);
    }
  
    public function getPosts(Request $request) {
      $request->validate([
            'username' => 'required|string'
        ]);
    
      $retorno = array(
          'success' => true,
          'data'    => []
        );
      
        try {
          
          $user = Auth::user();
          $instagram = new \InstagramScraper\Instagram();
          
          $minutes = 5;
          $medias = Cache::remember('getMediasUsername-'.$request->username, $minutes*60, function () use ($request, $instagram) {
              $retorno = $instagram->getMedias($request->username, 25);
              return $retorno;
          });
          
          $mediaArray = [];
          
          foreach ($medias as $media) {
              $mediaArray[] = array(
                  'link'    => $media->getLink(),
                  'imgUrl'  => $media->getImageThumbnailUrl(),
              );              
          }
          
          $retorno['data'] = $mediaArray;          
          
        } catch(\Exception $e) {
            $retorno['success'] = false;
            $retorno['message'] = $e->getMessage();
        }
      
        return response()->json($retorno, 200);
  }
  
}
