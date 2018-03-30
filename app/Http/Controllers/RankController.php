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

    /**
     * 排行榜页面
     *
     * @return \Core\Response
     */
    function index()
    {
        return $this->render('Rank/index', array());
    }

    function rankData()
    {
        $cb = $this->request()->get('callback', null);

        /** @var UserService $userService */
        $userService = resolve(UserService::class);
        $month = date('Ym');
//        $month = '201701';
        $data = [
            'rank_appoint_month' => $userService->getRank(static::ZRANK_APPOINT_MONTH . $month, 0, static::RANK_PAGE_SIZE),
            'rank_exp_day' => $userService->getRank(static::ZRANK_POP_DAY, 0, static::RANK_PAGE_SIZE),
            'rank_exp_his' => $userService->getRank(static::ZRANK_POP_HISTORY, 0, static::RANK_PAGE_SIZE),
            'rank_exp_month' => $userService->getRank(static::ZRANK_POP_MONTH . $month, 0, static::RANK_PAGE_SIZE),
            'rank_exp_week' => $userService->getRank(static::ZRANK_POP_WEEK, 0, static::RANK_PAGE_SIZE),
            'rank_game_day' => $userService->getRank(static::ZRANK_GAME_DAY, 0, static::RANK_PAGE_SIZE),
            'rank_game_his' => $userService->getRank(static::ZRANK_GAME_HISTORY, 0, static::RANK_PAGE_SIZE),
            'rank_game_month' => $userService->getRank(static::ZRANK_GAME_MONTH . $month, 0, static::RANK_PAGE_SIZE),
            'rank_game_week' => $userService->getRank(static::ZRANK_GAME_WEEK, 0, static::RANK_PAGE_SIZE),
            'rank_rich_day' => $userService->getRank(static::ZRANK_RICH_DAY, 0, static::RANK_PAGE_SIZE),
            'rank_rich_week' => $userService->getRank(static::ZRANK_RICH_WEEK, 0, static::RANK_PAGE_SIZE),
            'rank_rich_month' => $userService->getRank(static::ZRANK_RICH_MONTH . $month, 0, static::RANK_PAGE_SIZE),
            'rank_rich_his' => $userService->getRank(static::ZRANK_RICH_HISTORY, 0, static::RANK_PAGE_SIZE),
        ];

        /** jsonp or json */
       // $jsonData = json_encode($data);
       // $jsonData = $cb ? $cb . '(' . $jsonData . ')' : $jsonData;
        $jsonData =  $this->format_jsoncode($data);
        return new JsonResponse($jsonData);

    }
}