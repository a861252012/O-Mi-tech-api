<?php

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Models\Active;
use App\Models\ActivePage;
use App\Models\ActivityPag;
use App\Models\CharmRank;
use App\Models\ExtremeRank;
use App\Models\Users;
use App\Services\User\UserService;
use Core\Exceptions\NotFoundHttpException;
//use Debugbar;
use DebugBar\DebugBar;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Libraries\SuccessResponse;
use App\Models\ActiveCommon;
use App\Models\CommonRank;

class ActivityController extends Controller
{

    public function index()
    {

        $data = Redis::get('huodong_cache:' . SiteSer::siteId());
        $list = collect(json_decode($data))->where('pid', 0);

        $list = json_decode(json_encode($list), true);
        $temp = [];
        $result = [];
        foreach ($list as $key => $value) {
            $temp[] = $value;
            array_push($result, $value);
        }
        $list = $this->format_jsoncode($result);
        return new jsonresponse($list);

    }

    /**
     * 活动详情页面
     * @param $id int 活动的详细信息
     */
    public function info($id)
    {

        $data = ActivityPag::where('img_text_id', $id)->where('dml_flag', '!=', 3)->first();
        $tmp = ActivityPag::where('pid', $id)->where('dml_flag', '!=', 3)->first();


        if (empty($tmp)) {
            $data = "";
            $status = 0;
        } else {
            // 分割活动页面的图片
            $temp = explode(',', $tmp['temp_name']);
            $data['image'] = $temp[0]; // 第一个为原图
            $data['tmp'] = array_slice($temp, 1); // 抛出第一个原图的 后面的才是切割后的图
            $status = 1;
        }
        $result['type'] = 3;
        unset($data['type']);
        $result['activity'] = $data;

        return $result;

        //   return SuccessResponse::create($data,$msg = "获取成功", $status);

//        return $this->render('Activity/info',array('activity'=>$data));
    }

    /**
     * @author 活动详情页配置 <[<email address>]>
     * @param $action
     * @return Response
     */
    public function activity($action)
    {
        $active = Redis::hgetall('hactive_page:' . SiteSer::siteId());

        //先从redis中获取，如果取不到，再去匹配数据库。

        if ($action != $active['url']) {
            $active = ActivePage::where('url', $action)->first();
            if (empty($active)) {
                return ['status' => 0, 'msg' => '找不到该页面！'];
            } else {
                $active = json_decode($active, true);
            }
        }


        if (Auth::check()) {
            $arr['userHasTimes'] = intval(Redis::hget('hlottery_ary', Auth::id()));
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


        /* $result['type'] = $arr['type'];
         unset($arr['type']);
         $result['activity'] = $arr;*/
        return $arr;
        //return  new JsonResponse(['data'=>$arr]);
    }

    /**
     * 魅力之星活动页面
     * @author TX
     * update 2015.6.20
     * @return Response
     */
    public function charmstar()
    {
        if ($this->make('redis')->exists('hactive:' . SiteSer::siteId())) {
            $charmstar = $this->make('redis')->hGetAll('hactive:' . SiteSer::siteId());
            $var['etime'] = date('Y/m/d H:i:s', strtotime($charmstar['etime'] . ' 23:59:59'));
            $var['stime'] = date('Y/m/d H:i:s', strtotime($charmstar['stime'] . ' 00:00:00'));
            $charm = $this->make('redis')->zRevRange('zvideo_charm:' . SiteSer::siteId(), 0, 9, true);
            $star = $this->make('redis')->zRevRange('zvideo_extreme:' . SiteSer::siteId(), 0, 9, true);

            //zvideo_charm_gnum:".SiteSer::siteId().':'.$gid
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
                return $var;
                //return JsonResponse::create($var);
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
        //$var =$this->format_jsoncode($var);
        return $var;
        //  return JsonResponse::create($var);
    }

    /**
     * 多礼物活动
     * @return Response
     */
    public function mulitRank()
    {
        $var = [];
        $var['stime'] = 0;
        $var['etime'] = 0;
        $var['list'] = [];

        //获取活动
        $dbType = "msyql";
        $active = [];
        $active_id = null;

        if ($this->make('redis')->exists('hactive_common:' . SiteSer::siteId())) {
            $active = $this->make('redis')->hGetAll('hactive_common:' . SiteSer::siteId());
            $dbType = "redis";
            $active_id = $active['active_id'];
        } else {
            $temp = ActiveCommon::where('etime', '<', date('Y-m-d'))->where('dml_flag', '!=', 3)
                ->orderBy('etime', 'desc')
                ->first();
            $active = $temp;
            $dbType = "msyql";
            $active_id = $temp ? $temp['active_id'] : null;
        }

        //无活动退出
        if (empty($active)) {
            return $var;
        }

        //获取排行榜
        $gidArray = explode(',', $active['gids']);
        $rank = [];

        foreach ($gidArray as $k => $gid) {
            $charmlist = $this->single($active_id, $gid, 1, $dbType);
            $starlist = $this->single($active_id, $gid, 2, $dbType);

            $rank[$gid][1] = $charmlist;
            $rank[$gid][2] = $starlist;
//            array_push($rank,$charmlist);
//            array_push($rank,$starlist);
        }

        $var['etime'] = date('Y/m/d H:i:s', strtotime($active['etime'] . ' 23:59:59'));
        $var['stime'] = date('Y/m/d H:i:s', strtotime($active['stime'] . ' 00:00:00'));
        $times = $this->countDown($var['etime']);   //倒计时
        $var['countdown'] = $times;
        $var['list'] = $rank;

        return $var;
        //return new Response(json_encode($var));
    }

    public function activityUrl($action)
    {

        $active = ActivePage::where('url', $action)->first();
        if (empty($active)) {
            return new jsonresponse(['status' => 0, 'msg' => '找不到该页面！']);
        } else {
            return new jsonresponse(['status' => 1, 'msg' => '获取成功', 'data' => ['url' => 'activity/' . $active->toArray()['url']]]);
        }
    }

    public function detailtype()
    {
        //根据id获取对应的url
        $id = intval($_GET['id']);
        if (!$id) {
            return new JsonResponse(['status' => 0, 'msg' => '活动id错误']);
        }
        $activepage = ActivityPag::where('img_text_id', $id)->where('dml_flag', '!=', 3)->first();
        if (empty($activepage)) {
            return new JsonResponse(['status' => 0, 'msg' => '找不到页面']);
        }
        $url = $activepage->toArray()['url'];
        if (empty($url)) {
            return new JsonResponse(['status' => 0, 'msg' => '未设置url路径']);
        }
        //根据url匹配以下三种情况 nac ; /activity/cde + /CharmStar;/activity/cde + /paihang
        $url_type = explode('/', $url);
        $data = $this->info($id);
        if ($url_type[1] == 'nac') {
            return new jsonresponse(['status' => 1, 'data' => $data]);
        }
        if ($url_type[1] == 'activity') {
            $data['activity'] = $this->activity($url_type[2]);
            $data['type'] = $data['activity']['type'];
            //单双页排行区分
            if ($data['activity']['type'] == 1) {
                $data['charmstar'] = $this->charmstar();
            }
            if ($data['activity']['type'] == 2) {
                $data['paihang'] = $this->mulitRank();
            }
            return new jsonresponse(['status' => 1, 'data' => $data, 'msg' => '获取成功']);
        }
        return new JsonResponse(['status' => 0, 'msg' => '找不到页面']);
    }


    /**
     * 单个排行榜
     * @param string $gid 物品
     * @param string $type 主播OR用户
     * @param string $db 数据源
     * @return array
     */
    private function single($active_id = null, $gid = '', $type = '1', $db = 'mysql')
    {
        $list = [];
        if ($db == 'redis') {

            $zkey = $type == 1 ? "zvideo_charm:" . SiteSer::siteId() . ':' . $gid : "zvideo_extreme:" . SiteSer::siteId() . ':' . $gid;   //zvideo_charm_gnum
            //  $zkey .= ':'.$gid;
            $list = $charm = $this->make('redis')->zRevRange($zkey, 0, 9, true);
        } else {
            $charm = CommonRank::where([
                'active_id' => $active_id,
                'gid' => $gid,
                'rank_type' => $type
            ])->where('dml_flag', '!=', 3)->groupBy('uid')
                ->orderBy('gnum', 'desc')
                ->take(10)
                ->get();

            $charm = $charm->toArray();
            $values = array_column($charm, 'gnum');
            $keys = array_column($charm, 'uid');
            $list = array_combine($keys, $values);
        }

        $userServer = resolve(UserService::class);
        $no_charm = 1;
        $charmlist = [];
        foreach ($list as $key => $value) {
            $temp = [];
            $temp['points'] = $value;
            $temp['no'] = $no_charm++;
            $temp['nickname'] = $userServer->getUserByUid($key)['nickname'];
            $temp['gnum'] = $value;
            array_push($charmlist, $temp);
        }
        return $charmlist;
    }

    /**
     * 倒计时
     * @param $etime
     * @return mixed
     */
    private function countDown($etime)
    {
        $time = strtotime($etime) - time();
        if ($time <= 0) {
            $var['day'] = 0;
            $var['hour'] = 0;
            $var['min'] = 0;
            $var['second'] = 0;
        } else {
            $var['day'] = floor($time / 86400);
            if ($var['day'] < 10) {
                $var['day'] = "0" . $var['day'];
            }
            $time = $time % 86400;
            $var['hour'] = floor($time / 3600);
            if ($var['hour'] < 10) {
                $var['hour'] = "0" . $var['hour'];
            }
            $time = $time % 3600;
            $var['min'] = floor($time / 60);
            if ($var['min'] < 10) {
                $var['min'] = "0" . $var['min'];
            }
            $time = $time % 60;
            $var['second'] = $time >= 10 ? $time : "0" . $time;
        }
        return $var;
    }
}