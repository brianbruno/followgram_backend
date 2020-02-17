<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\UserInstagram;
use App\UserRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Notifications\UserAccountAdd;
use Phpfastcache\Helper\Psr16Adapter;
use App\Notifications\ErrorLog;

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
      
        // $confirmKey = Crypt::encryptString('FollowGram'.$username.$user->id);
        $confirmKey = Str::random(20);
        
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
            'success' => false,
            'message' => ''
        );
      
        $user = Auth::user();
        $username = $request->username;
      
        try {            

            $userInsta = UserInstagram::where('username', $username)->where('user_id', $user->id)->first();
          
            $instagram = new \InstagramScraper\Instagram();
            $instagram = \InstagramScraper\Instagram::withCredentials('dicas.ig', 'marketing2020', new Psr16Adapter('Files'));
            $instagram->login();
          
            $accountInsta = $instagram->getAccount($userInsta->username);   
          
            if (empty($accountInsta)) {
                throw new \Exception('Essa conta não existe. Certifique-se de colocar seu nome de usuário do Instagram.');
            }
          
            $userInsta->confirmed = true;
            $userInsta->profile_pic_url = $accountInsta->getProfilePicUrl();
            $userInsta->external_url = $accountInsta->getExternalUrl();
            $userInsta->full_name = $accountInsta->getFullName();
            $userInsta->biography = $accountInsta->getBiography();                  
            $userInsta->is_private = $accountInsta->isPrivate();
            $userInsta->is_verified = $accountInsta->isVerified(); 
            $userInsta->save();
            $retorno['success'] = true;
            $retorno['message'] = "Confirmamos a sua conta!";

            $userNotify = array(
                'username' => $user->name,
                'ig' => $userInsta->username,
                'image' => $userInsta->profile_pic_url
            );

            $user->notify(new UserAccountAdd($userNotify));

            //$medias = $instagram->getMedias($userInsta->username, 1);
            
            /* if (sizeof($medias) > 0) {
                $shortCode = $medias[0]['shortCode'];

                $comments = $instagram->getMediaCommentsByCode($shortCode);
                sleep(3);

                // pega o cod de confirmacao
                $codConfirm = substr($userInsta->confirm_key, -8);

                // tem comentarios
                if (sizeof($comments) > 0) {
                    // pega o ultimo comentario
                    $lastComment = $comments[sizeof($comments) - 1];
                    //dd($lastComment);die;
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
                        $retorno['message'] = "Confirmamos a sua conta!";

                        $userNotify = array(
                            'username' => $user->name,
                            'ig' => $userInsta->username,
                            'image' => $userInsta->profile_pic_url
                        );

                        $user->notify(new UserAccountAdd($userNotify));
                    } else {
                        $retorno['message'] = "Ainda não conseguimos verificar sua conta! Confirme o código e tente novamente. Contas privadas não são aceitas.";
                    } 
                }
            } else {
                throw new \Exception('Essa conta não possui nenhum post ou é privada.');
            } */

        } catch (\Exception $e) {
            $retorno['success'] = false;
            $retorno['message'] = $e->getMessage();      
            $erro = $e->getMessage(). ' => '.$user->name;
            $data = array(
                'class'   => 'InstagramAuthController->confirm',
                'line'    => $e->getLine(),
                'message' => $erro
            );

            $notification = UserInstagram::where('user_id', 1)->first();
            $notification->notify(new ErrorLog($data));
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
          
            $data = array(
                'class'   => 'InstagramAuthController->getAccounts',
                'line'    => $e->getLine(),
                'message' => $e->getMessage()
            );

            $notification = UserInstagram::where('user_id', 1)->first();
            $notification->notify(new ErrorLog($data));
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
          $instagram = \InstagramScraper\Instagram::withCredentials('dicas.ig', 'marketing2020', new Psr16Adapter('Files'));
          $instagram->login();
          
          $minutes = 5;
          $medias = Cache::remember('getMediasUsername-'.$request->username, $minutes*60, function () use ($request, $instagram) {
              $retorno = $instagram->getMedias($request->username, 25);
              return $retorno;
          });
          
          $mediaArray = [];
          
          foreach ($medias as $media) {
              $requestAlreadyMade = UserRequest::where('post_url', $media->getLink())->first();
              if (empty($requestAlreadyMade)) {
                  $mediaArray[] = array(
                      'caption' => $media->getCaption(),
                      'link'    => $media->getLink(),
                      'imgUrl'  => $media->getImageThumbnailUrl(),
                  );    
              }                          
          }
          
          $retorno['data'] = $mediaArray;          
          
        } catch(\Exception $e) {
            $retorno['success'] = false;
            $retorno['message'] = $e->getMessage();
          
            $data = array(
                'class'   => 'InstagramAuthController->getPosts',
                'line'    => $e->getLine(),
                'message' => $e->getMessage()
            );

            $notification = UserInstagram::where('user_id', 1)->first();
            $notification->notify(new ErrorLog($data));
        }
      
        return response()->json($retorno, 200);
  }
  
}
