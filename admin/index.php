<?php
/**
 * 管理后台入口文件
 * 版本: 1.0.0
 */

// 开启错误显示（临时调试用）
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// 记录错误到文件
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-error.log');

// 确保日志目录存在
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// 错误处理函数
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $errorLog = __DIR__ . '/logs/php-error.log';
    $message = date('Y-m-d H:i:s') . " - Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($message, 3, $errorLog);
    
    // 对于严重错误，显示友好的错误页面
    if ($errno == E_ERROR || $errno == E_PARSE || $errno == E_CORE_ERROR || 
        $errno == E_COMPILE_ERROR || $errno == E_USER_ERROR) {
        echo "<h1>系统错误</h1>";
        echo "<p>抱歉，系统遇到了一个错误。管理员已经被通知。</p>";
        echo "<p>请稍后再试或联系网站管理员。</p>";
        exit(1);
    }
    
    // 返回false以允许PHP的内置错误处理器继续执行
    return false;
}

// 设置自定义错误处理器
set_error_handler("customErrorHandler");

// 开启输出缓冲
ob_start();

// PHP 8.1 兼容性配置
require_once __DIR__ . '/includes/php81-compat.php';

// 启动会话
session_start();

// 检查是否已安装
if (!file_exists(__DIR__ . '/config/database.php')) {
    header('Location: ../install.php');
    exit('系统未安装，请先运行安装程序');
}

// 引入配置文件
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 获取路由参数
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// 检查登录状态
if (function_exists('isLoggedIn') && !isLoggedIn() && $page !== 'login') {
    header('Location: login.php');
    exit;
}

// 处理登出
if ($page === 'logout') {
    logout();
    header('Location: login.php');
    exit;
}

// 页面路由
$allowedPages = [
    'dashboard', 'profile', 'social', 'navigation', 
    'categories', 'media', 'themes', 'settings', 'stats', 'logs'
];

if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

// 获取当前用户信息
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : ['username' => 'Admin', 'avatar' => '../assets/images/default-avatar.svg'];

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- 主界面布局 -->
        <div class="admin-layout">
            <!-- 侧边栏 -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="logo">
                        <h2>HomePage</h2>
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
                        <li class="<?= $page === 'profile' ? 'active' : '' ?>">
                            <a href="?page=profile">
                                <span class="icon">👤</span>
                                <span class="text">个人信息</span>
                            </a>
                        </li>
                        <li class="<?= $page === 'social' ? 'active' : '' ?>">
                            <a href="?page=social">
                                <span class="icon">🔗</span>
                                <span class="text">社交链接</span>
                            </a>
                        </li>
                        <li class="nav-divider">导航管理</li>
                        <li class="<?= $page === 'categories' ? 'active' : '' ?>">
                            <a href="?page=categories">
                                <span class="icon">📁</span>
                                <span class="text">分类管理</span>
                            </a>
                        </li>
                        <li class="<?= $page === 'navigation' ? 'active' : '' ?>">
                            <a href="?page=navigation">
                                <span class="icon">🧭</span>
                                <span class="text">导航项目</span>
                            </a>
                        </li>
                        <li class="<?= $page === 'media' ? 'active' : '' ?>">
                            <a href="?page=media">
                                <span class="icon">🖼️</span>
                                <span class="text">媒体管理</span>
                            </a>
                        </li>
                        <li class="nav-divider">系统设置</li>
                        <li class="<?= $page === 'themes' ? 'active' : '' ?>">
                            <a href="?page=themes">
                                <span class="icon">🎨</span>
                                <span class="text">主题设置</span>
                            </a>
                        </li>
                        <li class="<?= $page === 'settings' ? 'active' : '' ?>">
                            <a href="?page=settings">
                                <span class="icon">⚙️</span>
                                <span class="text">系统设置</span>
                            </a>
                        </li>
                        <li class="<?= $page === 'stats' ? 'active' : '' ?>">
                            <a href="?page=stats">
                                <span class="icon">📈</span>
                                <span class="text">访问统计</span>
                            </a>
                        </li>
                        <li class="<?= $page === 'logs' ? 'active' : '' ?>">
                            <a href="?page=logs">
                                <span class="icon">📋</span>
                                <span class="text">系统日志</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <div class="sidebar-footer">
                    <div class="user-info">
                        <div class="user-avatar">
                            <img src="<?= $currentUser['avatar'] ?? '../assets/images/default-avatar.svg' ?>" alt="头像">
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?= htmlspecialchars($currentUser['username']) ?></div>
                            <div class="user-role">管理员</div>
                        </div>
                    </div>
                    <a href="?page=logout" class="logout-btn" title="退出登录">🚪</a>
                </div>
            </aside>
            
            <!-- 主内容区 -->
            <main class="main-content">
                <!-- 顶部栏 -->
                <header class="top-bar">
                    <div class="page-title">
                        <h1><?= $pageTitle ?></h1>
                        <div class="breadcrumb">
                            <span>管理后台</span>
                            <span class="separator">/</span>
                            <span><?= $pageTitle ?></span>
                        </div>
                    </div>
                    
                    <div class="top-actions">
                        <a href="../index.html" target="_blank" class="btn btn-outline">
                            <span class="icon">🏠</span>
                            <span>查看前台</span>
                        </a>
                        <div class="user-menu">
                            <button class="user-menu-btn">
                                <img src="<?= $currentUser['avatar'] ?? '../assets/images/default-avatar.svg' ?>" alt="头像">
                                <span><?= htmlspecialchars($currentUser['username']) ?></span>
                                <span class="arrow">▼</span>
                            </button>
                            <div class="user-menu-dropdown">
                                <a href="?page=profile">个人设置</a>
                                <a href="?page=logout">退出登录</a>
                            </div>
                        </div>
                    </div>
                </header>
                
                <!-- 页面内容 -->
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
    
    <!-- 通用JavaScript -->
    <script src="assets/js/admin.js"></script>
    
    <!-- 消息提示 -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="toast toast-<?= $_SESSION['message']['type'] ?>" id="toast">
            <div class="toast-content">
                <span class="toast-icon">
                    <?= $_SESSION['message']['type'] === 'success' ? '✅' : '❌' ?>
                </span>
                <span class="toast-message"><?= htmlspecialchars($_SESSION['message']['text']) ?></span>
            </div>
            <button class="toast-close" onclick="closeToast()">×</button>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast');
                if (toast) toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                closeToast();
            }, 5000);
            
            function closeToast() {
                const toast = document.getElementById('toast');
                if (toast) {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }
            }
        </script>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
</body>
</html>