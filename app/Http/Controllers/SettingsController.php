<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Settings;
use Illuminate\Support\Facades\Crypt;
use App\Notifications\UserAction;

class SettingsController extends Controller
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
    
    public function getSettings(Request $request) {
      
        $result = array(
            'success' => true,
            'message' => 'Operação realizada com sucesso',
            'data' => []
        );
      
        try {
          
            $user = Auth::user();
          
            if ($user->is_admin) {
                $settings = Settings::where('id', 1)->first();
              
                if (empty($settings)) {
                    $settings = new Settings();
                    $settings->id = 1;
                    $settings->save();
                }
              
                $result['data'] = $settings;
                
            } else {
                throw new \Exception('Você não é um ADM.');
            }   
          
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $result['stack'] = $e->getTraceAsString();
        }    
      
        return response()->json($result, 200);
      
    }
  
    public function saveSettings(Request $request) {
      
        $result = array(
            'success' => true,
            'message' => 'Operação realizada com sucesso',
            'data' => []
        );
      
        try {
          
            $user = Auth::user();
            $settingsRequest = $request->settings;
          
            if ($user->is_admin) {
                $settings = Settings::where('id', 1)->first();
              
                if (empty($settings)) {
                    $settings = new Settings();
                    $settings->id = 1;
                    $settings->save();
                }
              
                $settings->bot_username = $settingsRequest['bot_username'];
                $settings->bot_password = $settingsRequest['bot_password'];
                $settings->site_url = $settingsRequest['site_url'];
                $settings->save();
                
            } else {
                throw new \Exception('Você não é um ADM.');
            }   
          
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $result['stack'] = $e->getTraceAsString();
        }    
      
        return response()->json($result, 200);
      
    }
}
