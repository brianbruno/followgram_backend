<?php

namespace App\Http\Controllers;

use App\UserVIP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\UserRewards;
use App\UserInstagram;
use App\Notifications\ErrorLog;
use Illuminate\Support\Facades\DB;

class UserRewardsController extends Controller
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
  
    public function getDayReward(Request $request) {

        $result = array(
            'success' => true,
            'message' => 'Operação realizada com sucesso.',
            'day' => 1,
            'collected' => false
        );

        try {

            $user = Auth::user();
            $days = 0;

          
            $rewardAnteriores = UserRewards::where('user_id', $user->id)
              ->whereDay('reward_date', Carbon::now()->subDays($days))
              ->first();          
            
            // Verifica se fez ate o setimo dia ou se já fez nos últimos 7 dias
            while (!empty($rewardAnteriores)) {
                $days++;

                $rewardAnteriores = UserRewards::where('user_id', $user->id)
                  ->whereDay('reward_date', Carbon::now()->subDays($days))
                  ->first();
            }
            
            if ($days == 0 ) {
              $result['day'] = 1;
            } else {
              $result['day'] = $days + 1;  
            }
          
            $carbonHoje = Carbon::now();

            // Busca para ver se ja retirou o premio
            $reward = UserRewards::where('user_id', $user->id)
              ->whereDay('reward_date', $carbonHoje->day)
              ->first();

            if (!empty($reward)) {
                $result['collected'] = true;
            } 


        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $result['stack'] = $e->getTraceAsString();
          
            $erro = $e->getMessage(). ' => '.$user->name;
            $data = array(
                'class'   => 'UserRewardsController->getDayReward',
                'line'    => $e->getLine(),
                'message' => $erro
            );

            $notification = UserInstagram::where('user_id', 1)->first();
            $notification->notify(new ErrorLog($data));
        }

        return response()->json($result);

    }

    public function getReward(Request $request) {

        $result = array(
            'success' => true,
            'message' => 'Operação realizada com sucesso.'
        );

        try {

            $user = Auth::user();
            $user->gerarReward();


        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $result['stack'] = $e->getTraceAsString();
          
            $erro = $e->getMessage(). ' => '.$user->name;
            $data = array(
                'class'   => 'UserRewardsController->getReward',
                'line'    => $e->getLine(),
                'message' => $erro
            );

            $notification = UserInstagram::where('user_id', 1)->first();
            $notification->notify(new ErrorLog($data));
        }

        return response()->json($result);

    }
}
