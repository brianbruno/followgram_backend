<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Carbon\Carbon;


class BusinessIntelligenceController extends Controller {
    
    public function atividades(Request $request) {
        $csv = '';
      
        try {
          
            $todosUsuarios = User::all();
          
            foreach($todosUsuarios as $usuario) {
                
                $contasInsta = $usuario->instagramAccounts()->where('confirmed', 1)->get();
              
                foreach ($contasInsta as $contaInsta) {
                    $follows = $contaInsta->instagramFollowing()->where('status', 'confirmed')->get();
                    $likes = $contaInsta->instagramLikes('status', 'confirmed')->get();
                  
                    foreach($follows as $follow) {
                        $csv .= $contaInsta->id.','.$follow->points.','.Carbon::parse($follow->created_at)->timestamp.PHP_EOL;
                    }
                  
                    foreach($likes as $like) {
                        $csv .= $contaInsta->id.','.$like->points.','.Carbon::parse($like->created_at)->timestamp.PHP_EOL;
                    }
                  
                }
                
            }
            
            
          
        } catch (\Exception $e) {
            $csv = 'Erro. '.$e->getMessage();
        }    
      
        return response($csv)->header('Content-Type', 'text/plain');
    }
  
}