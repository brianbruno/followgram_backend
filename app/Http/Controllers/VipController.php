<?php

namespace App\Http\Controllers;

use App\UserVIP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\UserLike;
use Illuminate\Support\Facades\DB;

class VipController extends Controller
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

    public function buyVIP(Request $request) {

        $result = array(
            'success' => true,
            'message' => 'Operação realizada com sucesso.'
        );

        try {

            $user = Auth::user();

            $priceVip = 1000;
            $descriptionVip = 'Você comprou VIP.';

            if ($user->points >= $priceVip) {

                $userVipOld = UserVIP::where('end_date', '>', DB::raw('NOW()'))->first();

                if (empty($userVipOld)) {
                    $start = Carbon::now();
                    $date = $start->addDays(7);

                    $userVip = new UserVIP();
                    $userVip->user_id = $user->id;
                    $userVip->start_date = $start->format('Y-m-d');
                    $userVip->end_date = $date->format('Y-m-d');
                    $userVip->save();

                    $user->removePoints($priceVip, $descriptionVip);
                } else {
                    throw new \Exception("Você já possui VIP ativo.");
                }

            } else {

                throw new \Exception('Você não tem pontos suficientes para virar VIP.');

            }


        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            // $result['stack'] = $e->getTraceAsString();
        }

        return response()->json($result);


    }
}
