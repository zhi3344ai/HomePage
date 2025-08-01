<?php
/**
 * 基本配置文件
 */

return [
    // 网站基本信息
    'site_name' => 'HomePage | 个人导航',
    'site_description' => '一个具有未来科技感设计的个人导航页面',
    'site_url' => 'http://localhost',
    
    // 安全配置
    'secret_key' => 'homepage-secret-key-' . md5(__DIR__),
    'session_name' => 'HOMEPAGE_SESSION',
    'cookie_lifetime' => 86400 * 7, // 7天
    
    // 文件上传配置
    'upload_max_size' => 5 * 1024 * 1024, // 5MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
    
    // 调试模式
    'debug' => true,
    'log_errors' => true,
    
    // 默认设置
    'timezone' => 'Asia/Shanghai',
    'language' => 'zh-CN'
];