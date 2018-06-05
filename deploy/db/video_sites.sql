/*
Navicat MySQL Data Transfer

Source Server         : dev.vvv162.主
Source Server Version : 50721
Source Host           : 10.1.100.162:3306
Source Database       : vvv

Target Server Type    : MYSQL
Target Server Version : 50721
File Encoding         : 65001

Date: 2018-06-05 16:58:11
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `video_sites`
-- ----------------------------
DROP TABLE IF EXISTS `video_sites`;
CREATE TABLE `video_sites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '站点名称',
  `isself` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否自主运营',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '站点状态：1开0关(预留字段)',
  `created` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of video_sites
-- ----------------------------
INSERT INTO `video_sites` VALUES ('1', '第一坊', '0', '1', '2018-05-04 13:50:06');
INSERT INTO `video_sites` VALUES ('2', '蜜桃', '0', '1', '2018-05-04 13:50:06');
