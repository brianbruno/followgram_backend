<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use App\Notifications\PedidoAjuda;
use App\HelpItem;

class HelpController extends Controller
{
   
    public function addHelp(Request $request)
    {
        $request->validate([
            'textHelp' => 'required'
        ]);
      
        $result = array(
            'status' => true,
            'message' => 'Operação realizada com sucesso.'
        );
      
        try {
          
            $user = Auth::user();
          
            $contas = $user->instagramAccounts()->where('confirmed', 1)->get();
            $strContas = ''; 
            foreach ($contas as $conta) {
                $strContas = $strContas.$conta->username.', ';
            }
                  
            $dadosPedido = array(
                'nome'           => $user->name,
                'points'         => $user->points,
                'follows'        => $user->new_followers,
                'likes'          => $user->new_likes,
                'pending_points' => $user->pending_points,
                'texto'          => $request->textHelp,
                'contas'         => $strContas
            );

            $helpItem = new HelpItem();
          
            $helpItem->notify(new PedidoAjuda($dadosPedido));
        } catch (\Exception $e) {
            $result['status'] = false;
            $result['message'] = $e->getMessage();
            $result['stack'] = $e->getTraceAsString();
        }    
      
        return response()->json($result, 200);
    }
  
    
}