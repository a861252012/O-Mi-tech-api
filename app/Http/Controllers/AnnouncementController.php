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

class   AnnouncementController extends Controller{
    public function loginmsg(){
        $device = Input::get('device',1);
        
        $data = json_decode(Redis::hget('hloginmsg:' .SiteSer::siteId(),'list'));

        if(isset($data)){

            $A_data = array();
            foreach($data as $key => $val){
                $O = (object)array();
                if($key>strtotime('-3 month 00:00:00')){
                    if($val->device==$device){
                        if(isset($_GET['blank'])){
                            if($val->blank==$_GET['blank']){
                                $O->id = count($A_data)+1;
                                $O->type = $val->type;
                                $O->interval = $val->between;
                                $O->title = $val->title;
                                $O->content = $val->content;
                                $O->img = $val->image;
                                $O->url = $val->link;
                                $O->blank = $val->blank;
                                $O->create_time = $key;
                                array_push($A_data,$O);
                            }
                        }else{
                            $O->id = count($A_data)+1;
                            $O->type = $val->type;
                            $O->interval = $val->between;
                            $O->title = $val->title;
                            $O->content = $val->content;
                            $O->img = $val->image;
                            $O->url = $val->link;
                            $O->blank = $val->blank;
                            $O->create_time = $key;
                            array_push($A_data,$O);
                        }
                    }
                }//取三个月内
            }
        }
        return SuccessResponse::create($A_data);
    }
}
