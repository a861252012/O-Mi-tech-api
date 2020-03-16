<?php
/**
 * 守護功能 服務
 * @author Weine
 * @date 2020/02/15
 */

namespace App\Services;


use App\Http\Resources\Guardian\GuardianMyInfoResource;
use App\Http\Resources\Guardian\GuardianSettingResource;
use App\Repositories\GuardianRepository;
use App\Repositories\GuardianSettingRepository;
use App\Repositories\UsersRepository;

class GuardianService
{
    protected $guardianSettingRepository;
    protected $guardianRepository;
    protected $usersRepository;

    public function __construct(
        GuardianSettingRepository $guardianSettingRepository,
        GuardianRepository $guardianRepository,
        UsersRepository $usersRepository
    ) {
        $this->guardianSettingRepository = $guardianSettingRepository;
        $this->guardianRepository = $guardianRepository;
        $this->usersRepository = $usersRepository;
    }

    /* 取得設定 */
    public function getSetting()
    {
        return GuardianSettingResource::collection($this->guardianSettingRepository->getAll());
    }

    /* 取得我的守護 */
    public function getMyInfo()
    {
//        $result = $this->guardianRepository->getMy(auth()->id());
//        $guardianPermission = $this->guardianSettingRepository->getOne($result->guard_id);
//        $result['guardian_permission'] = collect($guardianPermission)->except(['id', 'name', 'activate', 'renewal', 'created_at', 'updated_at']);
//
//        return $result;

        return new GuardianMyInfoResource($this->usersRepository->getUserById(auth()->id()));
    }

    //取得user最新的財富等級
    public function getRichLv($user_exp)
    {
        $richLv = array(
            33 => 350000000,
            32 => 274143000,
            31 => 199143000,
            30 => 144143000,
            29 => 99143000,
            28 => 64143000,
            27 => 39143000,
            26 => 29143000,
            25 => 20143000,
            24 => 14143000,
            23 => 10143000,
            22 => 7143000,
            21 => 5143000,
            20 => 3343000,
            19 => 2343000,
            18 => 1743000,
            17 => 1293000,
            16 => 993000,
            15 => 793000,
            14 => 633000,
            13 => 493000,
            12 => 373000,
            11 => 273000,
            10 => 183000,
            9  => 113000,
            8  => 63000,
            7  => 33000,
            6  => 18000,
            5  => 10000,
            4  => 5000,
            3  => 2000,
            2  => 500,
            1  => 0
        );

        foreach ($richLv as $k => $v) {
            if ($user_exp >= $v) {
                $new_level = $k;
                break;
            }
            continue;
        }

        return $new_level;
    }


    //取得主播最新的等級
    public function getAnchorLevel($anchor_exp)
    {
        $levelExp = array(
            30 => 93850000,
            29 => 79650000,
            28 => 67050000,
            27 => 55950000,
            26 => 46250000,
            25 => 37850000,
            24 => 30650000,
            23 => 24550000,
            22 => 19450000,
            21 => 15250000,
            20 => 11850000,
            19 => 9150000,
            18 => 7050000,
            17 => 5450000,
            16 => 4250000,
            15 => 3350000,
            14 => 2650000,
            13 => 2050000,
            12 => 1550000,
            11 => 1150000,
            10 => 850000,
            9  => 600000,
            8  => 400000,
            7  => 250000,
            6  => 150000,
            5  => 100000,
            4  => 60000,
            3  => 30000,
            2  => 10000,
            1  => 0
        );

        foreach ($levelExp as $k => $v) {
            if ($anchor_exp >= $v) {
                $new_exp = $k;
                break;
            }
            continue;
        }

        return $new_exp;
    }

    /* 計算進直播間價格 */
    public function calculRoomSale($price, $salePercent)
    {
        return (int) round(((100 - $salePercent)/100) * $price);
    }

}