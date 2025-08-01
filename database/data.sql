-- HomePage 数据库结构
-- 创建时间: 2025-01-26
-- 数据库版本: MySQL 5.7+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 数据库创建
-- ----------------------------
CREATE DATABASE IF NOT EXISTS `homepage` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `homepage`;

-- ----------------------------
-- 管理员表
-- ----------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `last_login` datetime DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int(11) DEFAULT '0' COMMENT '登录次数',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1:启用 0:禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- ----------------------------
-- 个人信息表
-- ----------------------------
DROP TABLE IF EXISTS `profile`;
CREATE TABLE `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '姓名',
  `title` varchar(200) NOT NULL COMMENT '职业标题',
  `description` text COMMENT '个人简介',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像路径',
  `status_text` varchar(50) DEFAULT '在线' COMMENT '状态文本',
  `status_type` enum('online','away','busy','offline') DEFAULT 'online' COMMENT '状态类型',
  `projects_count` int(11) DEFAULT '0' COMMENT '项目数量',
  `experience_years` int(11) DEFAULT '0' COMMENT '经验年数',
  `skills_count` int(11) DEFAULT '0' COMMENT '技能数量',
  `email` varchar(100) DEFAULT NULL COMMENT '联系邮箱',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `location` varchar(100) DEFAULT NULL COMMENT '所在地',
  `website` varchar(255) DEFAULT NULL COMMENT '个人网站',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='个人信息表';

-- ----------------------------
-- 社交链接表
-- ----------------------------
DROP TABLE IF EXISTS `social_links`;
CREATE TABLE `social_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL COMMENT '平台名称',
  `url` varchar(500) NOT NULL COMMENT '链接地址',
  `icon` varchar(255) DEFAULT NULL COMMENT '图标',
  `color` varchar(7) DEFAULT '#333333' COMMENT '主题色',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社交链接表';

-- ----------------------------
-- 导航分类表
-- ----------------------------
DROP TABLE IF EXISTS `nav_categories`;
CREATE TABLE `nav_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `icon` varchar(255) DEFAULT NULL COMMENT '分类图标',
  `color` varchar(7) NOT NULL DEFAULT '#00f5ff' COMMENT '分类颜色',
  `description` text COMMENT '分类描述',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导航分类表';

-- ----------------------------
-- 导航项目表
-- ----------------------------
DROP TABLE IF EXISTS `nav_items`;
CREATE TABLE `nav_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT '分类ID',
  `name` varchar(100) NOT NULL COMMENT '项目名称',
  `url` varchar(500) NOT NULL COMMENT '链接地址',
  `icon` varchar(255) DEFAULT NULL COMMENT '项目图标',
  `description` text COMMENT '项目描述',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `click_count` int(11) DEFAULT '0' COMMENT '点击次数',
  `is_featured` tinyint(1) DEFAULT '0' COMMENT '是否推荐',
  `target` enum('_self','_blank') DEFAULT '_blank' COMMENT '打开方式',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `nav_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `nav_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导航项目表';

-- ----------------------------
-- 主题配置表
-- ----------------------------
DROP TABLE IF EXISTS `themes`;
CREATE TABLE `themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '主题名称',
  `display_name` varchar(100) NOT NULL COMMENT '显示名称',
  `colors` json NOT NULL COMMENT '颜色配置',
  `effects` json DEFAULT NULL COMMENT '特效配置',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '是否默认',
  `preview_image` varchar(255) DEFAULT NULL COMMENT '预览图',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='主题配置表';

-- ----------------------------
-- 系统设置表
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL COMMENT '设置键名',
  `value` json NOT NULL COMMENT '设置值',
  `description` text COMMENT '设置描述',
  `group_name` varchar(50) DEFAULT 'general' COMMENT '设置分组',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';

-- ----------------------------
-- 访问统计表
-- ----------------------------
DROP TABLE IF EXISTS `visit_stats`;
CREATE TABLE `visit_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL COMMENT '日期',
  `page_views` int(11) DEFAULT '0' COMMENT '页面浏览量',
  `unique_visitors` int(11) DEFAULT '0' COMMENT '独立访客数',
  `nav_clicks` json DEFAULT NULL COMMENT '导航点击统计',
  `user_agents` json DEFAULT NULL COMMENT '用户代理统计',
  `referrers` json DEFAULT NULL COMMENT '来源统计',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问统计表';

-- ----------------------------
-- 系统日志表
-- ----------------------------
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `action` varchar(100) NOT NULL COMMENT '操作类型',
  `description` text COMMENT '操作描述',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统日志表';

-- ----------------------------
-- 默认数据插入
-- ----------------------------

-- 插入默认管理员账号 (用户名: admin, 密码: admin123)
INSERT INTO `admin_users` (`username`, `password`, `email`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 1);

-- 插入默认个人信息
INSERT INTO `profile` (`name`, `title`, `description`, `avatar`, `projects_count`, `experience_years`, `skills_count`, `email`, `location`) VALUES
('半边', '全栈开发工程师', '热爱技术，专注于前端开发和用户体验设计。喜欢探索新技术，分享技术心得。', 'assets/images/avatar.svg', 12, 3, 8, 'example@email.com', '中国');

-- 插入默认社交链接
INSERT INTO `social_links` (`platform`, `url`, `icon`, `color`, `sort_order`) VALUES
('GitHub', 'https://github.com', '🐙', '#333333', 1),
('Twitter', 'https://twitter.com', '🐦', '#1DA1F2', 2),
('LinkedIn', 'https://linkedin.com', '💼', '#0077B5', 3),
('Email', 'mailto:example@email.com', '📧', '#D44638', 4),
('微信', '#', '💬', '#07C160', 5);

-- 插入默认导航分类
INSERT INTO `nav_categories` (`name`, `icon`, `color`, `description`, `sort_order`) VALUES
('开发工具', '🛠️', '#00f5ff', '编程开发相关工具和平台', 1),
('设计资源', '🎨', '#ff0080', '设计相关的工具和资源', 2),
('学习资源', '📚', '#4facfe', '学习和文档相关资源', 3),
('常用工具', '🔧', '#00f2fe', '日常使用的实用工具', 4),
('娱乐休闲', '🎮', '#ff6b6b', '娱乐和休闲相关网站', 5);

-- 插入默认导航项目
INSERT INTO `nav_items` (`category_id`, `name`, `url`, `icon`, `description`, `sort_order`, `is_featured`) VALUES
(1, 'GitHub', 'https://github.com', '🐙', '全球最大的代码托管平台', 1, 1),
(1, 'VS Code', 'https://code.visualstudio.com', '💻', '微软开发的强大代码编辑器', 2, 1),
(1, 'Stack Overflow', 'https://stackoverflow.com', '❓', '程序员问答社区', 3, 0),
(2, 'Figma', 'https://figma.com', '🎨', '在线协作设计工具', 1, 1),
(2, 'Adobe Creative Cloud', 'https://www.adobe.com/creativecloud.html', '🎭', 'Adobe创意套件', 2, 0),
(3, 'MDN Web Docs', 'https://developer.mozilla.org', '📚', 'Web开发权威文档', 1, 1),
(3, '菜鸟教程', 'https://www.runoob.com', '📖', '编程学习教程网站', 2, 0),
(4, 'Google', 'https://google.com', '🔍', '全球最大搜索引擎', 1, 1),
(4, '百度翻译', 'https://fanyi.baidu.com', '🌐', '在线翻译工具', 2, 0),
(5, 'YouTube', 'https://youtube.com', '📺', '全球最大视频分享平台', 1, 0),
(5, 'Netflix', 'https://netflix.com', '🎬', '在线视频流媒体服务', 2, 0);

-- 插入默认主题
INSERT INTO `themes` (`name`, `display_name`, `colors`, `effects`, `is_default`) VALUES
('cyberpunk', '赛博朋克', '{"primary": "#00f5ff", "secondary": "#ff0080", "background": "#0a0a0a", "text": "#ffffff", "accent": "#00ff41"}', '{"blur": "20px", "glow": "0 0 20px rgba(0, 245, 255, 0.3)", "particles": true}', 1),
('aurora', '极光', '{"primary": "#4facfe", "secondary": "#00f2fe", "background": "#1a1a2e", "text": "#eee", "accent": "#a8edea"}', '{"blur": "20px", "glow": "0 0 20px rgba(79, 172, 254, 0.3)", "particles": true}', 0),
('sunset', '日落', '{"primary": "#ff6b6b", "secondary": "#feca57", "background": "#2c2c54", "text": "#f1f2f6", "accent": "#ff9ff3"}', '{"blur": "15px", "glow": "0 0 15px rgba(255, 107, 107, 0.3)", "particles": true}', 0),
('forest', '森林', '{"primary": "#00d2d3", "secondary": "#54a0ff", "background": "#1e3c72", "text": "#ffffff", "accent": "#5f27cd"}', '{"blur": "18px", "glow": "0 0 18px rgba(0, 210, 211, 0.3)", "particles": true}', 0);

-- 插入默认系统设置
INSERT INTO `settings` (`key_name`, `value`, `description`, `group_name`) VALUES
('site_title', '"HomePage | 个人导航"', '网站标题', 'general'),
('site_description', '"一个具有未来科技感设计的个人导航页面"', '网站描述', 'general'),
('site_keywords', '"个人导航,科技感,导航页,未来主义,响应式设计"', '网站关键词', 'general'),
('animations_enabled', 'true', '是否启用动画效果', 'display'),
('particles_enabled', 'true', '是否启用粒子效果', 'display'),
('particle_count', '80', '粒子数量', 'display'),
('effects_quality', '"high"', '特效质量', 'display'),
('stats_enabled', 'true', '是否启用统计功能', 'features'),
('click_tracking', 'true', '是否启用点击统计', 'features'),
('auto_backup', 'false', '是否启用自动备份', 'system'),
('backup_interval', '7', '备份间隔(天)', 'system');

SET FOREIGN_KEY_CHECKS = 1;