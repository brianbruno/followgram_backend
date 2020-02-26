<?php

namespace App\Http\Controllers;

use App\UserVIP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\UserLike;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
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

    public function getPointsData(Request $request) {

        $result = array(
            'success' => true,
            'message' => 'Operação realizada com sucesso.'
        );

        try {
          
            /*$points = DB::select(DB::raw("SELECT `users`.`name`, `user_points`.`points`, ,
                                        
                                    FROM user_points, user_insta, users                                        
                                    WHERE user_insta.user_id = `users`.`id`
                                      AND users.id = user_points.user_id                                      
                                    GROUP BY users.name, user_points.points, ul.total, uf.total
                                    ORDER BY user_points.points DESC
                                    LIMIT 15
                                    OFFSET 0"));*/
          
            $points = DB::table('user_points')
                    ->select('users.name', 'user_points.points', 
                             DB::raw("(SELECT COALESCE(SUM(total), 0) FROM (SELECT COALESCE(COUNT(user_like.id), 0) total, insta_target FROM user_like WHERE status = 'confirmed' GROUP BY insta_target) T, user_insta WHERE T.insta_target = user_insta.id AND user_insta.user_id = users.id ) AS likes"), 
                             DB::raw("(SELECT COALESCE(SUM(total), 0) FROM (SELECT COALESCE(COUNT(user_follow.id), 0) total, insta_target FROM user_follow WHERE status = 'confirmed' GROUP BY insta_target) T, user_insta WHERE T.insta_target = user_insta.id AND user_insta.user_id = users.id) AS follows"))
                    ->join('users', 'user_points.user_id', '=', 'users.id')   
                    ->groupBy('users.name', 'user_points.points', 'users.id')
                    ->orderByRaw('user_points.points desc')->paginate(15);
          /*
            $points = DB::table('user_points')
                    ->select('users.name', 'user_points.points', DB::raw('count(user_like.id) as likes'), DB::raw('count(user_follow.id) as follows'))
                    ->join('users', 'user_points.user_id', '=', 'users.id')   
                    ->join('user_insta', 'user_insta.user_id', 'users.id')
                    ->rightJoin('user_like', 'user_insta.id', 'user_like.insta_liking')
                    ->rightJoin('user_follow', 'user_insta.id', 'user_follow.insta_following')  
                    ->where('user_like.status', 'confirmed')
                    ->where('user_follow.status', 'confirmed')
                    ->groupBy('users.name', 'user_points.points')
                    ->orderByRaw('user_points.points desc')->paginate(15);
            dd($points);die;*/
          
            $result['data'] = $points;


        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            // $result['stack'] = $e->getTraceAsString();
        }

        return response()->json($result);
    }
  
    public function getTasksDay(Request $request) {
        $result = array(
            'success' => true,
            'message' => 'Operação realizada com sucesso.'
        );

        try {
          
            $date = \Carbon\Carbon::today()->subDays(1);

            $interactions = DB::table('user_like')
                    ->select(DB::raw('hour(user_like.created_at) as hour'), DB::raw('COUNT(user_like.id) as total'))
                    ->where('created_at', '>', DB::raw('NOW() - INTERVAL 24 HOUR'))
                    ->orderByRaw('created_at ASC')
                    ->groupBy(DB::raw('hour(created_at)'))
                    ->get()->toArray();
          
            $interactionsFollow = DB::table('user_follow')
                    ->select(DB::raw('hour(user_follow.created_at) as hour'), DB::raw('COUNT(user_follow.id) as total'))
                    ->where('created_at', '>', DB::raw('NOW() - INTERVAL 24 HOUR'))
                    ->orderByRaw('created_at ASC')
                    ->groupBy(DB::raw('hour(created_at)'))
                    ->get()->toArray();
          
            $date = (new \DateTime())->modify('-23 hour');
          
            $hoursPast = 0;
            $totals = [];
            $labels = [];
          
            for($hoursPast = 0; $hoursPast < 24; $hoursPast++) {
                $exists = false;
                foreach ($interactions as $interaction) {
                    if (intval($interaction->hour) == intval($date->format('H'))) {
                        $totals[] = $interaction->total;
                        $exists = true;
                    }
                }
              
                foreach ($interactionsFollow as $interactionFollow) {
                    if (intval($interactionFollow->hour) == intval($date->format('H'))) {
                        $totals[] = $interactionFollow->total;
                        $exists = true;
                    }
                }
              
                if (!$exists)
                    $totals[] = 0;
                $labels[] = $date->format('H');
                $date->modify('+ 1 hour');
            }

            $result['labels'] = $labels; 
            $result['data'] = $totals;


        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            // $result['stack'] = $e->getTraceAsString();
        }

        return response()->json($result);
    }
}
