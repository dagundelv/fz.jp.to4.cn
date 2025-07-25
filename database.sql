-- 健康医疗网站数据库结构
-- Database: fz_jp_to4_cn

-- 用户表
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `interests` text COMMENT '关注的科室和疾病,JSON格式',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive','banned') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 分类表（科室分类）
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `level` int(2) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `icon` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 医院表
CREATE TABLE `hospitals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `level` enum('三甲','三乙','二甲','二乙','一甲','一乙','专科') NOT NULL,
  `type` enum('综合医院','专科医院','中医医院','妇幼保健院','其他') DEFAULT '综合医院',
  `province` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `district` varchar(50) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `introduction` text,
  `specialties` text COMMENT '特色专科',
  `equipment` text COMMENT '医疗设备',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `province` (`province`),
  KEY `city` (`city`),
  KEY `level` (`level`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 医生表
CREATE TABLE `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `title` enum('主任医师','副主任医师','主治医师','住院医师','医师') NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `specialties` text COMMENT '擅长领域',
  `education` varchar(255) DEFAULT NULL,
  `experience` text COMMENT '工作经历',
  `achievements` text COMMENT '学术成果',
  `introduction` text,
  `avatar` varchar(255) DEFAULT NULL,
  `schedule` text COMMENT '出诊时间,JSON格式',
  `consultation_fee` decimal(8,2) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `hospital_id` (`hospital_id`),
  KEY `category_id` (`category_id`),
  KEY `title` (`title`),
  FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 疾病表
CREATE TABLE `diseases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `alias` text COMMENT '别名',
  `symptoms` text COMMENT '症状',
  `causes` text COMMENT '病因',
  `diagnosis` text COMMENT '诊断方法',
  `treatment` text COMMENT '治疗方法',
  `prevention` text COMMENT '预防措施',
  `care` text COMMENT '护理康复',
  `complications` text COMMENT '并发症',
  `prognosis` text COMMENT '预后',
  `image` varchar(255) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `name` (`name`),
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 新闻文章表
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `content` longtext NOT NULL,
  `summary` text,
  `category_id` int(11) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `source_url` varchar(255) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  `share_count` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `publish_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `publish_time` (`publish_time`),
  KEY `status` (`status`),
  KEY `is_featured` (`is_featured`),
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 问答表
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `answer_count` int(11) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `best_answer_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','closed','deleted') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `created_at` (`created_at`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 回答表
CREATE TABLE `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  `is_best` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','deleted') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `user_id` (`user_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 评论表（通用）
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_type` enum('article','question','answer','doctor','hospital') NOT NULL,
  `target_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `content` text NOT NULL,
  `like_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','deleted') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `target_type_id` (`target_type`,`target_id`),
  KEY `parent_id` (`parent_id`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 预约挂号表
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `type` enum('expert','normal','emergency') DEFAULT 'normal',
  `patient_name` varchar(100) NOT NULL,
  `patient_phone` varchar(20) NOT NULL,
  `patient_idcard` varchar(20) DEFAULT NULL,
  `symptoms` text,
  `fee` decimal(8,2) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `hospital_id` (`hospital_id`),
  KEY `appointment_date` (`appointment_date`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户收藏表
CREATE TABLE `favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_type` enum('article','doctor','hospital','disease','question') NOT NULL,
  `target_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_target` (`user_id`,`target_type`,`target_id`),
  KEY `user_id` (`user_id`),
  KEY `target_type_id` (`target_type`,`target_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 搜索热词表
CREATE TABLE `search_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL,
  `search_count` int(11) DEFAULT 1,
  `result_count` int(11) DEFAULT 0,
  `category` enum('doctor','hospital','disease','article','question') DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keyword` (`keyword`),
  KEY `search_count` (`search_count`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 初始化分类数据
INSERT INTO `categories` (`id`, `name`, `parent_id`, `level`, `sort_order`, `description`) VALUES
(1, '内科', 0, 1, 1, '内科系统疾病'),
(2, '外科', 0, 1, 2, '外科系统疾病'),
(3, '妇产科', 0, 1, 3, '妇科和产科疾病'),
(4, '儿科', 0, 1, 4, '儿童疾病'),
(5, '骨科', 0, 1, 5, '骨骼和关节疾病'),
(6, '皮肤科', 0, 1, 6, '皮肤疾病'),
(7, '眼科', 0, 1, 7, '眼部疾病'),
(8, '耳鼻喉科', 0, 1, 8, '耳鼻喉疾病'),
(9, '口腔科', 0, 1, 9, '口腔疾病'),
(10, '精神科', 0, 1, 10, '精神心理疾病'),

-- 内科细分
(11, '心内科', 1, 2, 1, '心血管疾病'),
(12, '呼吸内科', 1, 2, 2, '呼吸系统疾病'),
(13, '消化内科', 1, 2, 3, '消化系统疾病'),
(14, '内分泌科', 1, 2, 4, '内分泌疾病'),
(15, '肾内科', 1, 2, 5, '肾脏疾病'),
(16, '神经内科', 1, 2, 6, '神经系统疾病'),
(17, '血液科', 1, 2, 7, '血液疾病'),
(18, '风湿免疫科', 1, 2, 8, '风湿免疫疾病'),

-- 外科细分
(19, '普外科', 2, 2, 1, '普通外科'),
(20, '胸外科', 2, 2, 2, '胸部外科'),
(21, '神经外科', 2, 2, 3, '神经外科'),
(22, '泌尿外科', 2, 2, 4, '泌尿系统外科'),
(23, '肝胆外科', 2, 2, 5, '肝胆外科'),
(24, '血管外科', 2, 2, 6, '血管外科'),
(25, '整形外科', 2, 2, 7, '整形美容外科');