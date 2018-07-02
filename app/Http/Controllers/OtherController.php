<?php

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Models\RoomOneToMore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * Class ApiController
 * @package     App\Controller
 * @author      dc
 * @version     20151021
 * @description 代替外部各平台的生成数据，读取等接口
 */
class OtherController extends Controller
{
    /**
     * uid
     *
     * $onetomanyStr = 'cb({"total_users":2,"room_url":"/video_gs/room/","rooms":[{"id":2410,"lv_type":1,"lv_exp":8,"rid":2650003,"uid":2650003,"tid":7,"username":"v2_ea3",
     * "headimg":"","points":"300","start_time":"12-31 23:40","end_time":"01-01 00:00","new_user":0,"live_status":0,"user_count":8,"duration":"-1351309","attens":4,
     * "one_to_many_status":1,"enterRoomlimit":0,"origin":11},
     * {"id":2801,"lv_type":1,"lv_exp":8,"rid":2650003,"uid":2650003,"tid":7,"username":"v2_ea3","headimg":"","points":"300","start_time":"12-20 14:20","end_time":"12-20 14:40",
     * "new_user":0,"live_status":0,"user_count":1,"duration":"1200","attens":4,"one_to_many_status":1,"enterRoomlimit":0,"origin":11}]});';
     */
    public function createHomeOneToManyList(Request $request)
    {
        $uid = $request->get('rid', "10000");
        $flashVersion = SiteSer::config('publish_version') ?: 'v201504092044';
        $onetomanyArr = RoomOneToMore::query()->where('uid', $uid)->get();
        $rooms = [];
        foreach ($onetomanyArr as $onetomany) {
            //改成接口调用
            $user = UserSer::getUserByUid($onetomany->uid);
            $temp = $user ? $user->only(["lv_type", "lv_exp", "rid", "uid", "username", "headimg", "points", "new_user", "points"]) : [];
            $room = $onetomany->only(['id', 'live_status', 'origin']);
            $other = [];
            $other['start_time'] = date('m-d H:i', strtotime($onetomany['starttime']));
            $other['end_time'] = date('m-d H:i', strtotime($onetomany['endtime']));
            $other['duration'] = strtotime($onetomany['endtime']) - strtotime($onetomany['starttime']);

            $other['tid'] = 7;
            $other['user_count'] = 7;
            $other['attens'] = 4;
            $other['one_to_many_status'] = 1;
            $other['enterRoomlimit'] = 1;

            array_push($rooms, array_merge($room, $temp, $other));
        }
        $rs = [
            'total_users' => $onetomanyArr->count(),
            'room_url' => "/video_gs/room/",
            'rooms' => $rooms,
        ];
        $onetomanyStr = 'cb(' . json_encode($rs) . ');';


        $a = Redis::set('home_one_many_' . $flashVersion, $onetomanyStr);
        return ['data' => $onetomanyStr, 'msg' => '创建直播间一对多数据成功'];
    }

    public function createHomeOneToOneList()
    {

    }

}
