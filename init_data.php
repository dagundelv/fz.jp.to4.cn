<?php
require_once 'includes/init.php';

echo "开始初始化测试数据...\n";

try {
    // 清空现有数据（小心使用）
    $db->query("DELETE FROM comments WHERE id > 0");
    $db->query("DELETE FROM articles WHERE id > 0"); 
    $db->query("DELETE FROM doctors WHERE id > 0");
    $db->query("DELETE FROM hospitals WHERE id > 0");
    $db->query("DELETE FROM diseases WHERE id > 0");
    
    // 重置自增ID
    $db->query("ALTER TABLE hospitals AUTO_INCREMENT = 1");
    $db->query("ALTER TABLE doctors AUTO_INCREMENT = 1");
    $db->query("ALTER TABLE articles AUTO_INCREMENT = 1");
    $db->query("ALTER TABLE diseases AUTO_INCREMENT = 1");
    
    echo "清空现有数据完成\n";
    
    // 插入测试医院数据
    $hospitals = [
        [
            'name' => '北京协和医院',
            'level' => '三甲',
            'type' => '综合医院',
            'province' => '北京市',
            'city' => '北京',
            'district' => '东城区',
            'address' => '北京市东城区东单帅府园1号',
            'phone' => '010-69156114',
            'website' => 'https://www.pumch.cn',
            'introduction' => '北京协和医院是中国医学科学院的附属医院，是国家卫生健康委指定的全国疑难重症诊治指导中心，也是最早承担外宾医疗任务的医院之一，以学科齐全、技术力量雄厚、特色专科突出、多学科综合优势强大享誉海内外。',
            'specialties' => '内科、外科、妇产科、儿科、神经科、心血管科、呼吸科',
            'equipment' => '磁共振、CT、PET-CT、直线加速器等先进医疗设备',
            'rating' => 4.8
        ],
        [
            'name' => '上海交通大学医学院附属瑞金医院',
            'level' => '三甲',
            'type' => '综合医院',
            'province' => '上海市',
            'city' => '上海',
            'district' => '黄浦区',
            'address' => '上海市黄浦区瑞金二路197号',
            'phone' => '021-64370045',
            'website' => 'https://www.rjh.com.cn',
            'introduction' => '瑞金医院建于1907年，是一所集医疗、教学、科研为一体的三级甲等综合性医院，有着百年的深厚底蕴。',
            'specialties' => '血液科、内分泌科、心血管科、神经外科、烧伤整形科',
            'equipment' => '达芬奇手术机器人、3.0T磁共振、256排CT等',
            'rating' => 4.7
        ],
        [
            'name' => '广州市第一人民医院',
            'level' => '三甲',
            'type' => '综合医院',
            'province' => '广东省',
            'city' => '广州',
            'district' => '越秀区',
            'address' => '广州市越秀区盘福路1号',
            'phone' => '020-81048888',
            'website' => 'http://www.gzph.com',
            'introduction' => '广州市第一人民医院创建于1899年，是广州地区集医疗、教学、科研、预防、保健、康复于一体的三级甲等综合医院。',
            'specialties' => '心血管内科、神经内科、消化内科、呼吸内科',
            'equipment' => '磁共振、螺旋CT、数字血管造影机等',
            'rating' => 4.5
        ],
        [
            'name' => '深圳市人民医院',
            'level' => '三甲',
            'type' => '综合医院',
            'province' => '广东省',
            'city' => '深圳',
            'district' => '罗湖区',
            'address' => '深圳市罗湖区东门北路1017号',
            'phone' => '0755-25533018',
            'website' => 'http://www.szph.com',
            'introduction' => '深圳市人民医院始建于1946年，是深圳市最大的三级甲等综合性医院。',
            'specialties' => '心脏中心、肿瘤科、神经科、急诊科',
            'equipment' => 'PET-CT、磁共振、直线加速器等',
            'rating' => 4.4
        ],
        [
            'name' => '四川大学华西医院',
            'level' => '三甲',
            'type' => '综合医院',
            'province' => '四川省',
            'city' => '成都',
            'district' => '武侯区',
            'address' => '四川省成都市武侯区国学巷37号',
            'phone' => '028-85422286',
            'website' => 'https://www.cd120.com',
            'introduction' => '四川大学华西医院是中国西部疑难危急重症诊疗的国家级中心，也是世界规模第一的综合性单点医院。',
            'specialties' => '急诊医学、麻醉科、临床药学、病理科',
            'equipment' => '质子重离子治疗系统、达芬奇机器人等',
            'rating' => 4.9
        ]
    ];
    
    foreach ($hospitals as $hospital) {
        $db->insert('hospitals', $hospital);
    }
    echo "插入医院数据完成\n";
    
    // 插入测试医生数据
    $doctors = [
        [
            'name' => '张伟明',
            'title' => '主任医师',
            'hospital_id' => 1,
            'category_id' => 11, // 心内科
            'specialties' => '冠心病、高血压、心律失常的诊治，心脏介入治疗',
            'education' => '北京医科大学博士',
            'experience' => '从事心血管内科临床工作30余年',
            'introduction' => '在冠心病介入治疗方面有丰富经验，已完成各类心脏介入手术5000余例。',
            'rating' => 4.8
        ],
        [
            'name' => '李小华',
            'title' => '副主任医师',
            'hospital_id' => 1,
            'category_id' => 12, // 呼吸内科
            'specialties' => '肺部感染、慢性阻塞性肺疾病、哮喘的诊治',
            'education' => '协和医科大学硕士',
            'experience' => '从事呼吸内科临床工作20年',
            'introduction' => '擅长各种呼吸系统疾病的诊断和治疗。',
            'rating' => 4.6
        ],
        [
            'name' => '王建国',
            'title' => '主任医师',
            'hospital_id' => 2,
            'category_id' => 17, // 血液科
            'specialties' => '白血病、淋巴瘤、贫血的诊治，造血干细胞移植',
            'education' => '上海交通大学博士',
            'experience' => '从事血液病临床工作25年',
            'introduction' => '国内知名血液病专家，在造血干细胞移植领域有突出贡献。',
            'rating' => 4.9
        ],
        [
            'name' => '陈美丽',
            'title' => '主治医师',
            'hospital_id' => 3,
            'category_id' => 13, // 消化内科
            'specialties' => '胃肠疾病、肝病的诊治，内镜诊疗技术',
            'education' => '中山大学硕士',
            'experience' => '从事消化内科工作15年',
            'introduction' => '擅长各种消化系统疾病的内镜诊断和治疗。',
            'rating' => 4.5
        ]
    ];
    
    foreach ($doctors as $doctor) {
        $db->insert('doctors', $doctor);
    }
    echo "插入医生数据完成\n";
    
    // 插入测试文章数据
    $articles = [
        [
            'title' => '春季养生：如何预防感冒和过敏',
            'subtitle' => '专家教你春季保健小贴士',
            'content' => '<p>春季是感冒和过敏高发季节，正确的预防措施非常重要。</p><p>1. 加强锻炼，提高免疫力</p><p>2. 注意保暖，避免受凉</p><p>3. 均衡饮食，多吃新鲜蔬果</p><p>4. 保持室内空气流通</p><p>5. 避免接触过敏原</p>',
            'summary' => '春季养生需要注意预防感冒和过敏，通过合理的生活方式和饮食习惯来增强身体抵抗力。',
            'category_id' => 1,
            'author' => '健康编辑部',
            'source' => '健康医疗网',
            'tags' => '春季养生,感冒预防,过敏,健康',
            'status' => 'published',
            'publish_time' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'view_count' => 1250,
            'like_count' => 45,
            'is_featured' => 1
        ],
        [
            'title' => '糖尿病患者的饮食管理指南',
            'subtitle' => '科学饮食，控制血糖',
            'content' => '<p>糖尿病患者的饮食管理是控制病情的重要环节。</p><p><strong>饮食原则：</strong></p><p>1. 控制总热量摄入</p><p>2. 合理分配三大营养素</p><p>3. 定时定量进餐</p><p>4. 选择低血糖指数食物</p><p>5. 增加膳食纤维摄入</p>',
            'summary' => '糖尿病患者通过科学的饮食管理，可以有效控制血糖水平，改善生活质量。',
            'category_id' => 1,
            'author' => '内分泌科专家',
            'source' => '医学期刊',
            'tags' => '糖尿病,饮食管理,血糖控制',
            'status' => 'published',
            'publish_time' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'view_count' => 890,
            'like_count' => 32
        ],
        [
            'title' => '高血压的预防与治疗新进展',
            'subtitle' => '了解最新的高血压管理方法',
            'content' => '<p>高血压是常见的心血管疾病，需要综合管理。</p><p><strong>预防措施：</strong></p><p>1. 限制钠盐摄入</p><p>2. 保持理想体重</p><p>3. 规律运动</p><p>4. 戒烟限酒</p><p>5. 保持心理平衡</p>',
            'summary' => '高血压的预防和治疗需要生活方式干预和药物治疗相结合的综合管理策略。',
            'category_id' => 1,
            'author' => '心血管专家',
            'source' => '中华心血管病杂志',
            'tags' => '高血压,心血管,预防治疗',
            'status' => 'published',
            'publish_time' => date('Y-m-d H:i:s'),
            'view_count' => 567,
            'like_count' => 23
        ]
    ];
    
    foreach ($articles as $article) {
        $db->insert('articles', $article);
    }
    echo "插入文章数据完成\n";
    
    // 插入测试疾病数据
    $diseases = [
        [
            'name' => '高血压',
            'category_id' => 11,
            'alias' => '原发性高血压,高血压病',
            'symptoms' => '头痛、头晕、心悸、胸闷、乏力等症状，部分患者无明显症状',
            'causes' => '遗传因素、环境因素、生活方式、年龄、性别等多种因素综合作用',
            'diagnosis' => '血压测量、心电图、超声心动图、眼底检查、实验室检查等',
            'treatment' => '生活方式干预和药物治疗相结合，包括饮食控制、运动、戒烟限酒、降压药物等',
            'prevention' => '健康饮食、适量运动、控制体重、限制钠盐、戒烟限酒、保持心理平衡',
            'care' => '定期监测血压、按时服药、定期复查、注意生活细节',
            'view_count' => 2340
        ],
        [
            'name' => '糖尿病',
            'category_id' => 14,
            'alias' => '糖尿病,DM',
            'symptoms' => '多饮、多尿、多食、体重下降，疲乏无力、视力模糊等',
            'causes' => '遗传因素和环境因素共同作用，包括胰岛素分泌不足或作用缺陷',
            'diagnosis' => '空腹血糖、餐后血糖、糖化血红蛋白、口服葡萄糖耐量试验等',
            'treatment' => '饮食治疗、运动治疗、药物治疗、血糖监测、糖尿病教育',
            'prevention' => '健康饮食、适量运动、控制体重、定期体检、避免过度紧张',
            'care' => '血糖监测、足部护理、眼部检查、肾功能监测',
            'view_count' => 1890
        ]
    ];
    
    foreach ($diseases as $disease) {
        $db->insert('diseases', $disease);
    }
    echo "插入疾病数据完成\n";
    
    echo "测试数据初始化完成！\n";
    echo "医院数量：" . count($hospitals) . "\n";
    echo "医生数量：" . count($doctors) . "\n";
    echo "文章数量：" . count($articles) . "\n";
    echo "疾病数量：" . count($diseases) . "\n";
    
} catch (Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
}
?>