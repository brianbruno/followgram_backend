<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\UserInstagram;
use App\UserRequest;
use App\Settings;
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
      
        $retorno = array(
            'success' => true,
            'message' => 'Operação realizada com sucesso.'
        );
      
        $user = Auth::user();
        $username = $request->username;
        $settings = Settings::where('id', 1)->first();
      
        try {
            
            $instagram = new \InstagramScraper\Instagram();
            // $instagram = \InstagramScraper\Instagram::withCredentials($settings->bot_username, $settings->bot_password, new Psr16Adapter('Files'));
            $instagram = \InstagramScraper\Instagram::withCredentials('luizamelo.1', $settings->bot_password, new Psr16Adapter('Files'));
            $instagram->login();
            dd('aqui');die;          
          
            $accountInsta = null;
          
            $userExists = UserInstagram::where('username', $username)->where('confirmed', '1')->first();
            if (!empty($userExists)) {
                throw new \Exception('Esse Instagram já foi associado a uma conta. Caso isso seja um engano, entre em contato com o suporte através do menu "Ajuda"');
            }
            
          
            try {
                $accountInsta = $instagram->getAccount($username);     
            } catch (\Exception $e) {
                $retorno['success'] = false;
            }
          
            if (empty($accountInsta)) {
              throw new \Exception('Essa conta não existe. Certifique-se de colocar seu nome de usuário do Instagram.');
            }
          
            if ($accountInsta->isPrivate()) {
              throw new \Exception('Essa conta é privada. Deixe a conta pública para realizar esse procedimento.');
            }

            $userInsta = UserInstagram::where('username', $username)->where('user_id', $user->id)->first();

            if (empty($userInsta)) {
                $userInsta = new UserInstagram();
                $userInsta->user_id = $user->id;
                $userInsta->username = $username;
            }
          
            $userInsta->save();
          
        } catch (\Exception $e) {
            $retorno['success'] = false;
            $retorno['message'] = $e->getMessage().' '.$e->getLine();
        }
      
        return response()->json($retorno, 200);
      
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
        $settings = Settings::where('id', 1)->first();
      
        try {            

            $userInsta = UserInstagram::where('username', $username)->where('user_id', $user->id)->first();
          
            $instagram = new \InstagramScraper\Instagram();
            //$instagram = \InstagramScraper\Instagram::withCredentials($settings->bot_username, $settings->bot_password, new Psr16Adapter('Files'));
            //$instagram->login();
          
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
          
            // seta a conta cadastrada como ativa
            $user->insta_id_active = $userInsta->id;
            $user->save();

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
  
    public function confirm2(Request $request) {
      
        $request->validate([
            'username' => 'required|string'
        ]);
      
        $retorno = array(
            'success' => false,
            'message' => ''
        );
      
        $user = Auth::user();
        $username = $request->username;
        $settings = Settings::where('id', 1)->first();
      
        try {            

            $userInsta = UserInstagram::where('username', $username)->where('user_id', $user->id)->first();
          
            $instagram = new \InstagramScraper\Instagram();
            $instagram = \InstagramScraper\Instagram::withCredentials($settings->bot_username, $settings->bot_password, new Psr16Adapter('Files'));
            $instagram->login();
          
            $accountInsta = $instagram->getAccount($userInsta->username);   
          
            if (empty($accountInsta)) {
                throw new \Exception('Essa conta não existe. Certifique-se de colocar seu nome de usuário do Instagram.');
            }
          
            $minutes = 15;
          
            // Pega os últimos 20 seguidores de marketingfollowgram
            $followersAccount = $instagram->getFollowers('28978262580', 20, 20, true);

            $isFollowing = false;

            foreach($followersAccount as $followAccount) {
                if($followAccount['username'] == $username) {
                  $isFollowing = true;
                                                             

                  $userInsta->confirmed = true;
                  $userInsta->account_id = $accountInsta->getId();
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

                  // seta a conta cadastrada como ativa
                  $user->insta_id_active = $userInsta->id;
                  $user->save();

                  break;
                }
            }
          
            if (!$isFollowing) {
                throw new \Exception('Não foi possível verificar sua conta. Tente novamente em alguns instantes. Certifique-se de que está seguindo através da conta: '.$username);
            }

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
      
        $settings = Settings::where('id', 1)->first();
      
        try {
          
          $user = Auth::user();
          $instagram = new \InstagramScraper\Instagram();
          $instagram = \InstagramScraper\Instagram::withCredentials('luizamelo.1', $settings->bot_password, new Psr16Adapter('Files'));
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
