-- MySQL dump 10.13  Distrib 5.7.21, for osx10.13 (x86_64)
--
-- Host: localhost    Database: cpcccim
-- ------------------------------------------------------
-- Server version	5.7.21

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL COMMENT '登录名',
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `friend`
--

DROP TABLE IF EXISTS `friend`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `friend` (
  `fid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '拥有者id',
  `friend_uid` int(11) NOT NULL COMMENT '好友id',
  `remark` char(255) NOT NULL DEFAULT '' COMMENT '好友备注',
  `state` enum('chatting','hidden') NOT NULL DEFAULT 'hidden' COMMENT '状态 chatting:在会话列表中 hidden:不在会话列表中',
  `last_read_time` int(11) NOT NULL DEFAULT '0' COMMENT '上次读消息的时间',
  `last_mid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上一条消息的mid',
  `unread_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '未读消息数',
  PRIMARY KEY (`fid`),
  UNIQUE KEY `key` (`uid`,`friend_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=229 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group_member`
--

DROP TABLE IF EXISTS `group_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) NOT NULL COMMENT '群id',
  `uid` int(11) NOT NULL COMMENT '成员id',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `state` enum('chatting','hidden') NOT NULL DEFAULT 'hidden' COMMENT ' 状态 chatting:在会话列表中 hidden:不在会话列表中',
  `last_read_time` int(11) NOT NULL DEFAULT '0' COMMENT '上次读消息的时间',
  `forbidden` int(11) NOT NULL DEFAULT '0' COMMENT '禁言时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `gid-uid` (`gid`,`uid`),
  KEY `uid-state` (`uid`,`state`),
  KEY `gid-state` (`gid`,`state`)
) ENGINE=MyISAM AUTO_INCREMENT=256 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `gid` int(11) NOT NULL AUTO_INCREMENT,
  `groupname` varchar(60) NOT NULL COMMENT '群名称',
  `uid` int(11) NOT NULL COMMENT '拥有者id',
  `avatar` varchar(100) NOT NULL COMMENT '群头像',
  `state` enum('normal','disabled') DEFAULT 'normal' COMMENT 'normal：表示正常；disabled：表示解散',
  `timestamp` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`gid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=74 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message` (
  `mid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '消息id',
  `from` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '发起者uid/group_id',
  `to` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '接收者id，根据type不同，可能是用户uid，可能是群组id',
  `content` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '具体的消息数据',
  `type` enum('friend','group') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '消息类型，好友数据或群组数据',
  `sub_type` enum('message','notice','block','images','link','pay') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'message' COMMENT '子类型，'message消息','notice公告','block通知','images图片','link链接','pay支付'',
  `timestamp` int(11) unsigned NOT NULL COMMENT '消息时间戳',
  PRIMARY KEY (`mid`),
  KEY `timestamp` (`timestamp`),
`st_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `st_imgurl` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `st_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `st_linkicon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `st_linktext` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `st_paymoney` decimal(10,2) NOT NULL DEFAULT '0.00',
  `st_payamount` int(3) NOT NULL DEFAULT '0',
  KEY `from-type-subtype` (`from`,`type`,`sub_type`),
  KEY `to-type-subtype` (`to`,`type`,`sub_type`)
) ENGINE=MyISAM AUTO_INCREMENT=1347 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notice`
--

DROP TABLE IF EXISTS `notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notice` (
  `nid` int(11) NOT NULL AUTO_INCREMENT,
  `from` int(11) NOT NULL,
  `to` int(11) NOT NULL COMMENT '好友id 或 群主id',
  `type` enum('add_friend','join_group') NOT NULL COMMENT '消息类型加好友或者群组',
  `data` text COMMENT '相关数据',
  `operation` enum('not_operated','refuse','agree') NOT NULL DEFAULT 'not_operated' COMMENT '未操作,拒绝,同意',
  `timestamp` int(13) NOT NULL,
  PRIMARY KEY (`nid`),
  UNIQUE KEY `to-from-type` (`to`,`from`,`type`),
  KEY `to-operation` (`to`,`operation`)
) ENGINE=MyISAM AUTO_INCREMENT=144 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `setting`
--

DROP TABLE IF EXISTS `setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voice` enum('on','off') DEFAULT 'off' COMMENT '是否开启发送语音',
  `audio` enum('on','off') DEFAULT 'off' COMMENT '是否开启语音实时聊天',
  `video` enum('on','off') DEFAULT 'off' COMMENT '是否开启视频实时聊天',
  `prompt_tone` enum('on','off') DEFAULT 'on' COMMENT '是否开启提示音',
  `group_chat` enum('on','off') DEFAULT 'on' COMMENT '是否开启群聊',
  `private_chat` enum('on','off') DEFAULT 'on' COMMENT '是否开启私聊',
  `add_friend` enum('on','off') DEFAULT 'on' COMMENT '是否允许加好友',
  `create_group` enum('on','off') DEFAULT 'on' COMMENT '是否允许创建群组',
  `upload_file` enum('on','off') DEFAULT 'on' COMMENT '是否允许上传文件',
  `upload_img` enum('on','off') DEFAULT 'on' COMMENT '是否允许上传图片',
  `emoji` enum('on','off') DEFAULT 'on' COMMENT '是否允许发表情',
  `dirty_words` mediumtext COMMENT '过滤这些脏字，逗号分隔',
  `stranger_chat` enum('on','off') DEFAULT 'on' COMMENT '是否允许非好友聊天',
  `appkey` varchar(255) DEFAULT '' COMMENT '与cpccc-socket通讯的appkey',
  `ws_address` varchar(255) DEFAULT '' COMMENT '与cpccc-socket通讯的ws地址',
  `appsecret` varchar(255) DEFAULT '' COMMENT '与cpccc-socket通讯的appsecret',
  `api_address` varchar(255) DEFAULT '' COMMENT '与cpccc-socket通讯的api地址',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '登录名',
  `nickname` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '昵称',
  `avatar` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '头像',
  `sign` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '个性签名',
  `password` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码',
  `safepassword` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '交易密码',
  `state` enum('offline','online') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'offline' COMMENT 'offline: 离线 ,online : 在线',
  `logout_timestamp` int(11) NOT NULL COMMENT '离线时间',
  `timestamp` int(11) NOT NULL DEFAULT '0' COMMENT '注册时间',
  `account_state` enum('normal','disabled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '账户状态',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;



--
-- Table structure for table `user_session`
--
CREATE TABLE `user_session` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(100) NOT NULL,
  `phpsessid` varchar(255) NOT NULL,
  `uid` int(8) NOT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;



-- Dump completed on 2019-08-12 16:10:17
