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
        return new JsonResponse($resp);
    }
}
