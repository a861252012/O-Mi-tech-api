<?php

return [
    'success' => '成功',
    'apiError' => 'API操作错误',
    'page_not_found' => '找不到该页面!',
    'successfully_obtained' => '获取成功',
    'unknown_error' => '操作出现未知错误',
    'permission_denied' => '没有权限!',
    'unknown_user' => '用户错误',
    'captcha_error' => '验证码错误',
    'must_login_on_platform' => '请由平台网站登入',
    'is_vip' => '目前已是贵族身份，无法使用喔！',
    'exchanged_failed' => '兑换失败',
    'exchanged_successful' => '兑换成功',
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
    'Activity.detailtype.wrong_type' => '配置的链接错误或者type类型错误',
    'Api.getUserByDes.invalid_user' => '无效用户',
    'Api.platExchange.processing' => '已送出，请耐心等待审核',
    'Api.platExchange.Already_exist' => '已存在审核中的订单',
    'Api.getTimeCountRoomDiscountInfo.not_vip' => '非贵族',
    'Api.getTimeCountRoomDiscountInfo.permission_denied' => '无权限组',
    'Api.aa.login_permission_denied' => '您的账号已经被禁止登录，请联系客服！',
    'Api.platform.validate_failed' => '您的账号已经被禁止登录，请联系客服！',
    'Api.platform.wrong_param' => ':num 接入方提供参数不对',
    'Api.platform.closed' => '接入已关闭',
    'Api.platform.wrong_sign' => '接入方校验失败',
    'Api.platform.data_acquisition_failed' => '接入方数据获取失败  :url  :data  返回: :res',
    'Api.platform.uuid_does_not_exist' => '接入方uuid不存在',
    'Api.platform.empty_nickename' => '接入方用户名为空',
    'Api.platform.user_does_not_exist' => '用户不存在 :user  :uid  :res',
    'BackPack.use_item_failed' => '使用失敗',
    'BackPack.useItem.is_vip' => '目前已是贵族身份，无法使用喔！',
    'Mobile._getEquipHandle.use_in_room' => '该道具限房间内使用,不能装备！',
    'Mobile.login.password_required' => '用户名密码不能为空',
    'Mobile.login.account_block_30days_no_show' => '您超过30天未开播，账号已被冻结，请联系客服QQ: :S_qq',
    'Mobile.login.password_error' => '用户名密码错误',
    'Mobile.login.token_error' => 'token写入redis失败，请重新登录!',
    'Mobile.statistic.param_error' => '请求参数错误',
    'Mobile.getFans.host_id_not_exist' => '该主播id不存在！',
    'Mobile.passwordChange.old_password_required' => '原始密码不能为空！',
    'Mobile.passwordChange.more_or_equal_than_six_char_length' => '请输入大于或等于6位字符串长度',
    'Mobile.passwordChange.new_password_is_not_the_same' => '新密码两次输入不一致!',
    'Mobile.passwordChange.old_password_is_wrong' => '原始密码错误!',
    'Mobile.passwordChange.new_and_old_is_the_same' => '新密码和原密码相同',
    'Mobile.passwordChange.modify_failed' => '修改失败!',
    'Mobile.loginmsg.no_data' => '無資料',
    'Charge.block_msg' => '尊敬的用户，您好，您今日的充值申请已达上限，请点击在线客服，让我们协助您，感谢您的支持与理解！',
    'Charge.order.charge_error' => '需要充值请联系客服！！！',
    'Charge.del.the_record_is_not_yours' => '这条纪录不是你的',
    'Charge.del.success' => '删除成功',
    'Charge.pay.please_enter_right_price' => '请输入正确的金额!',
    'Charge.pay.please_select_top_up_way' => '请选择充值渠道',
    'Charge.pay.the_top_up_channel_is_not_open' => '充值渠道未开放',
    'Charge.pay.one_pay_error' => '请联系客服，错误代码  :onePayError',
    'Charge.exchange.orderID_is_empty' => '没有订单号！',
    'Charge.exchange.order_is_not_exist' => '该订单号不存在！',
    'Charge.exchange.empty_status' => '状态不正确！',
    'Charge.processGD.empty_name' => '请输入名称',
    'Charge.processGD.limit' => '1小时内，不能提同一金额，同一姓名的订单',
];
