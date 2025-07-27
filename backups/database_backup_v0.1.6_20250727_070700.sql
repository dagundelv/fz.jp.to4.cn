-- MySQL dump 10.13  Distrib 5.7.44, for Linux (aarch64)
--
-- Host: 127.0.0.1    Database: fz_jp_to4_cn
-- ------------------------------------------------------
-- Server version	5.7.44-log

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
-- Table structure for table `answers`
--

DROP TABLE IF EXISTS `answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT '0',
  `like_count` int(11) DEFAULT '0',
  `is_best` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','deleted') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `user_id` (`user_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `answers_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `answers`
--

LOCK TABLES `answers` WRITE;
/*!40000 ALTER TABLE `answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_number` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `type` enum('expert','normal','emergency') DEFAULT 'normal',
  `patient_name` varchar(100) NOT NULL,
  `patient_phone` varchar(20) NOT NULL,
  `patient_age` int(3) DEFAULT NULL,
  `patient_gender` enum('male','female') DEFAULT NULL,
  `patient_idcard` varchar(20) DEFAULT NULL,
  `symptoms` text,
  `is_urgent` tinyint(1) DEFAULT '0',
  `fee` decimal(8,2) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `hospital_id` (`hospital_id`),
  KEY `appointment_date` (`appointment_date`),
  KEY `status` (`status`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,'TEST1753621914',1,1,1,'2025-07-28','09:00:00','normal','Test Patient','13800138000',30,'male',NULL,'Test symptoms',0,NULL,'pending','2025-07-27 13:11:54','2025-07-27 13:11:54'),(2,'AP2025072700018773',1,1,1,'2025-07-30','09:30:00','normal','张先生','13216774262',33,'male',NULL,'test',1,NULL,'pending','2025-07-27 13:14:08','2025-07-27 13:14:08'),(3,'AP2025072700033826',1,3,2,'2025-08-04','10:30:00','normal','张先生','13216774262',12,'male',NULL,'2222222222222222222222222',1,NULL,'pending','2025-07-27 13:49:18','2025-07-27 13:49:18');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `view_count` int(11) DEFAULT '0',
  `like_count` int(11) DEFAULT '0',
  `share_count` int(11) DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `publish_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `comment_count` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `publish_time` (`publish_time`),
  KEY `status` (`status`),
  KEY `is_featured` (`is_featured`),
  CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articles`
--

LOCK TABLES `articles` WRITE;
/*!40000 ALTER TABLE `articles` DISABLE KEYS */;
INSERT INTO `articles` VALUES (1,'春季养生：如何预防感冒和过敏','专家教你春季保健小贴士','<p>春季是感冒和过敏高发季节，正确的预防措施非常重要。</p><p>1. 加强锻炼，提高免疫力</p><p>2. 注意保暖，避免受凉</p><p>3. 均衡饮食，多吃新鲜蔬果</p><p>4. 保持室内空气流通</p><p>5. 避免接触过敏原</p>','春季养生需要注意预防感冒和过敏，通过合理的生活方式和饮食习惯来增强身体抵抗力。',1,'健康编辑部','健康医疗网',NULL,NULL,'春季养生,感冒预防,过敏,健康',1252,45,0,1,'2025-07-23 22:55:47','2025-07-25 07:55:47','2025-07-27 13:21:55','published',2),(2,'糖尿病患者的饮食管理指南','科学饮食，控制血糖','<p>糖尿病患者的饮食管理是控制病情的重要环节。</p><p><strong>饮食原则：</strong></p><p>1. 控制总热量摄入</p><p>2. 合理分配三大营养素</p><p>3. 定时定量进餐</p><p>4. 选择低血糖指数食物</p><p>5. 增加膳食纤维摄入</p>','糖尿病患者通过科学的饮食管理，可以有效控制血糖水平，改善生活质量。',1,'内分泌科专家','医学期刊',NULL,NULL,'糖尿病,饮食管理,血糖控制',891,32,0,0,'2025-07-24 22:55:47','2025-07-25 07:55:47','2025-07-25 20:34:19','published',2),(3,'高血压的预防与治疗新进展','了解最新的高血压管理方法','<p>高血压是常见的心血管疾病，需要综合管理。</p><p><strong>预防措施：</strong></p><p>1. 限制钠盐摄入</p><p>2. 保持理想体重</p><p>3. 规律运动</p><p>4. 戒烟限酒</p><p>5. 保持心理平衡</p>','高血压的预防和治疗需要生活方式干预和药物治疗相结合的综合管理策略。',1,'心血管专家','中华心血管病杂志',NULL,NULL,'高血压,心血管,预防治疗',567,23,0,0,'2025-07-25 22:55:47','2025-07-25 07:55:47','2025-07-25 13:32:38','published',1),(4,'世界卫生组织发布2024年全球健康趋势报告',NULL,'世界卫生组织（WHO）近日发布了2024年全球健康趋势报告，报告指出全球人均寿命继续延长，但慢性疾病发病率有所上升。\n\n报告重点内容包括：\n\n**主要趋势**\n1. 全球人均寿命达到73.4岁，比2020年提高了1.2岁\n2. 慢性疾病成为主要健康威胁，占死亡率的71%\n3. 心理健康问题显著增加，特别是在年轻群体中\n\n**新兴挑战**\n- 抗生素耐药性问题日益严重\n- 气候变化对健康的影响加剧\n- 数字化医疗的普及带来新的健康不平等\n\n**发展机遇**\n报告强调，人工智能和大数据在疾病预测和个性化治疗方面展现出巨大潜力。预计到2030年，精准医疗将覆盖更多疾病领域。\n\nWHO建议各国加大预防性医疗投入，建立更完善的初级卫生保健体系，以应对未来健康挑战。','最新报告显示，全球健康水平持续改善，但仍面临新的挑战。预防性医疗和个性化治疗成为未来发展重点。',26,NULL,NULL,NULL,NULL,'世界卫生组织,全球健康,预防医疗,慢性疾病',394,16,0,1,'2025-07-25 13:31:27','2025-07-25 13:31:27','2025-07-25 20:34:56','published',1),(5,'冬季流感高发期，专家教你科学预防',NULL,'进入冬季，流感病毒活跃度明显上升。据疾控中心监测数据显示，近期流感样病例报告数量呈上升趋势。\n\n**流感特点**\n- 传播速度快，传染性强\n- 症状较重，包括高热、头痛、肌肉酸痛\n- 老人、儿童、慢性病患者为高危人群\n\n**预防措施**\n1. **接种疫苗**：每年接种流感疫苗是最有效的预防措施\n2. **个人防护**：勤洗手，避免用手触摸口鼻眼\n3. **环境卫生**：保持室内通风，定期消毒\n4. **健康生活**：规律作息，适度运动，增强免疫力\n\n**出现症状怎么办**\n如出现发热、咳嗽、咽痛等症状，应及时就医，避免带病上班或上学，防止疫情扩散。\n\n专家特别提醒，接种流感疫苗后需要2-4周才能产生保护性抗体，建议在流感季节来临前提前接种。','随着气温下降，流感进入高发期。专家提醒，接种疫苗、勤洗手、佩戴口罩是预防流感的有效措施。',27,NULL,NULL,NULL,NULL,'流感预防,疫苗接种,冬季保健,传染病',471,22,0,1,'2025-07-25 13:31:27','2025-07-25 13:31:27','2025-07-27 02:56:01','published',1),(6,'中医养生：四季调理身体的智慧',NULL,'中医认为，人体应该顺应自然界的变化规律，根据春夏秋冬四季特点来调养身体。\n\n**春季养生：养肝为主**\n- 饮食：多吃绿色蔬菜，如菠菜、韭菜、芹菜\n- 运动：适合户外运动，如踏青、慢跑\n- 情志：保持心情舒畅，避免生气郁闷\n\n**夏季养生：养心为主**\n- 饮食：清淡为主，多吃苦味食物\n- 运动：避免剧烈运动，可选择游泳、太极\n- 作息：适当午休，避免熬夜\n\n**秋季养生：养肺为主**\n- 饮食：滋阴润燥，多吃白色食物如梨、银耳\n- 运动：登山、慢跑等有氧运动\n- 保健：注意保暖，预防感冒\n\n**冬季养生：养肾为主**\n- 饮食：温补为主，适当进补\n- 运动：室内运动为主，避免大汗\n- 作息：早睡晚起，保证充足睡眠\n\n中医养生强调整体观念，通过调整生活方式达到阴阳平衡，从而保持身体健康。','中医强调\"天人合一\"，根据四季变化调理身体。了解四季养生要点，让您在自然节律中保持健康。',28,NULL,NULL,NULL,NULL,'中医养生,四季调理,传统医学,健康生活',1834,43,0,0,'2025-07-25 13:31:27','2025-07-25 13:31:27','2025-07-25 13:32:38','published',1),(7,'家庭常用药物储存与使用注意事项',NULL,'家庭药箱是每个家庭的必需品，但如何正确储存和使用药物，很多人并不清楚。\n\n**储存环境要求**\n1. **温度**：大部分药物适宜在15-25℃保存\n2. **湿度**：避免潮湿，建议使用干燥剂\n3. **光线**：避免阳光直射，阴凉干燥处保存\n4. **分类**：内服药与外用药分开存放\n\n**常备药物清单**\n- **感冒药**：对乙酰氨基酚、布洛芬\n- **消化药**：健胃消食片、蒙脱石散\n- **外用药**：碘伏、创可贴、红花油\n- **急救药**：硝酸甘油（心脏病患者）\n\n**用药安全提醒**\n1. 仔细阅读说明书，按剂量服用\n2. 注意药物有效期，过期药物及时处理\n3. 服药期间观察身体反应\n4. 特殊人群（孕妇、儿童、老人）用药需谨慎\n\n**处理过期药物**\n过期药物不能随意丢弃，应送到指定回收点或医院处理，避免污染环境。\n\n建议每半年检查一次家庭药箱，及时更新过期药物，确保用药安全。','正确储存和使用家庭常备药物，能够在关键时刻发挥重要作用。了解用药安全知识，保护全家健康。',29,NULL,NULL,NULL,NULL,'用药安全,家庭药箱,药物储存,合理用药',509,69,0,0,'2025-07-25 13:31:27','2025-07-25 13:31:27','2025-07-25 13:32:38','published',1),(8,'基因治疗新突破：CAR-T细胞疗法治疗实体瘤取得进展',NULL,'近期，国际顶级医学期刊发表了关于CAR-T细胞疗法治疗实体瘤的最新研究成果，标志着癌症治疗进入新阶段。\n\n**什么是CAR-T细胞疗法**\nCAR-T细胞疗法是一种个性化的免疫治疗方法：\n1. 从患者体内提取T细胞\n2. 在实验室中对T细胞进行基因改造\n3. 改造后的细胞能够识别并攻击癌细胞\n4. 将改造后的细胞输回患者体内\n\n**突破性进展**\n- **治疗范围扩大**：从血液肿瘤扩展到实体瘤\n- **效果显著**：部分患者完全缓解率达到60%\n- **副作用减少**：新一代CAR-T技术副作用明显降低\n\n**临床试验结果**\n在一项针对胰腺癌的临床试验中：\n- 30名患者参与试验\n- 18名患者肿瘤缩小50%以上\n- 8名患者完全缓解\n\n**未来展望**\n专家预计，CAR-T细胞疗法将在未来5-10年内成为多种癌症的标准治疗方案。同时，治疗成本也有望随着技术成熟而大幅降低。\n\n这一突破为无数癌症患者带来了新的希望，也标志着精准医疗时代的到来。','最新研究显示，CAR-T细胞疗法在治疗实体瘤方面取得重要突破，为癌症治疗带来新希望。',30,NULL,NULL,NULL,NULL,'基因治疗,CAR-T细胞,癌症治疗,医学突破',1408,27,0,1,'2025-07-25 13:31:27','2025-07-25 13:31:27','2025-07-25 13:32:38','published',1),(9,'职场压力管理：如何在快节奏生活中保持心理健康',NULL,'在竞争激烈的职场环境中，压力已成为现代人普遍面临的挑战。适度的压力能够激发潜能，但过度的压力会影响身心健康。\n\n**识别压力信号**\n身体信号：\n- 头痛、肌肉紧张\n- 睡眠质量下降\n- 食欲改变\n- 容易感冒\n\n心理信号：\n- 焦虑、烦躁\n- 注意力难以集中\n- 情绪波动大\n- 缺乏动力\n\n**压力管理策略**\n\n**1. 时间管理**\n- 制定优先级，合理安排工作\n- 学会说\"不\"，避免过度承诺\n- 使用番茄工作法提高效率\n\n**2. 放松技巧**\n- 深呼吸练习：4-7-8呼吸法\n- 渐进性肌肉放松\n- 冥想和正念练习\n\n**3. 生活平衡**\n- 保证充足睡眠（7-8小时）\n- 规律运动，每周至少150分钟\n- 培养兴趣爱好\n\n**4. 社会支持**\n- 与家人朋友保持良好关系\n- 寻求专业心理咨询师帮助\n- 参加支持小组或社团活动\n\n**何时寻求专业帮助**\n如果压力症状持续2周以上，严重影响工作和生活，建议及时寻求专业心理咨询师或精神科医生的帮助。\n\n记住，关注心理健康与关注身体健康同样重要。','现代职场生活节奏快、压力大，学会有效的压力管理技巧，是维护心理健康的重要能力。',31,NULL,NULL,NULL,NULL,'职场压力,心理健康,压力管理,工作生活平衡',1790,14,0,0,'2025-07-25 13:31:27','2025-07-25 13:31:27','2025-07-27 05:46:22','published',0),(10,'糖尿病前期可逆转：生活方式干预的重要性',NULL,'糖尿病前期是指血糖水平高于正常但尚未达到糖尿病诊断标准的状态。好消息是，这个阶段是可以逆转的。\n\n**糖尿病前期的诊断标准**\n- 空腹血糖：6.1-6.9 mmol/L\n- 餐后2小时血糖：7.8-11.0 mmol/L\n- 糖化血红蛋白：5.7%-6.4%\n\n**高危人群**\n- 45岁以上\n- 超重或肥胖\n- 有糖尿病家族史\n- 高血压患者\n- 久坐少动的生活方式\n\n**生活方式干预方案**\n\n**1. 饮食调整**\n- 控制总热量摄入\n- 增加膳食纤维（每日25-35克）\n- 选择低升糖指数食物\n- 规律进餐，避免暴饮暴食\n\n推荐食物：\n- 全谷物：燕麦、糙米、全麦面包\n- 蔬菜：绿叶菜、十字花科蔬菜\n- 蛋白质：鱼类、豆类、瘦肉\n\n**2. 运动计划**\n- 有氧运动：每周150分钟中等强度\n- 抗阻运动：每周2-3次\n- 建议运动：快走、游泳、骑自行车\n\n**3. 体重管理**\n- 目标：减重5-10%\n- 健康减重速度：每周0.5-1公斤\n- BMI控制在18.5-23.9\n\n**4. 监测指标**\n- 每3-6个月检查血糖\n- 定期监测血压、血脂\n- 记录体重变化\n\n**成功案例**\n研究显示，通过生活方式干预：\n- 58%的糖尿病前期患者血糖恢复正常\n- 平均减重7%的参与者糖尿病发病风险降低58%\n\n预防糖尿病，关键在于行动。从今天开始，改变生活方式，守护健康未来。','研究表明，糖尿病前期通过合理的生活方式干预是可以逆转的。及早预防，远离糖尿病威胁。',27,NULL,NULL,NULL,NULL,'糖尿病预防,生活方式,健康饮食,运动健身',357,45,0,1,'2025-07-25 13:31:27','2025-07-25 13:31:27','2025-07-25 13:31:27','published',0),(11,'睡眠质量影响免疫力：科学睡眠指南',NULL,'良好的睡眠不仅能够消除疲劳，更是维护免疫系统正常功能的重要保障。研究表明，睡眠不足会显著降低机体免疫力。\n\n**睡眠与免疫的关系**\n- 深度睡眠时，免疫细胞活跃度增加\n- T细胞功能在睡眠中得到增强\n- 睡眠不足会降低疫苗效果\n- 慢性睡眠不足增加感染风险\n\n**理想睡眠时长**\n- 成年人：7-9小时\n- 青少年：8-10小时\n- 老年人：7-8小时\n- 儿童：9-11小时\n\n**提升睡眠质量的方法**\n\n**1. 建立睡眠仪式**\n- 固定作息时间，包括周末\n- 睡前1小时避免使用电子设备\n- 创造舒适的睡眠环境\n\n**2. 优化睡眠环境**\n- 室温：18-22℃\n- 湿度：50-60%\n- 光线：尽量暗黑\n- 噪音：保持安静\n\n**3. 饮食注意事项**\n- 睡前3小时避免大餐\n- 限制咖啡因摄入（下午2点后）\n- 避免睡前饮酒\n- 可适量食用含色氨酸食物\n\n**4. 放松技巧**\n- 深呼吸练习\n- 渐进性肌肉放松\n- 冥想或瑜伽\n- 听轻松音乐\n\n**睡眠障碍的处理**\n如果出现以下情况，建议就医：\n- 入睡困难超过30分钟\n- 夜间频繁醒来\n- 白天过度困倦\n- 打鼾伴呼吸暂停\n\n**改善睡眠的天然方法**\n- 规律运动（但避免睡前3小时内）\n- 接受阳光照射，调节生物钟\n- 睡前泡脚或洗热水澡\n- 适量补充镁元素\n\n记住，投资睡眠就是投资健康。从今晚开始，给自己一个高质量的睡眠吧！','充足优质的睡眠是维护免疫系统正常功能的重要因素。了解科学睡眠知识，提升睡眠质量。',28,NULL,NULL,NULL,NULL,'睡眠健康,免疫力,生物钟,睡眠质量',154,93,0,0,'2025-07-25 13:31:27','2025-07-25 13:31:27','2025-07-25 13:31:27','published',0);
/*!40000 ALTER TABLE `articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT '0',
  `level` int(2) DEFAULT '1',
  `sort_order` int(11) DEFAULT '0',
  `icon` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  `slug` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `level` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'内科',0,1,1,NULL,'内科系统疾病','2025-07-25 07:29:40','active','category-1'),(2,'外科',0,1,2,NULL,'外科系统疾病','2025-07-25 07:29:40','active','category-2'),(3,'妇产科',0,1,3,NULL,'妇科和产科疾病','2025-07-25 07:29:40','active','category-3'),(4,'儿科',0,1,4,NULL,'儿童疾病','2025-07-25 07:29:40','active','category-4'),(5,'骨科',0,1,5,NULL,'骨骼和关节疾病','2025-07-25 07:29:40','active','category-5'),(6,'皮肤科',0,1,6,NULL,'皮肤疾病','2025-07-25 07:29:40','active','category-6'),(7,'眼科',0,1,7,NULL,'眼部疾病','2025-07-25 07:29:40','active','category-7'),(8,'耳鼻喉科',0,1,8,NULL,'耳鼻喉疾病','2025-07-25 07:29:40','active','category-8'),(9,'口腔科',0,1,9,NULL,'口腔疾病','2025-07-25 07:29:40','active','category-9'),(10,'精神科',0,1,10,NULL,'精神心理疾病','2025-07-25 07:29:40','active','category-10'),(11,'心内科',1,2,1,NULL,'心血管疾病','2025-07-25 07:29:40','active','category-11'),(12,'呼吸内科',1,2,2,NULL,'呼吸系统疾病','2025-07-25 07:29:40','active','category-12'),(13,'消化内科',1,2,3,NULL,'消化系统疾病','2025-07-25 07:29:40','active','category-13'),(14,'内分泌科',1,2,4,NULL,'内分泌疾病','2025-07-25 07:29:40','active','category-14'),(15,'肾内科',1,2,5,NULL,'肾脏疾病','2025-07-25 07:29:40','active','category-15'),(16,'神经内科',1,2,6,NULL,'神经系统疾病','2025-07-25 07:29:40','active','category-16'),(17,'血液科',1,2,7,NULL,'血液疾病','2025-07-25 07:29:40','active','category-17'),(18,'风湿免疫科',1,2,8,NULL,'风湿免疫疾病','2025-07-25 07:29:40','active','category-18'),(19,'普外科',2,2,1,NULL,'普通外科','2025-07-25 07:29:40','active','category-19'),(20,'胸外科',2,2,2,NULL,'胸部外科','2025-07-25 07:29:40','active','category-20'),(21,'神经外科',2,2,3,NULL,'神经外科','2025-07-25 07:29:40','active','category-21'),(22,'泌尿外科',2,2,4,NULL,'泌尿系统外科','2025-07-25 07:29:40','active','category-22'),(23,'肝胆外科',2,2,5,NULL,'肝胆外科','2025-07-25 07:29:40','active','category-23'),(24,'血管外科',2,2,6,NULL,'血管外科','2025-07-25 07:29:40','active','category-24'),(25,'整形外科',2,2,7,NULL,'整形美容外科','2025-07-25 07:29:40','active','category-25'),(26,'健康头条',0,1,1,NULL,NULL,'2025-07-25 13:31:05','active','category-26'),(27,'疾病预防',0,1,2,NULL,NULL,'2025-07-25 13:31:05','active','category-27'),(28,'养生保健',0,1,3,NULL,NULL,'2025-07-25 13:31:05','active','category-28'),(29,'用药指南',0,1,4,NULL,NULL,'2025-07-25 13:31:05','active','category-29'),(30,'医学前沿',0,1,5,NULL,NULL,'2025-07-25 13:31:05','active','category-30'),(31,'心理健康',0,1,6,NULL,NULL,'2025-07-25 13:31:05','active','category-31'),(32,'妇科',0,1,3,NULL,NULL,'2025-07-25 13:36:16','active','category-32'),(33,'心理科',0,1,7,NULL,NULL,'2025-07-25 13:36:16','active','category-33');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_type` enum('article','question','answer','doctor','hospital') NOT NULL,
  `target_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT '0',
  `content` text NOT NULL,
  `like_count` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','deleted') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `target_type_id` (`target_type`,`target_id`),
  KEY `parent_id` (`parent_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` VALUES (10,1,'article',1,0,'非常实用的春季养生知识，谢谢分享！',5,'2025-07-25 13:32:34','active'),(11,2,'article',1,0,'按照这些方法预防感冒，确实有效果。',3,'2025-07-25 13:32:34','active'),(12,1,'article',2,0,'作为糖尿病患者，这篇文章太有用了！',8,'2025-07-25 13:32:34','active'),(13,3,'article',2,0,'饮食管理确实很重要，要严格控制。',4,'2025-07-25 13:32:34','active'),(14,2,'article',3,0,'高血压治疗的新进展很有意思。',2,'2025-07-25 13:32:34','active'),(15,4,'article',4,0,'WHO的报告很权威，值得关注。',6,'2025-07-25 13:32:34','active'),(16,1,'article',5,0,'流感预防知识很全面，收藏了。',7,'2025-07-25 13:32:34','active'),(17,2,'article',6,0,'中医养生确实博大精深。',5,'2025-07-25 13:32:34','active'),(18,3,'article',7,0,'用药安全知识很重要，家家户户都应该了解。',9,'2025-07-25 13:32:34','active'),(19,4,'article',8,0,'基因治疗的发展真是太神奇了！',12,'2025-07-25 13:32:34','active');
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diseases`
--

DROP TABLE IF EXISTS `diseases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `view_count` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `name` (`name`),
  CONSTRAINT `diseases_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diseases`
--

LOCK TABLES `diseases` WRITE;
/*!40000 ALTER TABLE `diseases` DISABLE KEYS */;
INSERT INTO `diseases` VALUES (1,'高血压',11,'原发性高血压,高血压病','头痛、头晕、心悸、胸闷、乏力等症状，部分患者无明显症状','遗传因素、环境因素、生活方式、年龄、性别等多种因素综合作用','血压测量、心电图、超声心动图、眼底检查、实验室检查等','生活方式干预和药物治疗相结合，包括饮食控制、运动、戒烟限酒、降压药物等','健康饮食、适量运动、控制体重、限制钠盐、戒烟限酒、保持心理平衡','定期监测血压、按时服药、定期复查、注意生活细节',NULL,NULL,NULL,2341,'2025-07-25 07:55:47','2025-07-25 13:28:28','active'),(2,'糖尿病',14,'糖尿病,DM','多饮、多尿、多食、体重下降，疲乏无力、视力模糊等','遗传因素和环境因素共同作用，包括胰岛素分泌不足或作用缺陷','空腹血糖、餐后血糖、糖化血红蛋白、口服葡萄糖耐量试验等','饮食治疗、运动治疗、药物治疗、血糖监测、糖尿病教育','健康饮食、适量运动、控制体重、定期体检、避免过度紧张','血糖监测、足部护理、眼部检查、肾功能监测',NULL,NULL,NULL,1892,'2025-07-25 07:55:47','2025-07-25 13:44:30','active');
/*!40000 ALTER TABLE `diseases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `rating` decimal(3,2) DEFAULT '0.00',
  `view_count` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `hospital_id` (`hospital_id`),
  KEY `category_id` (`category_id`),
  KEY `title` (`title`),
  CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES (1,'张伟明','主任医师',1,11,'冠心病、高血压、心律失常的诊治，心脏介入治疗','北京医科大学博士','从事心血管内科临床工作30余年',NULL,'在冠心病介入治疗方面有丰富经验，已完成各类心脏介入手术5000余例。',NULL,NULL,NULL,NULL,NULL,4.80,5,'2025-07-25 07:55:47','2025-07-27 13:33:06','active'),(2,'李小华','副主任医师',1,12,'肺部感染、慢性阻塞性肺疾病、哮喘的诊治','协和医科大学硕士','从事呼吸内科临床工作20年',NULL,'擅长各种呼吸系统疾病的诊断和治疗。',NULL,NULL,NULL,NULL,NULL,4.60,0,'2025-07-25 07:55:47','2025-07-25 07:55:47','active'),(3,'王建国','主任医师',2,17,'白血病、淋巴瘤、贫血的诊治，造血干细胞移植','上海交通大学博士','从事血液病临床工作25年',NULL,'国内知名血液病专家，在造血干细胞移植领域有突出贡献。',NULL,NULL,NULL,NULL,NULL,4.90,1,'2025-07-25 07:55:47','2025-07-25 08:05:51','active'),(4,'陈美丽','主治医师',3,13,'胃肠疾病、肝病的诊治，内镜诊疗技术','中山大学硕士','从事消化内科工作15年',NULL,'擅长各种消化系统疾病的内镜诊断和治疗。',NULL,NULL,NULL,NULL,NULL,4.50,1,'2025-07-25 07:55:47','2025-07-25 13:13:55','active');
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_type` enum('article','doctor','hospital','disease','question') NOT NULL,
  `target_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_target` (`user_id`,`target_type`,`target_id`),
  KEY `user_id` (`user_id`),
  KEY `target_type_id` (`target_type`,`target_id`),
  CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites`
--

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hospitals`
--

DROP TABLE IF EXISTS `hospitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `rating` decimal(3,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `province` (`province`),
  KEY `city` (`city`),
  KEY `level` (`level`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hospitals`
--

LOCK TABLES `hospitals` WRITE;
/*!40000 ALTER TABLE `hospitals` DISABLE KEYS */;
INSERT INTO `hospitals` VALUES (1,'北京协和医院','三甲','综合医院','北京市','北京','东城区','北京市东城区东单帅府园1号','010-69156114','https://www.pumch.cn','北京协和医院是中国医学科学院的附属医院，是国家卫生健康委指定的全国疑难重症诊治指导中心，也是最早承担外宾医疗任务的医院之一，以学科齐全、技术力量雄厚、特色专科突出、多学科综合优势强大享誉海内外。','内科、外科、妇产科、儿科、神经科、心血管科、呼吸科','磁共振、CT、PET-CT、直线加速器等先进医疗设备',NULL,NULL,NULL,4.80,'2025-07-25 07:55:47','2025-07-25 07:55:47','active'),(2,'上海交通大学医学院附属瑞金医院','三甲','综合医院','上海市','上海','黄浦区','上海市黄浦区瑞金二路197号','021-64370045','https://www.rjh.com.cn','瑞金医院建于1907年，是一所集医疗、教学、科研为一体的三级甲等综合性医院，有着百年的深厚底蕴。','血液科、内分泌科、心血管科、神经外科、烧伤整形科','达芬奇手术机器人、3.0T磁共振、256排CT等',NULL,NULL,NULL,4.70,'2025-07-25 07:55:47','2025-07-25 07:55:47','active'),(3,'广州市第一人民医院','三甲','综合医院','广东省','广州','越秀区','广州市越秀区盘福路1号','020-81048888','http://www.gzph.com','广州市第一人民医院创建于1899年，是广州地区集医疗、教学、科研、预防、保健、康复于一体的三级甲等综合医院。','心血管内科、神经内科、消化内科、呼吸内科','磁共振、螺旋CT、数字血管造影机等',NULL,NULL,NULL,4.50,'2025-07-25 07:55:47','2025-07-25 07:55:47','active'),(4,'深圳市人民医院','三甲','综合医院','广东省','深圳','罗湖区','深圳市罗湖区东门北路1017号','0755-25533018','http://www.szph.com','深圳市人民医院始建于1946年，是深圳市最大的三级甲等综合性医院。','心脏中心、肿瘤科、神经科、急诊科','PET-CT、磁共振、直线加速器等',NULL,NULL,NULL,4.40,'2025-07-25 07:55:47','2025-07-25 07:55:47','active'),(5,'四川大学华西医院','三甲','综合医院','四川省','成都','武侯区','四川省成都市武侯区国学巷37号','028-85422286','https://www.cd120.com','四川大学华西医院是中国西部疑难危急重症诊疗的国家级中心，也是世界规模第一的综合性单点医院。','急诊医学、麻醉科、临床药学、病理科','质子重离子治疗系统、达芬奇机器人等',NULL,NULL,NULL,4.90,'2025-07-25 07:55:47','2025-07-25 07:55:47','active');
/*!40000 ALTER TABLE `hospitals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_answers`
--

DROP TABLE IF EXISTS `qa_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qa_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `like_count` int(11) DEFAULT '0',
  `is_accepted` tinyint(4) DEFAULT '0',
  `status` enum('published','hidden') COLLATE utf8mb4_unicode_ci DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_best` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_doctor_id` (`doctor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_answers`
--

LOCK TABLES `qa_answers` WRITE;
/*!40000 ALTER TABLE `qa_answers` DISABLE KEYS */;
INSERT INTO `qa_answers` VALUES (1,1,1,NULL,'根据您描述的症状，建议您及时到医院相关科室就诊，医生会根据具体情况给出专业的诊断和治疗建议。请不要过于担心，及时就医是最好的选择。',9,0,'published','2025-07-25 13:23:25','2025-07-26 01:14:29',0),(2,2,1,NULL,'根据您描述的症状，建议您及时到医院相关科室就诊，医生会根据具体情况给出专业的诊断和治疗建议。请不要过于担心，及时就医是最好的选择。',6,0,'published','2025-07-25 13:23:25','2025-07-26 01:14:29',0),(3,3,1,NULL,'根据您描述的症状，建议您及时到医院相关科室就诊，医生会根据具体情况给出专业的诊断和治疗建议。请不要过于担心，及时就医是最好的选择。',10,0,'published','2025-07-25 13:23:25','2025-07-26 01:14:29',0),(4,1,1,1,'根据您描述的头痛症状，建议您先到神经内科就诊。经常性头痛可能与多种原因有关：\n\n1. 紧张性头痛：工作压力大、睡眠不足\n2. 偏头痛：遗传因素、激素变化\n3. 高血压引起的头痛\n4. 颈椎病导致的头痛\n\n建议您：\n- 保持规律作息，充足睡眠\n- 减少工作压力，学会放松\n- 如果头痛持续或加重，及时就医检查\n\n希望我的建议对您有帮助。\n\n——张伟明 主任医师',15,0,'published','2025-07-25 13:36:42','2025-07-26 01:14:29',0),(5,2,2,2,'孩子发烧38.5度需要及时处理，但不一定需要马上去医院。建议您：\n\n**立即处理：**\n1. 物理降温：温水擦浴、减少衣物\n2. 多喝水，保持充足水分\n3. 观察孩子精神状态\n\n**需要立即就医的情况：**\n- 体温超过39度\n- 精神萎靡、嗜睡\n- 呼吸困难\n- 持续呕吐\n- 出现皮疹\n\n**可以在家观察：**\n- 精神状态良好\n- 能正常进食饮水\n- 体温在38.5度以下\n\n如果发烧持续超过3天，建议到儿科就诊检查。\n\n——李小华 副主任医师',22,0,'published','2025-07-25 13:36:42','2025-07-26 01:14:29',0),(6,3,3,3,'胸闷气短确实可能是心脏病的症状之一，但也可能由其他原因引起。建议您重视这个症状：\n\n**心脏相关原因：**\n- 冠心病、心肌梗死\n- 心律不齐\n- 心肌炎\n- 心力衰竭\n\n**其他可能原因：**\n- 肺部疾病（哮喘、肺炎）\n- 焦虑、紧张情绪\n- 贫血\n- 胃食管反流\n\n**建议检查：**\n1. 心电图、心脏彩超\n2. 胸部X光片\n3. 血常规、心肌酶\n\n**紧急就医指征：**\n- 胸痛伴出汗\n- 严重呼吸困难\n- 晕厥\n\n建议您尽快到心内科就诊，明确诊断。\n\n——王建国 主任医师',18,0,'published','2025-07-25 13:36:42','2025-07-26 01:14:29',0);
/*!40000 ALTER TABLE `qa_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_questions`
--

DROP TABLE IF EXISTS `qa_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qa_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `view_count` int(11) DEFAULT '0',
  `answer_count` int(11) DEFAULT '0',
  `like_count` int(11) DEFAULT '0',
  `is_urgent` tinyint(4) DEFAULT '0',
  `status` enum('pending','published','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  FULLTEXT KEY `idx_title_content` (`title`,`content`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_questions`
--

LOCK TABLES `qa_questions` WRITE;
/*!40000 ALTER TABLE `qa_questions` DISABLE KEYS */;
INSERT INTO `qa_questions` VALUES (1,1,1,'经常头痛应该看什么科室？','我最近经常头痛，特别是工作压力大的时候，请问应该挂什么科室？','头痛,神经科,压力',13,2,0,0,'published','2025-07-25 13:23:25','2025-07-27 02:55:52'),(2,1,4,'孩子发烧38.5度需要马上去医院吗？','6岁孩子突然发烧38.5度，精神状态还可以，需要马上去医院吗？','儿童,发烧,儿科',95,2,0,0,'published','2025-07-25 13:23:25','2025-07-27 13:21:45'),(3,1,1,'胸闷气短是心脏病的症状吗？','最近总是感觉胸闷气短，尤其是爬楼梯的时候，这是心脏病的症状吗？','胸闷,气短,心脏病,心血管',23,2,0,0,'published','2025-07-25 13:23:25','2025-07-26 01:14:11'),(5,1,1,'问题问题问题','问题1问题1问题1问题1问题1问题1问题1',NULL,1,0,0,1,'published','2025-07-27 12:47:22','2025-07-27 12:47:22');
/*!40000 ALTER TABLE `qa_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT '0',
  `view_count` int(11) DEFAULT '0',
  `answer_count` int(11) DEFAULT '0',
  `like_count` int(11) DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `best_answer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','closed','deleted') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `created_at` (`created_at`),
  KEY `status` (`status`),
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_keywords`
--

DROP TABLE IF EXISTS `search_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `search_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL,
  `search_count` int(11) DEFAULT '1',
  `result_count` int(11) DEFAULT '0',
  `category` enum('doctor','hospital','disease','article','question') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keyword` (`keyword`),
  KEY `search_count` (`search_count`),
  KEY `category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_keywords`
--

LOCK TABLES `search_keywords` WRITE;
/*!40000 ALTER TABLE `search_keywords` DISABLE KEYS */;
INSERT INTO `search_keywords` VALUES (2,'11',3,0,NULL,'2025-07-27 05:11:21','2025-07-27 05:26:03'),(3,'医生',43,1,NULL,'2025-07-27 05:35:08','2025-07-27 05:47:16'),(4,'心脏',6,3,NULL,'2025-07-27 05:35:30','2025-07-27 05:47:36'),(5,'北京',1,0,NULL,'2025-07-27 05:35:30','2025-07-27 05:35:30'),(6,'发烧',1,0,NULL,'2025-07-27 05:35:30','2025-07-27 05:35:30'),(9,'66',4,0,NULL,'2025-07-27 05:38:03','2025-07-27 05:38:10');
/*!40000 ALTER TABLE `search_keywords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) DEFAULT NULL,
  `setting_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_favorites`
--

DROP TABLE IF EXISTS `user_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_type` enum('doctor','hospital','article','disease','question') COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`item_type`,`item_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_item` (`item_type`,`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_favorites`
--

LOCK TABLES `user_favorites` WRITE;
/*!40000 ALTER TABLE `user_favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `role` enum('user','admin') DEFAULT 'user',
  `real_name` varchar(100) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `bio` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@health.com','$2y$10$PDE3QBdWl1eRtM.8BFxcJ.HSAuGPr3llRNCPoHeLMEruObS22w9re',NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-25 13:32:23','2025-07-27 17:11:19','ed66551c7c50ddf5d1901f2db9d394bca47d6e8633374247af2be2c692c39fe8','2025-07-27 02:11:20','active','admin',NULL,NULL,NULL,NULL),(2,'user1','user1@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-25 13:32:23',NULL,NULL,'2025-07-25 13:32:23','active','user',NULL,NULL,NULL,NULL),(3,'user2','user2@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-25 13:32:23',NULL,NULL,'2025-07-25 13:32:23','active','user',NULL,NULL,NULL,NULL),(4,'healthlover','health@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-25 13:32:23',NULL,NULL,'2025-07-25 13:32:23','active','user',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'fz_jp_to4_cn'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-27  7:07:00
