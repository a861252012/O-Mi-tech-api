<?php

namespace App\Services\Task\GiftScript;

use App\Models\Pack;
class Goods extends GiftBase implements GiftInterface
{

    /**
     * 添加用户的礼物到背包中
     *
     * @param $gifts
     * @param $uid
     */
    public function present($gifts,$uid)
    {
        $flag = false;
        foreach($gifts as $gift) {
            $has_gif = Pack::where('uid',$uid)->where('gid',$gift['id'])->first();
            if ($has_gif) {
                if (!empty($gift['exp'])) {
                    $expires = $has_gif ['expires'] + $gift['exp'] * 24 * 3600;
                    Pack::where('uid',$uid)->where('gid',$gift['id'])->update(array('expires' => $expires));
                }
                $flag = true;
            }else {
                $exp = time() + $gift['exp'] * 24 * 3600;
                $result = Pack::create(array('uid' => $uid, 'gid' => $gift['id'], 'num' => $gift['num'], 'expires' => $exp));
                if ($result !== false) {
                    $flag = true;
                } else {
                    $flag = false;
                }
            }
        }
        return $flag;
    }
}