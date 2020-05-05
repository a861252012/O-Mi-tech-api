<?php
/**
 * One Pay金流控制器
 * @author Weine
 * @date 2020-05-05
 */
namespace App\Http\Controllers;

use App\Services\Charge\OnePayService;
use Illuminate\Http\Request;

class OnePayController extends Controller
{
    protected $onePayService;

    public function __construct(OnePayService $onePayService)
    {
        $this->onePayService = $onePayService;
    }

    /**
     * 金流異步通知
     */
    public function notify(Request $request)
    {
        try {
            $params = [
                'memberid',
                'orderid',
                'merchant_order',
                'amount',
                'datetime',
                'returncode',
                'pay_ext',
                'sign'
            ];

            if (!$this->onePayService->notify($request->only($params))) {
                return false;
            }

            return 'OK';
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
}
