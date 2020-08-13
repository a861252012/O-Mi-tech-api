<?php

return [
    'success' => '成功',
    'apiError' => 'API操作错误',
    'Guardian.getSetting.setting_is_empty' => '设定為空',
    'Guardian.buy.class_not_active' => '守护系统 :day 天方案未啟用',
    'Guardian.buy.user_point_not_enough' => '用戶鑽石不足,無法開通',
    'Guardian.buy.level_is_high' => '用戶现在的级别已大於要開通/續費的等级',
    'Guardian.buy.only_renewal' => '用戶已開通該級別守護，故僅能續費该等級守護',
    'Guardian.buy.only_active' => '用戶尚未開通該級別守護，故無法續費',
    'Api.reg.ip_block' => '来自您当前 IP 的注册数量过多，已暂停注册功能，请联系客服处理。',
    'Api.reg.invalid_request' => '无效的请求',
    'Api.reg.mobile_is_used' => '对不起, 该手机号已被使用!',
    'Api.reg.captcha_error' => '验证码错误',
    'Api.reg.username_wrong_format' => '注册邮箱不符合格式！(5-30位的邮箱)',
    'Api.reg.nickname_wrong_format' => '注册昵称不能使用/:;\空格,换行等符号！(2-11位的昵称)',
    'Api.reg.nickname_is_lawbreaking' => '昵称中含有非法字符，请修改后再提交!',
    'Api.reg.password_is_not_the_same' => '两次密码输入不一致!',
    'Api.reg.password_wrong_format' => '注册密码不符合格式!',
    'Api.reg.username_is_used' => '对不起, 该帐号已被使用!',
    'Api.reg.nickname_is_used' => '对不起, 该昵称已被使用!',
    'Api.reg.nickname_repeat' => '昵称已被注册或注册失败',
    'Api.reg.redis_token_error' => 'token 寫入redis失敗，請重新登錄',
    'Api.reg.please_login' => '请重新登陆!',
];
