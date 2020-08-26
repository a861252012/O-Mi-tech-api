<?php

namespace App\Services\Api;

use App\Repositories\GoodsRepository;
use App\Facades\SiteSer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redis;

class ApiService
{
    protected $goodsRepository;

    public function __construct(GoodsRepository $goodsRepository)
    {
        $this->goodsRepository = $goodsRepository;
    }

    public function getGoodsList()
    {
        $site = SiteSer::siteId();
        $isPC = strpos(request()->server('HTTP_REFERER'), '/h5');//HTTP_REFERER 如有/h5,則為二站pc

        //判斷user對應的禮物名稱語系
        $locale = strtolower(App::getLocale());//zh, zh_TW, zh_HK, en

        //讀取redis禮物列表
        if ($site === 2 && $isPC) {
            $goodsRedisKey = 'goods_list:2:' . $locale;
        } else {
            $goodsRedisKey = 'goods_list:' . $locale;
        }

        $data = json_decode(Redis::get($goodsRedisKey), true);

        if (!$data) {
            //禮物種類改為固定資訊,排除推薦禮物
            $data = [
                1 => [
                    "name"     => __('messages.goods.category_id.1'),
                    "category" => 1,
                    "items"    => []
                ],
                3 => [
                    "name"     => __('messages.goods.category_id.3'),
                    "category" => 3,
                    "items"    => []
                ],
                4 => [
                    "name"     => __('messages.goods.category_id.4'),
                    "category" => 4,
                    "items"    => []
                ],
                5 => [
                    "name"     => __('messages.goods.category_id.5'),
                    "category" => 5,
                    "items"    => []
                ],
            ];

            /**
             * 根据上面取出的分类的id获取对应的礼物
             * 然后格式化之后塞入到具体数据中
             * 如為二站且為PC,則不顯示跳蛋禮物
             */
            $getRedisGiftList = $this->goodsRepository->getList();

            $itemName = 'name';
            if (!empty($locale) || $locale !== 'zh') {
                $itemName = 'name_' . $locale;
            }

            foreach ($getRedisGiftList as $item) {
                $good = [];
                $good['gid'] = $item['gid'];
                $good['price'] = $item['price'];
                $good['category'] = $item['category'];
                $good['name'] = $item[$itemName];
                $good['desc'] = $item['desc'];
                $good['sort'] = $item['sort_order'];
                $good['time'] = $item['time'];
                $good['playType'] = $item['playType'];
                $good['type'] = $item['type'];
                $good['scaleX'] = $item['xScale'];
                $good['scaleY'] = $item['xScale'];
                $good['x'] = $item['x'];
                $good['y'] = $item['y'];

                //与现在的时间进行对比，如果在7天之内的都算是新礼物 isNew
                if ((time() - strtotime($item['create_time'])) / (24 * 60 * 60) < 7) {
                    $good['isNew'] = 1;
                } else {
                    $good['isNew'] = 0;
                }

                $jumpEggArray = [];//跳蛋禮物

                //检查幸运礼物
                $good['isLuck'] = $this->isLuck($item['gid']);

                if ($site == 2 && $isPC && $item['gid'] >= 200000 && $item['gid'] < 300000) {
                    $jumpEggArray[] = $good;
                } else {
                    $data[$item['category']]['items'][] = $good;
                }
            }

            //把跳蛋禮物放到列表最後面
            if (!empty($jumpEggArray)) {
                foreach ($jumpEggArray as $v) {
                    $data[$v['category']]['items'][] = $v;
                }
            }

            Redis::set($goodsRedisKey, json_encode($data));
            Redis::expire($goodsRedisKey, 120);//redis禮物列表 ttl兩分鐘
        }

        //返回json给前台 用了一个array_values格式化为 0 开始的索引数组
        return array_values($data);
    }

    protected function isLuck($gid)
    {
        return Redis::hget("hgoodluck:$gid:1", 'bet') ? 1 : 0;
    }
}