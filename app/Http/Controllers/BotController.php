<?php

namespace App\Http\Controllers;

use App\UserInstagram;

class BotController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }
  
    public function inativarBot($idBot) { 
      
        $userInsta = UserInstagram::where('id', $idBot)->first();
      
        $userInsta->confirmed = 0;
        $userInsta->save();
      
        return response()->json([
            'success' => true
        ], 200);
    }
  
}
