/*
 Navicat Premium Data Transfer

 Source Server         : datawarehouse
 Source Server Type    : MySQL
 Source Server Version : 50619
 Source Host           : 83.96.199.51
 Source Database       : reg_scheduler

 Target Server Type    : MySQL
 Target Server Version : 50619
 File Encoding         : utf-8

 Date: 10/01/2014 11:48:34 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `droplet`
-- ----------------------------
DROP TABLE IF EXISTS `droplet`;
CREATE TABLE `droplet` (
  `id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` enum('on','off','archived','?','processing') DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Records of `droplet`
-- ----------------------------
BEGIN;
INSERT INTO `droplet` VALUES (null, 'rstudio', 'archived', null, '2014-10-01 11:27:49');
COMMIT;

-- ----------------------------
--  Table structure for `queue`
-- ----------------------------
DROP TABLE IF EXISTS `queue`;
CREATE TABLE `queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dbname` varchar(255) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('queued','running','success','failed') NOT NULL DEFAULT 'queued',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4;



SET FOREIGN_KEY_CHECKS = 1;
