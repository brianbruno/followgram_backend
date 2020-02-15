<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use App\UserExtract;
use App\Notifications\UserRegister;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed'
        ]);
      
        // adiciona ponto de referidos
        if (!empty($request->reffer_id)) {
            $allIps = User::where('ip', $request->reffer_id)->first();
          
            if (empty($allIps)) {
                $userReffer = User::where('id', $request->reffer_id)->first();

                if (!empty($userReffer)) {
                    if ($userReffer->ip !== $request->getClientIp()) {
                        $description = 'Usuário cadastrado através de você! Dê as boas vindas para: '.$request->name.'.';
                        $userReffer->addPoints(50, $description);  
                    }

                }
            }
            
        }
      
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        $user->save();
      
        $userNotify = array(
          'name' => $request->name,
          'email' => $request->email,
        );
      
        $user->notify(new UserRegister($userNotify));
      
        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);
    }
  
    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);
        $credentials = request(['email', 'password']);
        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }
  
    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
  
    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->ip = $request->getClientIp();
        $user->save();
      
        return response()->json($request->user());
    }
  
    public function extract(Request $request) {
        $user = $request->user();
      
        $data = $user->extract()->latest()->limit(30)->get();
      
        $retorno = array(
            'status' => true,
            'data'   => $data
        );
      
        return response()->json($retorno);

    }
}