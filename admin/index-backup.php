<?php
/**
 * 使用备份functions的管理后台
 */

// 关闭错误显示
error_reporting(0);
ini_set('display_errors', 'Off');

// 开启输出缓冲
ob_start();

// PHP 8.1 兼容性配置
require_once 'includes/php81-compat.php';

// 启动会话
session_start();

// 引入配置文件
require_once 'config/config.php';
require_once 'includes/functions-backup.php';  // 使用备份的functions文件
require_once 'includes/database.php';

// 获取路由参数
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// 检查登录状态
if (function_exists('isLoggedIn') && !isLoggedIn() && $page !== 'login') {
    header('Location: login.php');
    exit;
}

// 获取当前用户信息
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : ['username' => 'Admin', 'avatar' => 'assets/images/default-avatar.svg'];

// 页面标题映射
$pageTitles = [
    'dashboard' => '仪表盘',
    'profile' => '个人信息',
    'social' => '社交链接',
    'navigation' => '导航管理',
    'categories' => '分类管理',
    'media' => '媒体管理',
    'themes' => '主题设置',
    'settings' => '系统设置',
    'stats' => '访问统计',
    'logs' => '系统日志'
];

$pageTitle = $pageTitles[$page] ?? '管理后台';

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - HomePage 管理后台</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="icon" type="image/svg+xml" href="../assets/icons/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h2>🚀 导航管理</h2>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                        <a href="?page=dashboard">
                            <span class="icon">📊</span>
                            <span class="text">仪表盘</span>
                        </a>
                    </li>
                    <li class="<?= $page === 'navigation' ? 'active' : '' ?>">
                        <a href="?page=navigation">
                            <span class="icon">🧭</span>
                            <span class="text">导航项目</span>
                        </a>
                    </li>
                    <li class="<?= $page === 'categories' ? 'active' : '' ?>">
                        <a href="?page=categories">
                            <span class="icon">📁</span>
                            <span class="text">分类管理</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- 主内容区 -->
        <main class="main-content">
            <header class="top-bar">
                <div class="page-title">
                    <h1><?= $pageTitle ?></h1>
                </div>
            </header>
            
            <div class="page-content">
                <?php
                $pageFile = "pages/{$page}.php";
                if (file_exists($pageFile)) {
                    include $pageFile;
                } else {
                    echo '<div class="error-message">页面不存在</div>';
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>