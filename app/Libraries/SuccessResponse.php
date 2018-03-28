<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2018/3/15
 * Time: 14:13
 */

namespace App\Libraries;


use Illuminate\Http\JsonResponse;

class SuccessResponse extends JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = [], $options = 0)
    {
        parent::__construct($data, $status, $headers, $options);
    }
    public static function create($data = null,$msg = "", $status = 1,$code=200, $headers = array()){
        return new static([
            'status'=>$status,
            'data'=>$data,
            'msg'=>$msg,
        ], $code, $headers);
    }
}