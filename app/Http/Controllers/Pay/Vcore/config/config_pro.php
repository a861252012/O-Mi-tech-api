<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/24
 * Time: 9:13
 */

/**
 * ***********************************************************************
 * 需求
 *
 *
 */
return [
    //'RockPay.web.wechat',
    'insert_plat'=>[
        'v2'=>[
            'notice_url'=>'http://peach.co/charge/notice2',
            'key'=>'P3v1e0NtpgHc6pjx',
        ]
    ],
    'three_plat'=>[
        '1' => [
            'code'=>'RockPay.WeChat',
            'app' => 't827',
            'wsdl' => 'https://www.ezsellmart.com/ws/RoFun.asmx',
            'query_url' => 'CheckTransactionWithCurrencyV2',
            'submit_url' => 'CommitTransaction',
            'r_url' => 'http://peach.co/pay/rock',
            'pay_url' => 'http://www.ezsellmart.com/directpay.aspx',

            'key' => '#e%9k2*z',
            'pw' => 'UnionPay',
        ],
        '2' => [
            'code'=>'Leajoy.AlipayTransfer',
            'r_url' => 'http://peach.co/pay/leajoy_AlipayTransfer',
            'strKeyInfo'=>'WFl1JbHNsavScR1NYok9LWlyfCUnuASX',
        ],
    ],
];