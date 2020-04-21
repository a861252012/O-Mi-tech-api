<?php

namespace App\Http\Controllers;

use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;

class RankController extends Controller
{
    /** 历史主播人气榜*/
    const ZRANK_POP_HISTORY = 'zrank_pop_history';
    /** 月主播人气榜*/
    const ZRANK_POP_MONTH = 'zrank_pop_month:';
    /** 周主播人气榜*/
    const  ZRANK_POP_WEEK = 'zrank_pop_week';
    /** 日主播排行榜*/
    const  ZRANK_POP_DAY = 'zrank_pop_day';
    /** 历史财富榜*/
    const  ZRANK_RICH_HISTORY = 'zrank_rich_history';
    /** 历史财富榜*/
    const  ZRANK_RICH_HISTORY_BY_MYSQL = 'zrank_rich_history_mysql';
    /** 财富榜*/
    const  ZRANK_RICH_MONTH = 'zrank_rich_month:';
    /** 周主播财富榜*/
    const  ZRANK_RICH_WEEK = 'zrank_rich_week';
    /** 日主播财富榜*/
    const  ZRANK_RICH_DAY = 'zrank_rich_day';
    /** 历史财富榜    */
    const  ZRANK_GAME_HISTORY = 'rank_game_his';
    /** 财富榜    */
    const  ZRANK_GAME_MONTH = 'rank_game_month';
    /** 周主播财富榜*/
    const  ZRANK_GAME_WEEK = 'rank_game_week';
    /** 日主播财富榜*/
    const  ZRANK_GAME_DAY = 'rank_game_day';
    /**主播预约月排名榜位 */
    const  ZRANK_APPOINT_MONTH = 'zrank_appoint_month';
    const RANK_PAGE_SIZE = 15;


    public function rankData()
    {
        /** @var UserService $userService */
        $userService = resolve(UserService::class);
        $rankStr = $userService->getAllRank();
        $rank = json_decode($rankStr, true);
        $rank['data']['rank_exp_day'] = [];
        $rank['data']['rank_exp_his'] = [];
        $rank['data']['rank_exp_month'] = [];
        $rank['data']['rank_exp_week'] = [];

        return response($rank)->header('Content-Type', 'application/json');
    }
}
