/*
Navicat MySQL Data Transfer

Source Server         : dev.vvv162.主
Source Server Version : 50721
Source Host           : 10.1.100.162:3306
Source Database       : vvv

Target Server Type    : MYSQL
Target Server Version : 50721
File Encoding         : 65001

Date: 2018-06-05 16:57:58
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `video_site_configs`
-- ----------------------------
DROP TABLE IF EXISTS `video_site_configs`;
CREATE TABLE `video_site_configs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL COMMENT '站点ID（88888不区分站点）',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '参数名称',
  `k` varchar(255) NOT NULL DEFAULT '' COMMENT '键',
  `v` text COMMENT '值',
  `desc` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  `reserved` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1=预留字段，无法删除',
  `type` varchar(6) NOT NULL DEFAULT 'string' COMMENT 'string,int,float,bool,json',
  `client` tinyint(4) DEFAULT '11' COMMENT '0关，1开（第一位前台，第二位后台）',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_id` (`site_id`,`k`)
) ENGINE=InnoDB AUTO_INCREMENT=1141 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of video_site_configs
-- ----------------------------
INSERT INTO `video_site_configs` VALUES ('924', '1', '', 'down_url', '{\"PC\":\"/PCdownload1\",\"ANDROID\":\"/apkdownload1\",\"IOS\":\"/iosdownload1\"}', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('925', '1', '邮件', 'email', '你好，{{name}}<br><p>\'\' &nbsp;\"\" &nbsp;\\\\ &nbsp;<javascript></javascript></p><p>您在第一坊申请了验证安全邮箱，可以正常使用<a href=\"http://www.qq.com\" data-ke-src=\"http://www.qq.com\" target=\"_blank\">找回密码</a>功能，保证您的账号安全。请点击以下链接完成您的邮箱验证：</p>{{url}}<br>（如果点击链接没反应，请复制激活链接，<span>粘贴到浏览器地址栏后访问）<br></span><br> <a href=\"http://www.sina.com.cn\" data-ke-src=\"http://www.sina.com.cn\" target=\"_blank\"><img src=\"http://img0.bdstatic.com/img/image/shouye/sheying0930.jpg\" data-ke-src=\"http://img0.bdstatic.com/img/image/shouye/sheying0930.jpg\" alt=\"\"></a><br>激活邮件24小时内有效，超过24小时请重新验证。<br><span>激活邮件将在您激活一次后失效。<br></span><br><span><br> </span><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><span>第一坊 1Room.cc</span></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><blockquote style=\"margin:0 0 0 40px;border:none;padding:0px;\"><span style=\"line-height:1.5;\">{{date}}</span></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote></blockquote><span><br></span>如您错误的收到了此邮件，请不要点击激活按钮，该帐号将不会被启用。<br>这是一封系统自动发出的邮件，请不要直接回复，如您有任何疑问，请联系客服<br>', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('926', '1', '', 'errorinfopop', '{\"1\":\"1\",\"2\":0,\"3\":\"1\",\"4\":\"1\",\"5\":\"sdsf/ffgf\"}', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('927', '1', '', 'errorinfopopssss', '{\"1\":\"1\",\"2\":\"1\",\"3\":\"1\",\"4\":\"1\",\"5\":\"http://10.1.100.89/collection/errorinfo\"}', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('928', '1', '', 'errorurl', 'd', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('929', '1', '', 'gamepop', '1', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('930', '1', '', 'hsign_switch', 'a:2:{s:4:\"sign\";s:1:\"1\";s:10:\"introduced\";s:1:\"1\";}', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('931', '1', '', 'hxpay_switch', '1', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('932', '1', '', 'imagesAdsConfig', '3', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('933', '1', '', 'imagesChargeAdsConfig', '1', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('934', '1', '', 'imagesFocusConfig', '3', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('935', '1', '', 'recharge_datetime', '{\"begintime\":\"2016-04-20 00:00:00\",\"endtime\":\"2016-05-20 23:59:59\"}', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('936', '1', '', 'recharge_passwd', '111111', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('937', '1', '', 'ticheng_company_percent', '5', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('938', '1', '', 'xspay_switch', '1', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('939', '1', '', 'recharge_url', 'http://10.1.100.103:10080/video_gs/web_api/add_point?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('940', '1', '', 'minus_gold_url', 'http://10.1.100.103:10080/video_gs/web_api/reduce_point?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('941', '1', '', 'goods_url', 'http://10.1.100.103:10080/video_gs/web_api/add_pack?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('942', '1', '', 'announcement_url', 'http://10.1.100.103:10080/video_gs/web_api/add_notice?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('943', '1', '', 'host_online_url', 'http://10.1.100.103:10080/video_gs/web_api/list_room?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('944', '1', '', 'add_host_url', 'http://10.1.100.103:10080/video_gs/web_api/add_room?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('945', '1', '', 'del_host_url', 'http://10.1.100.103:10080/video_gs/web_api/del_room?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('946', '1', '', 'is_online', 'http://10.1.100.103:10080/video_gs/web_api/get_online?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('947', '1', '', 'edit_host_type', 'http://10.1.100.103:10080/video_gs/web_api/add_tag?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('948', '1', '', 'keyword_cache_sync', 'http://10.1.100.103:10080/video_gs/kw/rf?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('949', '1', '', 'pay_repay_url', '/charge/callFailOrder', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('950', '1', '', 'activity_url', '/activitySend', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('951', '1', '', 'usr_real_cash', 'http://www.1room.org/balance?', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('952', '1', '', 'activity_name', 'firstcharge', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('953', '1', '', 'mail_type', '[\"\\u767b\\u5f55\\u6ce8\\u518c\\u7c7b\",\"\\u5145\\u503c\\u63d0\\u6b3e\\u7c7b\",\"\\u4f18\\u60e0\\u6d3b\\u52a8\\u7c7b\",\"\\u5ba2\\u670d\\u670d\\u52a1\\u7c7b\",\"\\u6295\\u8bc9\\u610f\\u89c1\\u7c7b\"]', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('954', '1', '', 'channel_type', '[\"sex8\",\"\\u767e\\u5ea6\",\"\\u5927\\u5a92\\u4f53\",\"\\u8bba\\u575b\"]', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('955', '1', '', 'pay_type', '{\"1\":\"\\u94f6\\u884c\\u8f6c\\u8d26\",\"2\":\"\\u62bd\\u5956\",\"3\":\"paypal\",\"4\":\"\\u540e\\u53f0\\u5145\\u503c\",\"5\":\"\\u5145\\u503c\\u8d60\\u9001\",\"6\":\"\\u4efb\\u52a1\\u548c\\u7b7e\\u5230\\u5956\\u52b1\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('956', '1', '', 'agents_type', '[\"\\u4ee3\\u7406\\u5546\",\"\\u6e20\\u9053\\u5546\"]', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('957', '1', '', 'recharge_type', '[\"\\u4eba\\u5de5\\u5145\\u503c\",\"\\u6d3b\\u52a8\\u5956\\u52b1\",\"\\u5e73\\u53f0\\u8d54\\u507f\"]', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('958', '1', '', 'action_type', '{\"Edit\":\"\\u7f16\\u8f91\",\"Del\":\"\\u5220\\u9664\",\"Shutter\":\"\\u5c01\\u505c\\u8d26\\u53f7\",\"Reset\":\"\\u91cd\\u7f6e\\u7528\\u6237\\u5bc6\\u7801\",\"Add\":\"\\u6dfb\\u52a0\",\"Mesaage\":\"\\u7cfb\\u7edf\\u6d88\\u606f\",\"Default\":\"\\u8bbf\\u95ee\",\"Recharge\":\"\\u5145\\u503c\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('959', '1', '', 'host_type', '{\"1\":\"\\u666e\\u901a\\u827a\\u4eba\",\"2\":\"\\u4e2d\\u7ea7\\u827a\\u4eba\",\"3\":\"\\u9ad8\\u7ea7\\u827a\\u4eba\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('960', '1', '', 'set_menu', '{\"1\":\"\\u662f\",\"0\":\"\\u5426\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('961', '1', '', 'cache_file', './cache/cache.php', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('962', '1', '', 'pay_verify_message', '{\"serviceCode\":\"FC0029\",\"version\":\"1.0\",\"serviceType\":\"03\",\"signType\":\"md5\",\"sysPlatCode\":\"V\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('963', '1', '', 'xs_privitekey', '32ousdjf9343djjomvsdf2233dskdlfb', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('964', '1', '', 'xs_url', 'http://192.168.10.155:8086/fc_iface/service/g2p', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('965', '1', '', 'task_reward', '{\"points\":\"\\u94bb\\u77f3\",\"goods\":\"\\u9053\\u5177\",\"icon\":\"\\u5934\\u8854\",\"medals\":\"\\u52cb\\u7ae0\",\"top\":\"\\u7b49\\u7ea7\\u76f4\\u8fbe\",\"level\":\"\\u7b49\\u7ea7\\u63d0\\u5347\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('966', '1', '', 'task_type', '{\"check_email\":\"\\u9a8c\\u8bc1\\u90ae\\u7bb1\",\"invite\":\"\\u9996\\u6b21\\u9884\\u7ea6\",\"points\":\"\\u5145\\u503c\",\"openvip\":\"\\u9996\\u5f00\\u8d35\\u65cf\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('967', '1', '', 'color', '{\"#FFFFFF\":\"\\u767d\",\"#A5EAB5\":\"\\u6d45\\u7eff\",\"#0FA731\":\"\\u6df1\\u7eff\",\"#09E1F9\":\"\\u6d45\\u84dd\",\"#056290\":\"\\u6df1\\u84dd\",\"#FF0000\":\"\\u7ea2\",\"#FFED00\":\"\\u91d1\\u9ec4\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('968', '1', '', 'usr_keep_vip', 'http://www.r.com/charge/checkKeepVip', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('969', '1', '', 'image_server', 'http://10.1.100.194:4869', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('970', '1', '', 'recharge_channel', '{\"7\":{\"0\":\"\\u652f\\u4ed8\\u5b9d\\u8f6c\\u5e10(\\u7a7a)\",\"7\":\"\\u652f\\u4ed8\\u5b9d\\u8f6c\\u5e10\"},\"5\":{\"0\":\"\\u652f\\u4ed8\\u5b9d(\\u7a7a)\",\"5\":\"\\u652f\\u4ed8\\u5b9d\\u652f\\u4ed81\",\"10\":\"\\u652f\\u4ed8\\u5b9d\\u652f\\u4ed82\"},\"6\":{\"0\":\"\\u5fae\\u4fe1\\u652f\\u4ed8(\\u7a7a)\",\"6\":\"\\u5fae\\u4fe1\\u652f\\u4ed8\"},\"2\":{\"0\":\"QQ\\u94b1\\u5305(\\u7a7a)\",\"2\":\"QQ\\u94b1\\u5305\"},\"4\":{\"0\":\"\\u7f51\\u94f6\\u8f6c\\u8d26(\\u7a7a)\",\"4\":\"\\u7f51\\u94f6\\u8f6c\\u8d26\"},\"8\":{\"0\":\"\\u94f6\\u884c\\u5361\\u4eba\\u5de5\\u8f6c\\u5e10(\\u7a7a)\",\"8\":\"\\u94f6\\u884c\\u5361\\u4eba\\u5de5\\u8f6c\\u5e10\"},\"9\":{\"0\":\"\\u4eac\\u4e1c\\u652f\\u4ed8(\\u7a7a)\",\"9\":\"\\u4eac\\u4e1c\\u652f\\u4ed8\"}}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('971', '1', '', 'url', '[\"\\/\",\"\\/login\"]', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('972', '1', '', 'ad', '{\"gift_down\":\"\\u76f4\\u64ad\\u95f4\\u793c\\u7269\\u4e0b\\u65b9\",\"nearly_logo\":\"\\u76f4\\u64ad\\u95f4logo\\u53f3\\u8fb9\",\"login_ad\":\"\\u767b\\u5f55\\u6846\\u5e7f\\u544a\",\"video_ad\":\"\\u623f\\u95f4\\u906e\\u5c4f\\u5e7f\\u544a\",\"layout_slide\":\"\\u9996\\u9875\\u8f6e\\u64ad\",\"layout_top\":\"\\u9996\\u9875\\u53f3\\u4fa7\\u5934\\u90e8\\u5e7f\\u544a\",\"layout_bottom\":\"\\u9996\\u9875\\u53f3\\u4fa7\\u5e95\\u90e8\\u5e7f\\u544a\",\"layout_center1\":\"\\u9996\\u9875\\u5185\\u5bb9\\u5e7f\\u544a1\",\"topup_right\":\"\\u5145\\u503c\\u9875\\u53f3\\u8fb9\\u5e7f\\u544a\",\"welcome\":\"\\u624b\\u673a\\u5f00\\u5c4f\",\"main_top\":\"\\u624b\\u673a\\u9996\\u9875\\u9876\\u680f\",\"main_top1\":\"\\u624b\\u673a\\u9996\\u9875\\u9876\\u680f1\",\"main_top2\":\"\\u624b\\u673a\\u9996\\u9875\\u9876\\u680f2\",\"main_top3\":\"\\u624b\\u673a\\u9996\\u9875\\u9876\\u680f3\",\"down_code\":\"\\u4e0b\\u8f7d\\u4e8c\\u7ef4\\u7801\",\"room_mid\":\"\\u76f4\\u64ad\\u95f4\\u4e2d\\u95f4\\u6846\",\"nearly_logo_tel\":\"\\u624b\\u673a\\u6e90\\u76f4\\u64ad\\u95f4logo\\u5de6\\u8fb9\",\"room_mid_tel\":\"\\u624b\\u673a\\u6e90\\u76f4\\u64ad\\u95f4\\u4e2d\\u95f4\\u6846\",\"gift_down_tel\":\"\\u624b\\u673a\\u6e90\\u76f4\\u64ad\\u95f4\\u793c\\u7269\\u4e0b\\u65b9\",\"layout_center2\":\"\\u9996\\u9875\\u9876\\u680f2\",\"layout_center3\":\"\\u9996\\u9875\\u5185\\u5bb9\\u5e7f\\u544a3\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('973', '1', '', 'database_driver', 'pdo_mysql', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('974', '1', '', 'database_host', '10.1.100.101', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('975', '1', '', 'database_port', '3306', '', '0', 'int', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('976', '1', '', 'database_name', 'video', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('977', '1', '', 'database_user', 'videousr', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('978', '1', '', 'mailer_transport', 'smtp', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('979', '1', '', 'mailer_host', '127.0.0.1', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('980', '1', '', 'mailer_user', null, '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('981', '1', '', 'mailer_password', null, '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('982', '1', '', 'locale', 'en', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('983', '1', '', 'secret', 'kalaaAaaaKoop', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('984', '1', '', 'debug_toolbar', '1', '', '0', 'bool', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('985', '1', '', 'debug_redirects', '0', '', '0', 'bool', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('986', '1', '', 'use_assetic_controller', '1', '', '0', 'bool', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('987', '1', '', 'remote_pic_url', 'http://10.1.100.194:4870', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('988', '1', '', 'remote_cdn_pic_url', 'http://www.1room.my/public/oort', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('989', '1', '', 'vfphp_sign', '0c9ec123b8d0bca68dcd4a82822317ec', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('990', '1', '', 'des_encryt_key', 'iloveyvv', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('991', '1', '', 'redis_cli_ip_port', '10.1.100.101:6379', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('992', '1', '', 'web_cdn_static', 'http://s.tnmhl.com/public', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('993', '1', '', 'publish_version', 'v2017100901', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('994', '1', '', 'pic_cdn_static', 'http://p1.1room1.co/public', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('995', '1', '', 'verify_from_mail', 'admin@1room.bar', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('996', '1', '', 'repass_from_mail', 'admin@1room.bar', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('997', '1', '', 'vfphp_host_name', 'http://www.1room.my', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('998', '1', '', 'vfjava_host_name', 'http://v.1room.my', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('999', '1', '', 'pay_call_url_m', 'http://pay.payhere.cc/fcpay/service/g2p', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1000', '1', '', 'pay_call_url', 'http://10.1.100.41:8084/fcpay/service/g2p', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1001', '1', '', 'verfriy_code_status', '1', '', '0', 'int', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1002', '1', '', 'pay_privatekey', '32ousdjf9343djjomvsdf2233dskdlfb', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1003', '1', '', 'lang', '[\"zh-cn\",\"en-us\"]', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1004', '1', '', 'default_lang', 'zh-cn', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1005', '1', '', 'pay_service_code', 'FC0045', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1006', '1', '', 'pay_version', '1.3', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1007', '1', '', 'pay_service_type', '03', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1008', '1', '', 'pay_signtype', 'md5', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1009', '1', '', 'pay_sysplatcode', 'V', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1010', '1', '', 'pay_charset', 'utf-8', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1011', '1', '', 'pay_notice_url', '/charge/notice', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1012', '1', '', 'pay_reback_url', '/charge/reback', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1013', '1', '', 'pay_log_file', '/tmp/video_charge_callback.log', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1014', '1', '', 'pay_find_code', 'FC0029', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1015', '1', '', 'activity_open', '1', '', '0', 'int', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1016', '1', '', 'pay_channel', '{\"xs\":\"NP\",\"hx\":\"IPS\"}', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1017', '1', '', 'send_gift_expire', '2014-01-01/2015-03-01', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1018', '1', '', 'invitation_status', '0', '', '0', 'int', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1019', '1', '', 'reg_send_status', '1', '', '0', 'int', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1020', '1', '', 'first_recharge_status', '1', '', '0', 'int', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1021', '1', '', 'lottry_status', '1', '', '0', 'int', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1022', '1', '', 'login_send_point', '100', '', '0', 'int', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1023', '1', '', 'service_online_type', '[\"\\u767b\\u9646\\u6ce8\\u518c\\u7c7b\",\"\\u5145\\u503c\\u63d0\\u6b3e\\u7c7b\",\"\\u4f18\\u60e0\\u6d3b\\u52a8\\u7c7b\",\"\\u5ba2\\u670d\\u670d\\u52a1\\u7c7b\",\"\\u6295\\u8bc9\\u610f\\u89c1\\u7c7b\"]', '', '0', 'json', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1024', '1', '注册', 'register_send_point', '500', '', '0', 'int', '0', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1025', '1', '', 'web_secret_key', 'c5ff645187eb7245d43178f20607920e456', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1026', '1', '', 'version_hash', '2017032058cf96a774a02', '', '0', 'string', '1', '2018-05-10 17:01:50');
INSERT INTO `video_site_configs` VALUES ('1027', '2', '', 'pay_repay_url', '/charge/callFailOrder', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1028', '2', '', 'activity_url', '/activitySend', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1029', '2', '', 'usr_real_cash', 'http://www.v2f.com/balance?', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1030', '2', '', 'flashimgstatic', 'http://www.v2f.com/convertstaticimg?', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1031', '2', '', 'activity_name', 'firstcharge', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1032', '2', '', 'mail_type', '[\"\\u767b\\u5f55\\u6ce8\\u518c\\u7c7b\",\"\\u5145\\u503c\\u63d0\\u6b3e\\u7c7b\",\"\\u4f18\\u60e0\\u6d3b\\u52a8\\u7c7b\",\"\\u5ba2\\u670d\\u670d\\u52a1\\u7c7b\",\"\\u6295\\u8bc9\\u610f\\u89c1\\u7c7b\"]', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1033', '2', '', 'channel_type', '[\"sex8\",\"\\u767e\\u5ea6\",\"\\u5927\\u5a92\\u4f53\",\"\\u8bba\\u575b\"]', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1034', '2', '', 'pay_type', '{\"1\":\"\\u94f6\\u884c\\u8f6c\\u8d26\",\"2\":\"\\u62bd\\u5956\",\"3\":\"paypal\",\"4\":\"\\u540e\\u53f0\\u5145\\u503c\",\"5\":\"\\u5145\\u503c\\u8d60\\u9001\",\"6\":\"\\u4efb\\u52a1\\u548c\\u7b7e\\u5230\\u5956\\u52b1\",\"7\":\"\\u8f6c\\u5e10\\u8bb0\\u5f55\",\"8\":\"\\u76f4\\u64ad\\u95f4\\u5151\\u6362\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1035', '2', '', 'agents_type', '[\"\\u4ee3\\u7406\\u5546\",\"\\u6e20\\u9053\\u5546\",\"A\"]', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1036', '2', '', 'recharge_type', '[\"\\u4eba\\u5de5\\u5145\\u503c\",\"\\u6d3b\\u52a8\\u5956\\u52b1\",\"\\u5e73\\u53f0\\u8d54\\u507f\"]', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1037', '2', '', 'image_ip', 'http://10.1.100.194:4869', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1038', '2', '', 'action_type', '{\"Edit\":\"\\u7f16\\u8f91\",\"Del\":\"\\u5220\\u9664\",\"Shutter\":\"\\u5c01\\u505c\\u8d26\\u53f7\",\"Reset\":\"\\u91cd\\u7f6e\\u7528\\u6237\\u5bc6\\u7801\",\"Add\":\"\\u6dfb\\u52a0\",\"Mesaage\":\"\\u7cfb\\u7edf\\u6d88\\u606f\",\"Default\":\"\\u8bbf\\u95ee\",\"Recharge\":\"\\u5145\\u503c\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1039', '2', '', 'host_type', '{\"1\":\"\\u666e\\u901a\\u827a\\u4eba\",\"2\":\"\\u4e2d\\u7ea7\\u827a\\u4eba\",\"3\":\"\\u9ad8\\u7ea7\\u827a\\u4eba\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1040', '2', '', 'set_menu', '{\"1\":\"\\u662f\",\"0\":\"\\u5426\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1041', '2', '', 'cache_file', './cache/cache.php', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1042', '2', '', 'pay_verify_message', '{\"serviceCode\":\"FC0029\",\"version\":\"1.0\",\"serviceType\":\"03\",\"signType\":\"md5\",\"sysPlatCode\":\"V\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1043', '2', '', 'xs_privitekey', '32ousdjf9343djjomvsdf2233dskdlfb', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1044', '2', '', 'xs_url', 'http://192.168.10.155:8086/fc_iface/service/g2p', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1045', '2', '', 'task_reward', '{\"points\":\"\\u94bb\\u77f3\",\"goods\":\"\\u9053\\u5177\",\"icon\":\"\\u5934\\u8854\",\"medals\":\"\\u52cb\\u7ae0\",\"top\":\"\\u7b49\\u7ea7\\u76f4\\u8fbe\",\"level\":\"\\u7b49\\u7ea7\\u63d0\\u5347\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1046', '2', '', 'task_type', '{\"check_email\":\"\\u9a8c\\u8bc1\\u90ae\\u7bb1\",\"invite\":\"\\u9996\\u6b21\\u9884\\u7ea6\",\"points\":\"\\u5145\\u503c\",\"openvip\":\"\\u9996\\u5f00\\u8d35\\u65cf\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1047', '2', '', 'color', '{\"#FFFFFF\":\"\\u767d\",\"#A5EAB5\":\"\\u6d45\\u7eff\",\"#0FA731\":\"\\u6df1\\u7eff\",\"#09E1F9\":\"\\u6d45\\u84dd\",\"#056290\":\"\\u6df1\\u84dd\",\"#FF0000\":\"\\u7ea2\",\"#FFED00\":\"\\u91d1\\u9ec4\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1048', '2', '', 'usr_keep_vip', 'http://www.v2f.com/charge/checkKeepVip', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1049', '2', '', 'recharge_channel_type', '{\"5\":\"\\u652f\\u4ed8\\u5b9d\",\"6\":\"\\u5fae\\u4fe1\\u652f\\u4ed8\",\"2\":\"QQ\\u94b1\\u5305\",\"4\":\"\\u7f51\\u94f6\\u8f6c\\u8d26\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1050', '2', '', 'originarr', '{\"51\":\"XO\\u9879\\u76ee\",\"11\":\"\\u76f4\\u63a5\\u95f4\",\"61\":\"L\\u9879\\u76ee\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1051', '2', '', 'discounttype', '{\"10\":\"\\u4e0d\\u6253\\u6298\",\"9\":\"9\\u6298\",\"8\":\"8\\u6298\",\"7\":\"7\\u6298\",\"6\":\"6\\u6298\",\"5\":\"5\\u6298\",\"4\":\"4\\u6298\",\"3\":\"3\\u6298\",\"2\":\"2\\u6298\",\"1\":\"1\\u6298\"}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1052', '2', '', 'check_socket_time', '330000', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1053', '2', '', 'video_url', '[\"\\/public\\/file\\/\"]', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1054', '2', '', 'pay', '{\"partner_names\":[\"DGPay\",\"RojoPay\",\"Liejie\"],\"channel_names\":[\"\\u652f\\u4ed8\\u5b9d\",\"\\u5fae\\u4fe1\",\"QQ\\u94b1\\u5305\",\"\\u7f51\\u94f6\\u652f\\u4ed8\",\"\\u4eba\\u5de5\\u8f6c\\u8d26\"]}', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1055', '2', '', 'redis_cli_ip_port', '10.1.100.192:6379', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1056', '2', '', 'mailer_transport', 'smtp', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1057', '2', '', 'mailer_host', '10.1.100.67', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1058', '2', '', 'mailer_user', null, '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1059', '2', '', 'mailer_password', null, '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1060', '2', '', 'verify_from_mail', 'verify@qq.com', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1061', '2', '', 'repass_from_mail', 'repass@qq.com', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1062', '2', '', 'locale', 'en', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1063', '2', '', 'secret', 'kalaaAaaaKoop', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1064', '2', '', 'debug_toolbar', '1', '', '0', 'bool', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1065', '2', '', 'debug_redirects', '0', '', '0', 'bool', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1066', '2', '', 'use_assetic_controller', '1', '', '0', 'bool', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1067', '2', '', 'remote_pic_url', 'http://10.1.100.194:4870', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1068', '2', '', 'vfphp_sign', '0c9ec123b8d0bca68dcd4a82822317ec', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1069', '2', '', 'des_encryt_key', 'iloveyvv', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1070', '2', '', 'vfphp_host_name', 'http://www.v2f.com', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1071', '2', '', 'vfjava_host_name', 'http://v.vphp.cn', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1072', '2', '', 'web_cdn_static', 'http://s.howsp.com', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1073', '2', '', 'pic_cdn_static', 'http://s.howsp.com', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1074', '2', '', 'pic_path', '/public/src/img/', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1075', '2', '', 'js_path', '/public/src/js/', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1076', '2', '', 'verify_code_status', '1', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1077', '2', '支付', 'pay_call_url', 'http://www.v2f.com/charge/moniCharge', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1078', '2', '', 'pay_privatekey', 'ShBq1NCMzc0Rv61c9g8e9hWzO4IWYMHcORbP4ya2iRzYcjGcxjB3tvmfjwaHHBY', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1079', '2', '', 'pay_service_code', 'FC0045', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1080', '2', '', 'pay_version', '1.3', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1081', '2', '', 'pay_service_type', '03', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1082', '2', '', 'pay_signtype', 'md5', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1083', '2', '', 'pay_sysplatcode', 'V2', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1084', '2', '', 'pay_charset', 'utf-8', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1085', '2', '', 'pay_notice_url', '/charge/notice', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1086', '2', '', 'pay_reback_url', '/charge/reback', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1087', '2', '', 'pay_log_file', '/tmp/video_charge_callback.log', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1088', '2', '', 'pay_find_code', 'FC0029', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1089', '2', '', 'back_plat_code', 'v2', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1090', '2', '', 'back_pay_call_url', 'http:///pay/g2p', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1091', '2', '', 'back_pay_notice_url', 'http://peach.co/charge/notice2', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1092', '2', '', 'back_pay_sign_key', 'P3v1e0NtpgHc6pjx', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1093', '2', '', 'activity_open', '1', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1094', '2', '', 'send_gift_expire', '2014-01-01/2015-03-01', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1095', '2', '', 'invitation_status', '0', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1096', '2', '', 'reg_send_status', '1', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1097', '2', '', 'first_recharge_status', '1', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1098', '2', '', 'lottry_status', '1', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1099', '2', '', 'login_send_point', '100', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1100', '2', '', 'service_online_type', '[\"\\u767b\\u9646\\u6ce8\\u518c\\u7c7b\",\"\\u5145\\u503c\\u63d0\\u6b3e\\u7c7b\",\"\\u4f18\\u60e0\\u6d3b\\u52a8\\u7c7b\",\"\\u5ba2\\u670d\\u670d\\u52a1\\u7c7b\",\"\\u6295\\u8bc9\\u610f\\u89c1\\u7c7b\"]', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1101', '2', '', 'register_send_point', '500', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1102', '2', '', 'web_secret_key', 'c5ff645187eb7245d43178f20607920e456', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1103', '2', '', 'syn_login_encode_key', 'c5sdfg5187e12345d43178f20607920e456', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1104', '2', '', 'login_domain', '[\"www.v2f.com\",\"peach.dev\",\"peach.front\"]', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1105', '2', '', 'default_domain', '[1]', '', '0', 'json', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1106', '2', '', 'register_domain', 'www.v2f.com', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1107', '2', '', 'user_points_min', '3000', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1108', '2', '', 'useanchorrtmp', '0', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1109', '2', '', 'rtmp_secret_key', '1234567812345678', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1110', '2', '', 'user_time_division', '2017-03-29 00:00:00', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1111', '2', '', 'nickname_price', '200', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1112', '2', '', 'open_web', '1', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1113', '2', '', 'xo_agent', '1', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1114', '2', '', 'l_agent', '2', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1115', '2', '', 'skip_captcha_login', '1', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1116', '2', '', 'skip_captcha_reg', '0', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1117', '2', '', 'h5', '1', '', '0', 'bool', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1118', '2', '', 'open_safe', '0', '', '0', 'bool', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1119', '2', '', 'hcertificate_start_expire', '14400', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1120', '2', '', 'pay_gd_key', 'BEJOUYYmE0Hd8gGkIdxden0iEPtATGOa', '', '0', 'string', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1121', '2', '', 'api_get_rtmp', '1', '', '0', 'int', '1', '2018-05-10 17:03:54');
INSERT INTO `video_site_configs` VALUES ('1126', '1', '测试', 'fgf', 'rwer', '', '0', 'string', '11', '2018-05-10 18:05:09');
INSERT INTO `video_site_configs` VALUES ('1128', '2', '', 'gamepop', '{\"1\":\"1\",\"2\":null,\"3\":\"0\"}', '', '0', 'string', '11', '2018-05-10 04:18:06');
INSERT INTO `video_site_configs` VALUES ('1129', '2', '', 'videoConfig', '3', '', '0', 'string', '11', '2018-05-17 22:34:31');
INSERT INTO `video_site_configs` VALUES ('1130', '2', '', 'imagesFocusConfig', '3', '', '0', 'string', '11', '2018-05-18 05:38:48');
INSERT INTO `video_site_configs` VALUES ('1131', '1', '', 'videoConfig', '5', '', '0', 'string', '11', '2018-05-21 23:43:40');
INSERT INTO `video_site_configs` VALUES ('1132', '2', '邮箱验证内容设置', 'email', '测试验证内容这是大V发大水发的说法', '', '0', 'string', '11', '2018-05-24 02:05:34');
INSERT INTO `video_site_configs` VALUES ('1133', '2', '下载地址', 'down_url', '{\"PC\":\"http://www.baidu.com\",\"ANDROID\":\"http://www.baidu.com\",\"IOS\":\"http://www.baidu.com\"}', '', '0', 'json', '11', '2018-05-24 19:05:40');
INSERT INTO `video_site_configs` VALUES ('1134', '2', '主播提成设置', 'ticheng_company_percent', '11', '', '0', 'float', '11', '2018-05-24 20:05:48');
INSERT INTO `video_site_configs` VALUES ('1135', '1', '一站cdn', 'cdn_host', 'http://10.1.100.157:81', '', '0', 'string', '11', '2018-05-25 08:37:11');
INSERT INTO `video_site_configs` VALUES ('1136', '2', '二站cdn', 'cdn_host', 'http://10.1.100.157:82', '', '0', 'string', '11', '2018-05-25 08:37:16');
INSERT INTO `video_site_configs` VALUES ('1137', '2', '签到管理', 'hsign_switch', 'a:2:{s:4:\"sign\";s:1:\"1\";s:10:\"introduced\";s:1:\"1\";}', '', '0', 'json', '11', '2018-05-28 15:05:27');
INSERT INTO `video_site_configs` VALUES ('1138', '2', '错误信息接口', 'errorinfopop', '{\"1\":\"1\",\"2\":0,\"3\":\"1\",\"4\":0,\"5\":\"dfg/lgk\"}', '', '0', 'json', '11', '2018-05-28 16:05:51');
INSERT INTO `video_site_configs` VALUES ('1139', '2', '', 'reflux_mail', '{\"subject\":\"士大夫士大夫敢死队风格1打发顺丰\",\"body\":\"<strong>手动阀手动阀阿发是大法师打发打发点阿斯顿发送到发的<\\/strong>\"}', '', '0', 'string', '11', '2018-05-28 18:21:45');
INSERT INTO `video_site_configs` VALUES ('1140', '1', '', 'reflux_mail', '{\"subject\":\"士大夫士大夫敢死队风格1打发顺丰\",\"body\":\"<strong>手动阀手动阀阿发是大法师打发打发点阿斯顿发送到发的<\\/strong>\"}', '', '0', 'string', '11', '2018-05-28 18:21:59');
