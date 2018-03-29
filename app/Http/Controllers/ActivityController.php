<?php

namespace App\Http\Controllers;

use App\Models\Active;
use App\Models\ActivePage;
use App\Models\ActivityPag;
use App\Models\CharmRank;
use App\Models\ExtremeRank;
use App\Services\User\UserService;
use Core\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{

    public function index()
    {

        $data = Redis::get('huodong_cache');
        $list = collect(json_decode($data))->where('pid', 0);
        return  new jsonresponse($list);

    }

    /**
     * 活动详情页面
     * @param $id int 活动的详细信息
     */
    public function info($id)
    {
        $data = ActivityPag::where('img_text_id', $id)->where('dml_flag', '!=', 3)->first();
        $tmp = ActivityPag::where('pid', $id)->where('dml_flag', '!=', 3)->first();

        // 分割活动页面的图片
        $temp = explode(',', $tmp['temp_name']);
        $data['image'] = $temp[0]; // 第一个为原图
        $data['tmp'] = array_slice($temp, 1); // 抛出第一个原图的 后面的才是切割后的图
        return $data;
//        return $this->render('Activity/info',array('activity'=>$data));
    }

    /**
     * @author 活动详情页配置 <[<email address>]>
     * @param $action
     * @return Response
     */
    public function activity($action)
    {
        $active = $this->make('redis')->hgetall('hactive_page');

        //先从redis中获取，如果取不到，再去匹配数据库。
        if ($action != $active['url']) {
              $active  = ActivePage::where('url',$action)->first();
              if(empty($active)){
                  return  new jsonresponse(['status'=>'0','msg'=>'找不到该页面！']);
              }else{
                  $active = json_decode($active,true);
              }
        }


        if (Auth::id() > 0) {
              $arr['userHasTimes'] = intval($this->make('redis')->hget('hlottery_ary', Auth::id()));
              $arr['nickname'] = Auth::user()->nickname;
              $arr['is_login'] = true;
        } else {
              $arr = array(
                  'is_login' => false,
                  'userHasTimes' => 0,
                  'nickname' => ''
              );
        }

        $arr = array_merge($arr, $active);
        $arr =  $this->format_jsoncode($arr);
        return  new JsonResponse($arr);
    }

    /**
     * 魅力之星活动页面
     * @author TX
     * update 2015.6.20
     * @return Response
     */
    public function charmstar()
    {
        if ($this->make('redis')->exists('hactive')) {
            $charmstar = $this->make('redis')->hGetAll('hactive');
            $var['etime'] = date('Y/m/d H:i:s', strtotime($charmstar['etime'] . ' 23:59:59'));
            $var['stime'] = date('Y/m/d H:i:s', strtotime($charmstar['stime'] . ' 00:00:00'));
            $charm = $this->make('redis')->zRevRange('zvideo_charm', 0, 9, true);
            $star = $this->make('redis')->zRevRange('zvideo_extreme', 0, 9, true);
            $no_charm = 1;
            $userServer = resolve(UserService::class);
            foreach ($charm as $key => $value) {
                $var['charmlist'][$no_charm - 1]['points'] = $value;
                $var['charmlist'][$no_charm - 1]['nickname'] = $userServer->getUserByUid($key)['nickname'];
                $var['charmlist'][$no_charm - 1]['no'] = $no_charm;
                $no_charm++;
            }
            $no_star = 1;
            foreach ($star as $key => $value) {
                $var['starlist'][$no_star - 1]['points'] = $value;
                $var['starlist'][$no_star - 1]['nickname'] = $userServer->getUserByUid($key)['nickname'];
                $var['starlist'][$no_star - 1]['no'] = $no_star;
                $no_star++;
            }
        } else {
            $userServer = resolve(UserService::class);
//            $time= $this->get('database_connection')->fetchAll("SELECT * FROM video_active WHERE etime < '".date('Y-m-d')."' AND  dml_flag !=3  ORDER BY etime DESC LIMIT 1");
            $time = Active::where('etime', '<', date('Y-m-d'))->where('dml_flag', '!=', 3)
                ->orderBy('etime', 'desc')
                ->first();
            if (empty($time)) {
                $var['etime'] = 0;
                $var['stime'] = 0;
                $var['starlist'] = array();
                $var['charmlist'] = array();

                return JsonResponse::create($var);
              //  return $this->render('Business/charmstar', $var);
            }
            $var['etime'] = date('Y/m/d H:i:s', strtotime($time['etime'] . ' 23:59:59'));
            $var['stime'] = date('Y/m/d H:i:s', strtotime($time['stime'] . ' 00:00:00'));
            $charm = CharmRank::where('active_id', $time['active_id'])->groupBy('uid')
                ->orderBy('num', 'desc')
                ->take(10)
                ->get();
            $star = ExtremeRank::where('active_id', $time['active_id'])->groupBy('uid')
                ->orderBy('num', 'desc')
                ->take(10)
                ->get();

//            $charm = $this->get('database_connection')->fetchAll("select num as points, uid from video_charm_rank where active_id =". $time['active_id']."  GROUP BY uid ORDER BY  points desc limit 10");
//            $star = $this->get('database_connection')->fetchAll("select num as points, uid from video_extreme_rank where active_id =". $time['active_id']."  GROUP BY uid ORDER BY  points desc limit 10");
            foreach ($charm as $key => $value) {
                $var['charmlist'][$key]['points'] = $value['num'];
                $var['charmlist'][$key]['gnum'] = $value['gnum'];
                $var['charmlist'][$key]['nickname'] = $userServer->getUserByUid($value['uid'])['nickname'];
                $var['charmlist'][$key]['no'] = $key + 1;
            }
            $no_star = 1;
            foreach ($star as $key => $value) {
                $var['starlist'][$key]['points'] = $value['num'];
                $var['starlist'][$key]['gnum'] = $value['gnum'];
                $var['starlist'][$key]['nickname'] = $userServer->getUserByUid($value['uid'])['nickname'];
                $var['starlist'][$key]['no'] = $no_star;
                $no_star++;
            }
        }
        if (empty($var['charmlist'])) {
            $var['charmlist'] = array();
        }
        if (empty($var['starlist'])) {
            $var['starlist'] = array();
        }

        return JsonResponse::create($var);
    }

}