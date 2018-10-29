<?php
/**
 * Created by PhpStorm.
 * User: desmond
 * Date: 2018/3/23
 * Time: 11:16
 */


namespace App\Http\Controllers;
use App\Facades\SiteSer;
use App\Libraries\SuccessResponse;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use App\Models\Ads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class   AdsController extends Controller{
    public function getAd(){
        $device = Input::get('device',1);
        $data = $this->getAds($device);
        //针对ios和安卓进行广告数据优化
        if($device == 2 || $device == 4){
            foreach($data as $key=>$value){
                if(\is_object($value['meta'])){
                    $data[$key]['aspect_ratio'] = $value['meta']->aspect_ratio;
                    $data[$key]['duration'] = $value['meta']->duration;
                    unset( $data[$key]['meta']);
                }
            }
        }

        //$cdn  = SiteSer::config('remote_cdn_pic_url');
        $cdn = SiteSer::config('cdn_host')."/storage/uploads/s".SiteSer::siteId()."/oort"; // 'http://s.tnmhl.com/public/oort';
        $img_path = Ads::IMG_PATH;



       /* foreach($ddata as $k=>$v ){
          $v['vip_levels'] = (array)json_decode($v['vip_levels'],true);

          if(isset(Auth::user()->vip)&& !in_array(Auth::user()->vip,$v['vip_levels'])){
                   array_pull($data,$v);
          }
        }*/
        return SuccessResponse::create(compact('cdn','img_path','data'));
    }


    public function getAds($device)
    {


        $data = unserialize(Redis::hget('ads-' .SiteSer::siteId(),$device),['allowed_classes' => ['stdClass']]);
        if (empty($data)) {
            $data = Ads::where('device',$device)->published()->get()->toArray();
            Redis::hset('ads-' .SiteSer::siteId(),$device,serialize($data));
        }
        $file = base_path("bootstrap/cache/")."config.php";
        return new JsonResponse(['status'=>1,'data'=>$data,'msg'=>"publish time:".date('Y-m-d H:i:s',filectime($file))]);
    }

}
