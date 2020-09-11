<?php

namespace App\Http\Controllers;

use App\Services\User\RegService;
use Illuminate\Http\JsonResponse;

class RegController extends Controller
{

    public function nickname()
    {
        $regService = resolve(RegService::class);
        $status = $regService->status();

        $resp = [
            'status' => 1,
            'data' => [
                'nickname' => $regService->randomNickname(),
                'reg_status' => $status,
            ],
        ];
        if ($status === RegService::STATUS_BLOCK) {
            $resp['data']['msg'] = __('messages.Reg.nickname.the_same_ip_too_many');
        }
        return new JsonResponse($resp);
    }
}
