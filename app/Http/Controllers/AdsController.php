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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class AdsController extends Controller
{
    /* 快取時間(分) */
    const APCU_TTL = 1;

    public function getAd()
    {
        $device = Input::get('device', 1);

        $data = Cache::remember(SiteSer::siteId() . ':oort2bunny:' . $device, self::APCU_TTL, function () use($device) {
            $ads = $this->getAds($device);
            //针对ios和安卓进行广告数据优化
            if ($device == 2 || $device == 4) {
                foreach ($ads as $key => $value) {
                    $ads[$key]['aspect_ratio'] = $value['meta']->aspect_ratio;
                    $ads[$key]['duration'] = $value['meta']->duration;
                }
            }

            return $ads;
        });

        $cdn = '';
        $img_path = '';

        return SuccessResponse::create(compact('cdn', 'img_path', 'data'))->setMaxAge(60);
    }


    public function getAds($device)
    {
        $data = unserialize(Redis::hget('ads-' . SiteSer::siteId(), $device), ['allowed_classes' => ['stdClass']]);

        if (empty($data)) {
            $data = Ads::where('device', $device)
                ->orderby('order', 'asc')
                ->orderby('position', 'asc')
                ->orderby('created', 'desc')
                ->where('published_at', '<=', date('Y-m-d H:i:s'))
                ->get()
                ->toArray();

            $cdn = SiteSer::config('cdn_host') . "/storage/uploads/s" . SiteSer::siteId() . "/oort"; // 'http://s.tnmhl.com/public/oort';
            $img_path = Ads::IMG_PATH;

            foreach ($data as $k => $v) {
                if ($v['ad_upload_type'] == '1') {
                    $data[$k]['image'] = $cdn . $img_path . $v['image'];
                } else {
                    $data[$k]['image'] = $v['image'];
                }
            }

            Redis::hset('ads-' . SiteSer::siteId(), $device, serialize($data));
        }

        return $data;
    }

}
