<?php


namespace App\Http\Controllers;

use App\Libraries\SuccessResponse;
use App\Models\Agents;
use App\Models\Domain;
use App\Models\Faq;
use Core\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\BaconQrCodeGenerator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Facades\SiteSer;

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

        if (!in_array($act, ['aboutus', 'help', 'state', 'help_old'])) {
            throw new NotFoundHttpException();
        }
        $data = [];
        if ($act == 'help') {
            $redis = $this->make('redis');
            $data = json_decode($redis->get('video:faq:list'), true);


            $list = Faq::all();
            $list_keyed = $list->keyBy('id');
            $list_grouped = $list->groupBy('class');
            $sort1 = json_decode($redis->get('video:faq:sort:class:1'));
            $sort2 = json_decode($redis->get('video:faq:sort:class:2'));
            $sort[1]  = $this->formatesort($sort1);
            $sort[2]  = $this->formatesort($sort2);

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

        }

        return SuccessResponse::create($data, $msg = __('messages.success'), $status = 1);

    }

    public function  formatesort($sort){
        foreach ($sort as $key=>$value){
            $faq = Faq::where('id',$value)->first();
            if(is_null($faq)){
                unset($sort[$key]);
            }
        }
        return $sort;

    }
    /**
     * 贵族详情介绍页面
     * @author Yvan
     */
    public function noble()
    {
        return new JsonResponse(['status' => 1, 'data' => '贵族']);
        //return $this->render('Page/noble');
    }

    /**
     * APP下载页面
     * @author Yvan
     */
    public function download()
    {

        $downloadUrl = $this->make('redis')->hget('hsite_config:'.SiteSer::siteId(), 'down_url');
        if (!empty($downloadUrl)) {
            $downloadUrl = json_decode($downloadUrl);
        } else {
            $downloadUrl = [
                'PC' => '',
                'ANDROID' => '',
                'IOS' => '',
                'QRCODE'=>'',
            ];
        }


        return new JsonResponse(['data' => $downloadUrl]);
    }

    public function downloadQR()
    {
        // 如果是代理推广用户，则显示代理下载 QRCODE
        $url = trim($this->request()->cookie('agent'));
        if ($url != '') {
            //查询域名表
            $domain = Domain::where('url', $url)->where('status', 0)->first();
            if ($domain->exists) {
                //通过域名查询对应的代理列表（did为对应的domain id）
                $agent = Agents::where('did', $domain->id)->where('status', 0)->first();
                if ($agent->download_url) {
                    $img = QrCode::format('png')->size(200)->generate($agent->download_url);
                    $img = gzencode($img);
                    return Response::create($img)->header('Content-Type', 'image/png')
                        ->setMaxAge(300)
                        ->setSharedMaxAge(300)
                        ->header('Content-Encoding', 'gzip');
                }
            }
        }

        $redis = $this->make('redis');
        $download = $redis->hGet("hsite_config:".SiteSer::siteId(), "down_url");
        $data = json_decode($download, true);

        if(empty($download)){
            $qrcode_url = $this->request()->getUri();
        }

        //如果没有配置qrcode_url,生成的二维码取当前链接
        if (!isset($data['QRCODE']) || $data['QRCODE']=='' ) {
            $qrcode_url = $this->request()->getUri();
        } else {
            $qrcode_url = $data['QRCODE'];
        }

        if($this->request()->input('origin')!=''){
            $download2 = $redis->hGet("hsite_config:".SiteSer::siteId(), "origin_url");
            $data2 = json_decode($download2, true);
            if(isset($data2[$this->request()->input('origin')])){
                $qrcode_url = $data2[$this->request()->input('origin')];
            }
        }

        $img = QrCode::format('png')->size(200)->generate($qrcode_url);
        $img = gzencode($img);
        return Response::create($img)->header('Content-Type', 'image/png')
            ->setMaxAge(300)
            ->setSharedMaxAge(300)
            ->header('Content-Encoding', 'gzip');
    }

    public function contactQR()
    {
        $qrcode_url = $this->request()->input('url');
        $img = QrCode::format('png')->size(200)->generate($qrcode_url);
        $img = gzencode($img);
        return Response::create($img)->header('Content-Type', 'image/png')
            ->setMaxAge(300)
            ->setSharedMaxAge(300)
            ->header('Content-Encoding', 'gzip');
    }

    /**
     * 主播招募
     * @author Young
     */
    public function join()
    {
        return new JsonResponse(['status' => 1, 'data' => '招募']);
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