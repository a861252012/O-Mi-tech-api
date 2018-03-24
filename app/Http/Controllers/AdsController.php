<?php
/**
 * Created by PhpStorm.
 * User: desmond
 * Date: 2018/3/23
 * Time: 11:16
 */


namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Ads;
use Illuminate\Support\Facades\Input;
use Symfony\Component\HttpFoundation\JsonResponse;

class   AdsController extends Controller{
    public function getAd(){
      $device = Input::get('device',1);

        $data = Ads::where('device',$device)->published()->get()->toArray();
        //$cdn  = $this->make('config')['config.REMOTE_CDN_PIC_URL'];
        $cdn = 'http://s.tnmhl.com/public/oort';
        $img_path = Ads::IMG_PATH;



       /* foreach($ddata as $k=>$v ){
          $v['vip_levels'] = (array)json_decode($v['vip_levels'],true);

          if(isset(Auth::user()->vip)&& !in_array(Auth::user()->vip,$v['vip_levels'])){
                   array_pull($data,$v);
          }
        }*/
        return jsonResponse::create(compact('cdn','img_path','data'));
    }

}
