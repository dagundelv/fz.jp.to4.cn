-- 基础会员中心数据库升级

-- 添加用户表的基础字段
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `real_name` varchar(50) COMMENT '真实姓名',
ADD COLUMN IF NOT EXISTS `birthday` date COMMENT '出生日期',
ADD COLUMN IF NOT EXISTS `bio` text COMMENT '个人简介',
ADD COLUMN IF NOT EXISTS `city` varchar(50) COMMENT '所在城市',
ADD COLUMN IF NOT EXISTS `last_login_at` timestamp NULL COMMENT '最后登录时间',
ADD COLUMN IF NOT EXISTS `login_count` int(11) DEFAULT 0 COMMENT '登录次数',
ADD COLUMN IF NOT EXISTS `profile_completeness` int(3) DEFAULT 0 COMMENT '资料完整度百分比',
ADD COLUMN IF NOT EXISTS `privacy_settings` json COMMENT '隐私设置',
ADD COLUMN IF NOT EXISTS `notification_settings` json COMMENT '通知设置';

-- 用户浏览历史表
CREATE TABLE IF NOT EXISTS `user_browse_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_type` enum('doctor','hospital','article','disease','question') NOT NULL,
  `item_id` int(11) NOT NULL,
  `view_count` int(11) DEFAULT 1 COMMENT '浏览次数',
  `first_viewed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_viewed_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_item` (`user_id`, `item_type`, `item_id`),
  KEY `user_id` (`user_id`),
  KEY `item_type_id` (`item_type`, `item_id`),
  KEY `last_viewed_at` (`last_viewed_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户健康档案表（可选信息）
CREATE TABLE IF NOT EXISTS `user_health_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `height` decimal(5,2) COMMENT '身高(cm)',
  `weight` decimal(5,2) COMMENT '体重(kg)',
  `blood_type` enum('A','B','AB','O','unknown') COMMENT '血型',
  `allergies` text COMMENT '过敏史',
  `chronic_diseases` text COMMENT '慢性病史',
  `family_history` text COMMENT '家族病史',
  `medications` text COMMENT '正在服用的药物',
  `emergency_contact` varchar(100) COMMENT '紧急联系人',
  `emergency_phone` varchar(20) COMMENT '紧急联系电话',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 更新现有用户的默认设置
UPDATE `users` SET 
  `privacy_settings` = JSON_OBJECT(
    'profile_public', true,
    'activity_public', true,
    'contact_public', false
  ),
  `notification_settings` = JSON_OBJECT(
    'email_notifications', true,
    'sms_notifications', true,
    'appointment_reminders', true,
    'question_answers', true,
    'system_messages', true
  ),
  `profile_completeness` = 20
WHERE `privacy_settings` IS NULL OR `notification_settings` IS NULL;