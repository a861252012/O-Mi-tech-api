/*
Navicat MySQL Data Transfer

Source Server         : dev.vvv162.主
Source Server Version : 50721
Source Host           : 10.1.100.162:3306
Source Database       : vvv

Target Server Type    : MYSQL
Target Server Version : 50721
File Encoding         : 65001

Date: 2018-06-05 16:58:05
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `video_site_domains`
-- ----------------------------
DROP TABLE IF EXISTS `video_site_domains`;
CREATE TABLE `video_site_domains` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL,
  `domain` varchar(255) NOT NULL COMMENT '域名',
  `desc` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态',
  `created` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`) USING BTREE,
  KEY `domain` (`domain`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of video_site_domains
-- ----------------------------
INSERT INTO `video_site_domains` VALUES ('1', '1', 'www.gooleg1.com', '测试1', '1', '2018-05-10 12:05:32');
INSERT INTO `video_site_domains` VALUES ('5', '2', 'www.baidu.com', '测试', '1', '2018-06-01 16:06:38');
INSERT INTO `video_site_domains` VALUES ('6', '1', 'www.baidu.com', '123', '1', '2018-06-04 11:06:17');
