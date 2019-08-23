<?php

namespace App\Http\Controllers;

use App\Services\User\RegService;
use Illuminate\Http\JsonResponse;

class RegController extends Controller
{

    public function nickname()
    {
        $regService = resolve(RegService::class);
        $randomNickname = $regService->randomNickname();
        $status = $regService->status();

        $resp = [
            'status' => 1,
            'data' => [
                'nickname' => $randomNickname,
                'reg_status' => $status,
            ],
        ];
        if ($status === RegService::STATUS_BLOCK) {
            $resp['data']['msg'] = '来自相同 IP 的注册数量过多，已暂停注册功能，请联系客服处理。';
        }
        return new JsonResponse($resp);
    }
}
