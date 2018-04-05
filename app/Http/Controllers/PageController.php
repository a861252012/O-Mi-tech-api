<?php


namespace App\Http\Controllers;

use App\Models\Faq;
use Core\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use App\Libraries\SuccessResponse;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\BaconQrCodeGenerator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
        return SuccessResponse::create($data,$msg = "获取成功", $status = 1);
    //    return  new JsonResponse($data);
       // return $this->render('Page/' . $act, compact('data'));
    }

    /**
     * 贵族详情介绍页面
     * @author Yvan
     */
    public function noble()
    {
        return  new JsonResponse(['status'=>1,'data'=>'贵族']);
        //return $this->render('Page/noble');
    }

    /**
     * APP下载页面
     * @author Yvan
     */
    public function download()
    {
        $downloadUrl = $this->make('redis')->hget('hconf', 'down_url');
        if(!empty($downloadUrl)){
            $downloadUrl = json_decode($downloadUrl);
        }else{
            $downloadUrl =array(
                'PC'=>'',
                'ANDROID'=>'',
                'IOS'=>''
            );
        }


       return new JsonResponse(['data'=>$downloadUrl]);
    }
    public function downloadQR()
    {
       $redis = $this->make('redis');
        $download =  $redis->hGet("hconf", "down_url");
        $data = json_decode($download,true);

        //如果没有配置qrcode_url,生成的二维码取当前链接
        if(!isset($data['QRCODE'])){
            $qrcode_url = $this->request()->getUri();
        }else{
            $qrcode_url = $data['QRCODE'];
        }

        $qrcode = new BaconQrCodeGenerator;
        $img = $qrcode->format('png')->size(200)->generate('www.baidu.com');
        return Response::create($img)->header('Content-Type','image/png')
            ->setMaxAge(300)->setPublic()->header('Age',300);
    }

    /**
     * 主播招募
     * @author Young
     */
    public function join()
    {
        return  new JsonResponse(['status'=>1,'data'=>'招募']);
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