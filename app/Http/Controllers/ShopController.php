<?php


namespace App\Http\Controllers;

use App\Models\Goods;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

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
        $group = $this->make('userGroupServer')->getPublicGroup();

        $result['lists'] = $lists;
        $result['vipmount'] = $vipmount;
        $result['group'] = $group;

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
                'info' => '请前往首页登录!'
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
            'msg' => ''
        );
        $userGroup = $this->make('userGroupServer')->getGroupById($gid);
        if (!$userGroup) {
            $msg['code'] = 1003;
            $msg['msg'] = '数据获取失败';
            return (new JsonResponse($msg))->setCallback('cb');
        }
        $msg['info'] = $userGroup;
        return (new JsonResponse($msg))->setCallback('cb');
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
            'code' => 0,
            'msg' => ''
        );
        $userGroup = $this->make('userGroupServer')->getPublicGroup();
        if (!$userGroup) {
            $msg['code'] = 1003;
            $msg['msg'] = '数据获取失败';
            return (new JsonResponse($msg))->setCallback('cb');
        }
        $msg['info'] = $userGroup;
        return (new JsonResponse($msg))->setCallback('cb');
    }

}