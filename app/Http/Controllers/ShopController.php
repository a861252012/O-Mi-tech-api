<?php


namespace App\Http\Controllers;

use App\Models\Goods;
use App\Models\UserGroupPermission;
use App\Services\UserGroup\UserGroupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class ShopController extends Controller
{

    /**
     * 商城页面 导航商城
     * @update Young
     * @return \Core\Response
     */
    public function index()
    {
        $lists = Goods::where('unit_type', '2')->where('is_show', 1)->where('category', 1002)->get();
        $vipmount = Goods::where('unit_type', '2')
            ->where('is_show', 1)
            ->where('category', 1008)
            ->with('mountGroup')->get();

        // 通过服务调用用户组
        $group = resolve(UserGroupService::class)->getPublicGroup();

        $result['lists'] = $lists;
        $result['vipmount'] = $vipmount;
        $result['group'] = $group;
        $result = $this->format_jsoncode($result);
        return  new jsonresponse($result);

    }

    /**
     * @description 返回贵族专属坐骑信息
     * @author Young <[<email address>]>
     * @return json
     */
    public function getPropInfo()
    {

        $goodId = $this->make('request')->get('gid');

        if (Auth::guest()) {
            return new JsonResponse(array(
                'ret' => false,
                'info' => __('messages.Shop.getPropInfo.not_login')
            ));
        }

        $good = Goods::where('gid', $goodId)->with('mountGroup')->first();
        return new JsonResponse(
            array(
                'ret' => true,
                'info' => $good
            )
        );

    }

    /**
     * AJAX 获取用组的信息
     *
     * @param $
     * @return JsonResponse
     */
    public function getgroup()
    {
        // 获取vip坐骑的id
        $gid = $this->make('request')->get('gid');
        $msg = array(
            'status' => 1,
            'msg' => __('messages.Shop.getgroup.get_data_successfully')
        );
        $userGroup = resolve(UserGroupService::class)->getGroupById($gid);
        if (!$userGroup) {
            $msg['code'] = 1003;
            $msg['msg'] = __('messages.Shop.getgroup.get_data_failed');
            return new JsonResponse($msg);
        }
        $msg['data'] = $userGroup;
        $msg['data']['level_name'] = __('messages.user.ViplevelName.' . $userGroup['level_id']);

        return new JsonResponse($msg);
    }

    /**
     * AJAX 获取所有贵族的信息
     * @author Young <young@wisdominfo.my>
     * @param null
     * @return JsonResponse
     */
    public function getGroupAll()
    {
        $msg = array(
            'status' => 1,
            'msg' => __('messages.Shop.getgroup.get_data_successfully')
        );
        $userGroup = resolve(UserGroupService::class)->getPublicGroup();
        if (!$userGroup) {
            $msg['status'] = 1003;
            $msg['msg'] = __('messages.Shop.getgroup.get_data_failed');
            return (new JsonResponse($msg));
        }

        $A_userGroup = array();
        foreach ($userGroup as $S_userGroup) {
            $permission = UserGroupPermission::where('gid', $S_userGroup['gid'])->get()->toArray();
            if (!empty($permission)) {
                $permission = $this->setHtmlText($permission[0]);
            }
            $S_userGroup->level_name = __('messages.user.ViplevelName.' . $S_userGroup->level_id);
            $S_userGroup->haswelcome = $permission['haswelcome'];
            $S_userGroup->haschateffect = $permission['haschateffect'];
            $S_userGroup->hasvipseat = $permission['hasvipseat'];
            array_push($A_userGroup,$S_userGroup);
        }

        $msg['data'] = $A_userGroup;

        return new JsonResponse($msg);
    }

    private function setHtmlText($array = array())
    {
        //是否限制访问房间
        if ($array['allowvisitroom']) {
            $data['allowvisitroom'] = '限制';
        } else {
            $data['allowvisitroom'] = '不限制';
        }
        //是否允许修改昵称
        if ($array['modnickname'] > 0) {
            $mod = explode('|', $array['modnickname']);
            $data['modnickname'] = $mod[0] . '次/';
            switch ($mod[1]) {
                case 'week':
                    $data['modnickname'] .= '周';
                    break;
                case 'month':
                    $data['modnickname'] .= '月';
                    break;
                case 'year':
                    $data['modnickname'] .= '年';
                    break;
            }
        } elseif ($array['modnickname'] == 0) {
            $data['modnickname'] = "限制";
        } elseif ($array['modnickname'] < 0) {
            $data['modnickname'] = "不受限";
        }
        //是否有进房欢迎语
        if ($array['haswelcome']) {
            $data['haswelcome'] = '有';
        } else {
            $data['haswelcome'] = '无';
        }
        //是否有聊天特效
        if ($array['haschateffect']) {
            $data['haschateffect'] = '有';
        } else {
            $data['haschateffect'] = '无';
        }
        //聊天文字长度限制
//        $limit = explode('|',$array['chatlimit']);
//        if(count($limit)>1){
//            $data['chatlimit'] = $limit[0].'s/'. $limit[1];
//        }else{
//            $data['chatlimit'] = $limit[0];
//        }
        //是否拥有贵宾席
        if ($array['hasvipseat']) {
            $data['hasvipseat'] = '有';
        } else {
            $data['hasvipseat'] = '无';
        }

        //是否防止被禁言
        if (!empty($array['nochat'])) {
            $nochat = explode('|', $array['nochat']);
            $data['nochat'] = '防';
            if ($nochat[0] == 1) {
                $data['nochat'] = '房主';
            }
            if ($nochat[0] == 2) {
                $data['nochat'] = '管理员';
            }
            if (isset($nochat[1]) && $nochat[1] == 2) {
                $data['nochat'] .= '、管理员';
            }
            if (isset($nochat[1]) && $nochat[1] == 1) {
                $data['nochat'] .= '、房主';
            }
        } else {
            $data['nochat'] = "无";
        }
        $data['chatlimit'] = $array['chatlimit'] . '字';
        if ($array['chatsecond'] == 0) {
            $data['chatsecond'] = '不受限';
        } elseif ($array['chatsecond'] > 0) {
            $data['chatsecond'] = $array['chatsecond'] . '秒';
        } else {
            $data['chatsecond'] = '肯定是游客';
        }

        //提普通用户权限
        if ($array['nochatlimit']) {
            $data['nochatlimit'] = $array['nochatlimit'] . '人/天';
        } else {
            $data['nochatlimit'] = "无";
        }

        //是否防止被踢
        if ($array['avoidout']) {
            $avoidout = explode('|', $array['avoidout']);
            $data['avoidout'] = '防';
            if ($avoidout[0] == 1) {
                $data['avoidout'] = '房主';
            }
            if ($avoidout[0] == 2) {
                $data['avoidout'] = '管理员';
            }
            if (isset($avoidout[1]) && $avoidout[1] == 2) {
                $data['avoidout'] .= '、管理员';
            }
            if (isset($avoidout[1]) && $avoidout[1] == 1) {
                $data['avoidout'] .= '、房主';
            }
        } else {
            $data['avoidout'] = "不防";
        }

        //提普通用户权限
        if ($array['letout']) {
            $data['letout'] = $array['letout'] . '人/天';
        } else {
            $data['letout'] = "无";
        }

        //是否拥有隐身权限
        if ($array['allowstealth']) {
            $data['allowstealth'] = '有';
        } else {
            $data['allowstealth'] = "无";
        }
        if ($array['discount']) {
//            $data['discount'] = $this->config['discountType'][$array['discount']];
            $data['discount'] = $this->discountType[$array['discount']];
        }
        return $data;
    }

}