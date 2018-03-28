<?php


namespace App\Http\Controllers;

use App\Models\Faq;
use Core\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{

    /**
     * 搜索页面
     * @author Young
     */
    public function search()
    {

        return $this->render('Page/search');
    }

    /**
     * 帮助 关于 投诉页面
     * @param $act
     * @return \Core\Response
     */
    public function index($act)
    {

        if (!in_array($act, array('aboutus', 'help', 'state', 'help_old'))) {
            throw new NotFoundHttpException();
        }
        $data = [];
        if ($act == 'help') {
            $redis = $this->make('redis');
            $data = json_decode($redis->get('video:faq:list'), true);
            if (!$data) {
                $list = Faq::all();
                $list_keyed = $list->keyBy('id');
                $list_grouped = $list->groupBy('class');
                $sort[1] = json_decode($redis->get('video:faq:sort:class:1'));
                $sort[2] = json_decode($redis->get('video:faq:sort:class:2'));
                $data = [1 => [], 2 => []];
                foreach ($data as $class => &$value) {
                    if (!empty($sort[$class])) {
                        $value = array_map(function ($id) use ($class, $list_keyed) {
                            return $list_keyed[$id];
                        }, $sort[$class]);
                    } else {
                        $value = $list_grouped[$class];
                    }
                }
                $redis->set('video:faq:list', json_encode($data));
            }
        }
        return  new JsonResponse($data);
       // return $this->render('Page/' . $act, compact('data'));
    }

    /**
     * 贵族详情介绍页面
     * @author Yvan
     */
    public function noble()
    {
        return  new JsonResponse(['status'=>1,'msg'=>'贵族']);
        //return $this->render('Page/noble');
    }

    /**
     * APP下载页面
     * @author Yvan
     */
    public function download()
    {
        return  new JsonResponse(['status'=>1,'msg'=>'下载']);
       // return $this->render('Page/download');
    }

    /**
     * 主播招募
     * @author Young
     */
    public function join()
    {
        return  new JsonResponse(['status'=>1,'msg'=>'招募']);
       // return $this->render('Page/join');
    }

    /**
     * 合作加盟
     * @author Young
     */
    public function cooperation()
    {
        return $this->render('Page/cooperation');
    }
}