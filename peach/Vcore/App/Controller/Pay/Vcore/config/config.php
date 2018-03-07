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
$config =  [
    //'RockPay.web.wechat',
    'insert_plat'=>[
        'v2'=>[
            'notice_url'=>'http://peach.co/charge/notice2',
            'key'=>'P3v1e0NtpgHc6pjx',
        ]
    ],
    //3Leajoy   '支付宝', '微信',  'QQ钱包',  '网银支付', '人工转账'
    'router'=>[
        '21'=>'RockPay.WeChat',
        '22'=>'RockPay.WeChat',
        '23'=>'RockPay.WeChat',

        '35'=>'Leajoy.AlipayTransfer',
        '11'=>'DGPay.AliPay',
        '12'=>'DGPay.Wechat',
    ],
];
return $config;