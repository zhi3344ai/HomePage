<?php
/**
 * 个人导航页一键安装脚本
 * 版本: 1.0.0
 * 作者: 半边
 * 创建时间: 2025-01-26
 */

// 防止直接访问
if (!defined('INSTALL_SCRIPT')) {
    define('INSTALL_SCRIPT', true);
}

// PHP 8.1 兼容性配置
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 安装步骤常量
define('STEP_CHECK', 1);
define('STEP_DATABASE', 2);
define('STEP_SITE_INFO', 3);
define('STEP_INSTALL', 4);
define('STEP_COMPLETE', 5);

// 获取当前步骤
$step = (int)($_GET['step'] ?? STEP_CHECK);

// 检查是否已安装（除非强制安装）
$forceInstall = isset($_GET['force']) && $_GET['force'] == '1';
if (file_exists('admin/config/database.php') && $step === STEP_CHECK && !$forceInstall) {
    // 显示已安装提示页面
    showAlreadyInstalledPage();
    exit;
}

// 处理POST请求
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = handlePostRequest();
}

/**
 * 处理POST请求
 */
function handlePostRequest() {
    global $step, $error;
    
    switch ($step) {
        case STEP_DATABASE:
            $error = handleDatabaseStep();
            break;
        case STEP_SITE_INFO:
            $error = handleSiteInfoStep();
            break;
        case STEP_INSTALL:
            $error = handleInstallStep();
            break;
    }
    
    return $error;
}

/**
 * 处理数据库配置步骤
 */
function handleDatabaseStep() {
    $dbHost = trim((string)($_POST['db_host'] ?? ''));
    $dbPort = trim((string)($_POST['db_port'] ?? '3306'));
    $dbName = trim((string)($_POST['db_name'] ?? ''));
    $dbUser = trim((string)($_POST['db_user'] ?? ''));
    $dbPass = trim((string)($_POST['db_pass'] ?? ''));
    
    // 验证输入
    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        return '请填写完整的数据库信息';
    }
    
    // 测试数据库连接
    try {
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // 检查数据库是否存在，不存在则创建
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // 保存数据库配置到session
        session_start();
        $_SESSION['db_config'] = [
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass
        ];
        
        header('Location: install.php?step=' . STEP_SITE_INFO);
        exit;
        
    } catch (PDOException $e) {
        return '数据库连接失败: ' . $e->getMessage();
    }
    
    return null;
}

/**
 * 处理网站信息配置步骤
 */
function handleSiteInfoStep() {
    session_start();
    
    $siteTitle = trim((string)($_POST['site_title'] ?? ''));
    $siteDescription = trim((string)($_POST['site_description'] ?? ''));
    $adminUser = trim((string)($_POST['admin_user'] ?? ''));
    $adminPass = trim((string)($_POST['admin_pass'] ?? ''));
    $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
    
    // 验证输入
    if (empty($siteTitle) || empty($adminUser) || empty($adminPass) || empty($adminEmail)) {
        return '请填写完整的网站信息';
    }
    
    if (strlen($adminPass) < 6) {
        return '管理员密码至少6位';
    }
    
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return '请输入有效的邮箱地址';
    }
    
    // 保存网站配置到session
    $_SESSION['site_config'] = [
        'title' => $siteTitle,
        'description' => $siteDescription,
        'admin_user' => $adminUser,
        'admin_pass' => $adminPass,
        'admin_email' => $adminEmail
    ];
    
    header('Location: install.php?step=' . STEP_INSTALL);
    exit;
    
    return null;
}

/**
 * 处理安装步骤
 */
function handleInstallStep() {
    session_start();
    
    if (!isset($_SESSION['db_config']) || !isset($_SESSION['site_config'])) {
        header('Location: install.php?step=' . STEP_DATABASE);
        exit;
    }
    
    $dbConfig = $_SESSION['db_config'];
    $siteConfig = $_SESSION['site_config'];
    
    try {
        // 连接数据库
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // 读取并执行SQL文件
        $sqlFile = 'database/data.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception('数据库文件 database/data.sql 不存在');
        }
        
        $sql = file_get_contents($sqlFile);
        
        // 替换数据库名
        $sql = str_replace('homepage', $dbConfig['name'], $sql);
        
        // 清理现有数据表（如果存在）
        cleanExistingTables($pdo);
        
        // 清理现有配置文件
        @unlink('admin/config/database.php');
        @unlink('admin/config/config.php');
        
        // 执行SQL文件
        executeSqlFile($pdo, $sql);
        
        // 更新管理员信息
        $hashedPassword = password_hash($siteConfig['admin_pass'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET username = ?, password = ?, email = ? WHERE id = 1");
        $stmt->execute([$siteConfig['admin_user'], $hashedPassword, $siteConfig['admin_email']]);
        
        // 更新网站信息
        $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key_name = 'site_title'");
        $stmt->execute([json_encode($siteConfig['title'])]);
        
        $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key_name = 'site_description'");
        $stmt->execute([json_encode($siteConfig['description'])]);
        
        // 创建配置目录
        if (!is_dir('admin/config')) {
            mkdir('admin/config', 0755, true);
        }
        
        // 生成数据库配置文件
        $configContent = generateDatabaseConfig($dbConfig);
        file_put_contents('admin/config/database.php', $configContent);
        
        // 生成基本配置文件
        $basicConfigContent = generateBasicConfig($siteConfig);
        file_put_contents('admin/config/config.php', $basicConfigContent);
        
        // 创建必要的目录
        $directories = [
            'admin/uploads',
            'admin/uploads/avatars',
            'admin/uploads/icons',
            'admin/logs',
            'assets/images',
            'assets/icons'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // 创建.htaccess文件
        createHtaccessFiles();
        
        // 清除session
        session_destroy();
        
        header('Location: install.php?step=' . STEP_COMPLETE);
        exit;
        
    } catch (Exception $e) {
        return '安装失败: ' . $e->getMessage();
    }
    
    return null;
}

/**
 * 显示已安装提示页面
 */
function showAlreadyInstalledPage() {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>系统已安装 - HomePage</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 40px;
                max-width: 500px;
                text-align: center;
            }
            .success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                cursor: pointer;
                margin: 10px;
                text-decoration: none;
                display: inline-block;
            }
            .btn-primary {
                background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
                color: white;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            .btn-danger {
                background: #dc3545;
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✅ 系统已安装</h1>
            
            <div class="success">
                <h3>HomePage 已经安装完成</h3>
                <p>系统检测到配置文件已存在，无需重复安装。</p>
            </div>
            
            <div style="margin: 30px 0;">
                <a href="index.html" class="btn btn-primary">🏠 访问前台</a>
                <a href="admin/" class="btn btn-secondary">⚙️ 管理后台</a>
            </div>
            
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            
            <p style="color: #666; margin-bottom: 20px;">
                如果需要重新安装系统：
            </p>
            
            <a href="install.php?step=2&force=1" class="btn btn-danger">🔄 强制重新安装</a>
        </div>
    </body>
    </html>
    <?php
}

/**
 * 清理现有数据表
 */
function cleanExistingTables($pdo) {
    try {
        // 获取所有表
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($tables)) {
            // 禁用外键检查
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // 删除所有表
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }
            
            // 重新启用外键检查
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
    } catch (PDOException $e) {
        // 如果清理失败，继续执行（可能是权限问题）
        // 不抛出异常，让后续的 SQL 执行来处理
    }
}

/**
 * 执行SQL文件
 */
function executeSqlFile($pdo, $sql) {
    // 移除注释和空行
    $lines = explode("\n", $sql);
    $cleanSql = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        // 跳过注释和空行
        if (empty($line) || strpos($line, '--') === 0 || strpos($line, '#') === 0) {
            continue;
        }
        $cleanSql .= $line . "\n";
    }
    
    // 分割SQL语句
    $statements = preg_split('/;\s*$/m', $cleanSql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
}

/**
 * 生成数据库配置文件内容
 */
function generateDatabaseConfig($config) {
    return "<?php
/**
 * 数据库配置文件
 * 自动生成于: " . date('Y-m-d H:i:s') . "
 */

return [
    'host' => '{$config['host']}',
    'port' => '{$config['port']}',
    'database' => '{$config['name']}',
    'username' => '{$config['user']}',
    'password' => '{$config['pass']}',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
";
}

/**
 * 生成基本配置文件内容
 */
function generateBasicConfig($config) {
    $secretKey = bin2hex(random_bytes(32));
    return "<?php
/**
 * 基本配置文件
 * 自动生成于: " . date('Y-m-d H:i:s') . "
 */

return [
    // 网站基本信息
    'site_name' => '{$config['title']}',
    'site_description' => '{$config['description']}',
    'site_url' => 'http://' . \$_SERVER['HTTP_HOST'],
    
    // 安全配置
    'secret_key' => '{$secretKey}',
    'session_name' => 'PERSONAL_NAV_SESSION',
    'cookie_lifetime' => 86400 * 7, // 7天
    
    // 文件上传配置
    'upload_max_size' => 5 * 1024 * 1024, // 5MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
    'upload_path' => '../assets/images/',
    
    // 系统配置
    'timezone' => 'Asia/Shanghai',
    'debug' => false,
    'log_level' => 'error',
    
    // 版本信息
    'version' => '1.0.0',
    'install_time' => '" . date('Y-m-d H:i:s') . "'
];
";
}

/**
 * 创建.htaccess文件
 */
function createHtaccessFiles() {
    // 根目录.htaccess
    $rootHtaccess = "# 个人导航页 .htaccess 配置
RewriteEngine On

# 强制HTTPS (可选，根据需要启用)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# 隐藏文件扩展名
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^([^.]+)$ $1.html [NC,L]

# 缓存静态资源
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css \"access plus 1 month\"
    ExpiresByType application/javascript \"access plus 1 month\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType image/jpg \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
    ExpiresByType image/gif \"access plus 1 month\"
    ExpiresByType image/svg+xml \"access plus 1 month\"
</IfModule>

# 压缩
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# 安全配置
<Files ~ \"^\\.(htaccess|htpasswd|ini|log|sh|sql)$\">
    Order allow,deny
    Deny from all
</Files>
";

    // 管理后台.htaccess
    $adminHtaccess = "# 管理后台 .htaccess 配置
RewriteEngine On

# 重写规则
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# 安全配置
<Files ~ \"^\\.(htaccess|htpasswd|ini|log|sh|sql)$\">
    Order allow,deny
    Deny from all
</Files>

# 保护配置文件
<Files \"config/*\">
    Order allow,deny
    Deny from all
</Files>
";

    file_put_contents('.htaccess', $rootHtaccess);
    file_put_contents('admin/.htaccess', $adminHtaccess);
}

/**
 * 检查系统环境
 */
function checkSystemRequirements() {
    $requirements = [
        'PHP版本' => [
            'required' => '7.4.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
        ],
        'PDO扩展' => [
            'required' => '启用',
            'current' => extension_loaded('pdo') ? '已启用' : '未启用',
            'status' => extension_loaded('pdo')
        ],
        'PDO MySQL' => [
            'required' => '启用',
            'current' => extension_loaded('pdo_mysql') ? '已启用' : '未启用',
            'status' => extension_loaded('pdo_mysql')
        ],
        'JSON扩展' => [
            'required' => '启用',
            'current' => extension_loaded('json') ? '已启用' : '未启用',
            'status' => extension_loaded('json')
        ],
        'GD扩展' => [
            'required' => '启用',
            'current' => extension_loaded('gd') ? '已启用' : '未启用',
            'status' => extension_loaded('gd')
        ],
        '文件写入权限' => [
            'required' => '可写',
            'current' => is_writable('.') ? '可写' : '不可写',
            'status' => is_writable('.')
        ]
    ];
    
    return $requirements;
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomePage - 一键安装</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .install-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .install-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .install-content {
            padding: 40px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: #6c757d;
            position: relative;
        }
        
        .step.active {
            background: #00f5ff;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            width: 20px;
            height: 2px;
            background: #e9ecef;
            transform: translateY(-50%);
        }
        
        .step.completed:not(:last-child)::after {
            background: #28a745;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00f5ff;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .btn {
            background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .requirements-table th,
        .requirements-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .requirements-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-ok {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        
        .install-progress {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
            height: 100%;
            transition: width 0.3s;
        }
        
        .complete-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .complete-actions .btn {
            flex: 1;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🚀 HomePage</h1>
            <p>一键安装向导 - 让您快速搭建专属导航页</p>
        </div>
        
        <div class="install-content">
            <!-- 步骤指示器 -->
            <div class="step-indicator">
                <div class="step <?= $step >= STEP_CHECK ? ($step > STEP_CHECK ? 'completed' : 'active') : '' ?>">1</div>
                <div class="step <?= $step >= STEP_DATABASE ? ($step > STEP_DATABASE ? 'completed' : 'active') : '' ?>">2</div>
                <div class="step <?= $step >= STEP_SITE_INFO ? ($step > STEP_SITE_INFO ? 'completed' : 'active') : '' ?>">3</div>
                <div class="step <?= $step >= STEP_INSTALL ? ($step > STEP_INSTALL ? 'completed' : 'active') : '' ?>">4</div>
                <div class="step <?= $step >= STEP_COMPLETE ? 'active' : '' ?>">5</div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === STEP_CHECK): ?>
                <!-- 步骤1: 环境检查 -->
                <h2>📋 环境检查</h2>
                <p>正在检查您的服务器环境是否满足安装要求...</p>
                
                <?php $requirements = checkSystemRequirements(); ?>
                <table class="requirements-table">
                    <thead>
                        <tr>
                            <th>检查项目</th>
                            <th>要求</th>
                            <th>当前状态</th>
                            <th>结果</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requirements as $name => $req): ?>
                            <tr>
                                <td><?= $name ?></td>
                                <td><?= $req['required'] ?></td>
                                <td><?= $req['current'] ?></td>
                                <td class="<?= $req['status'] ? 'status-ok' : 'status-error' ?>">
                                    <?= $req['status'] ? '✅ 通过' : '❌ 失败' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php $allPassed = array_reduce($requirements, function($carry, $req) { return $carry && $req['status']; }, true); ?>
                
                <?php if ($allPassed): ?>
                    <div class="alert alert-success">
                        ✅ 恭喜！您的服务器环境完全满足安装要求。
                    </div>
                    <a href="install.php?step=<?= STEP_DATABASE ?>" class="btn">下一步：配置数据库</a>
                <?php else: ?>
                    <div class="alert alert-danger">
                        ❌ 您的服务器环境不满足安装要求，请联系您的主机提供商解决上述问题。
                    </div>
                    <button class="btn" disabled>环境检查未通过</button>
                <?php endif; ?>
                
            <?php elseif ($step === STEP_DATABASE): ?>
                <!-- 步骤2: 数据库配置 -->
                <h2>🗄️ 数据库配置</h2>
                <p>请填写您的MySQL数据库连接信息</p>
                
                <form method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="db_host">数据库主机</label>
                            <input type="text" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label for="db_port">端口</label>
                            <input type="number" id="db_port" name="db_port" value="3306" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">数据库名</label>
                        <input type="text" id="db_name" name="db_name" placeholder="personal_nav" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="db_user">用户名</label>
                            <input type="text" id="db_user" name="db_user" required>
                        </div>
                        <div class="form-group">
                            <label for="db_pass">密码</label>
                            <input type="password" id="db_pass" name="db_pass">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">测试连接并继续</button>
                </form>
                
            <?php elseif ($step === STEP_SITE_INFO): ?>
                <!-- 步骤3: 网站信息配置 -->
                <h2>🌐 网站信息配置</h2>
                <p>请填写您的网站基本信息和管理员账号</p>
                
                <form method="post">
                    <div class="form-group">
                        <label for="site_title">网站标题</label>
                        <input type="text" id="site_title" name="site_title" value="个人导航 | Futuristic Nav" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">网站描述</label>
                        <textarea id="site_description" name="site_description" rows="3" required>一个具有未来科技感设计的个人导航页面，支持多主题切换、响应式布局和丰富的视觉效果</textarea>
                    </div>
                    
                    <h3 style="margin: 30px 0 20px 0; color: #333;">👤 管理员账号</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin_user">管理员用户名</label>
                            <input type="text" id="admin_user" name="admin_user" value="admin" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_email">管理员邮箱</label>
                            <input type="email" id="admin_email" name="admin_email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_pass">管理员密码</label>
                        <input type="password" id="admin_pass" name="admin_pass" placeholder="至少6位字符" required>
                    </div>
                    
                    <button type="submit" class="btn">保存配置并继续</button>
                </form>
                
            <?php elseif ($step === STEP_INSTALL): ?>
                <!-- 步骤4: 开始安装 -->
                <h2>⚡ 开始安装</h2>
                <p>所有配置已完成，点击下方按钮开始安装系统</p>
                
                <div class="install-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%" id="progress"></div>
                    </div>
                    <div id="status">准备安装...</div>
                </div>
                
                <form method="post" id="installForm">
                    <button type="submit" class="btn" id="installBtn">🚀 开始安装</button>
                </form>
                
                <script>
                document.getElementById('installForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const btn = document.getElementById('installBtn');
                    const progress = document.getElementById('progress');
                    const status = document.getElementById('status');
                    
                    btn.disabled = true;
                    btn.textContent = '安装中...';
                    
                    // 模拟安装进度
                    let percent = 0;
                    const steps = [
                        '连接数据库...',
                        '清理现有数据...',
                        '创建数据表...',
                        '导入默认数据...',
                        '生成配置文件...',
                        '完成安装...'
                    ];
                    
                    const interval = setInterval(() => {
                        percent += 16.67;
                        progress.style.width = percent + '%';
                        
                        if (steps[Math.floor(percent / 16.67) - 1]) {
                            status.textContent = steps[Math.floor(percent / 16.67) - 1];
                        }
                        
                        if (percent >= 100) {
                            clearInterval(interval);
                            // 提交表单
                            setTimeout(() => {
                                this.submit();
                            }, 1000);
                        }
                    }, 500);
                });
                </script>
                
            <?php elseif ($step === STEP_COMPLETE): ?>
                <!-- 步骤5: 安装完成 -->
                <h2>🎉 安装完成</h2>
                <div class="alert alert-success">
                    ✅ 恭喜！HomePage已成功安装到您的服务器上。
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin-bottom: 15px; color: #333;">📋 安装信息</h3>
                    <p><strong>安装时间:</strong> <?= date('Y-m-d H:i:s') ?></p>
                    <p><strong>版本:</strong> 1.0.0</p>
                    <p><strong>前台地址:</strong> <a href="index.html" target="_blank"><?= 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) ?>index.html</a></p>
                    <p><strong>后台地址:</strong> <a href="admin/" target="_blank"><?= 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) ?>admin</a></p>
                </div>
                
                <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffeaa7;">
                    <h3 style="margin-bottom: 15px; color: #856404;">⚠️ 安全提醒</h3>
                    <p>为了您的网站安全，请在安装完成后：</p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>删除 <code>install.php</code> 安装文件</li>
                        <li>删除 <code>database/data.sql</code> 数据库文件</li>
                        <li>修改管理员密码</li>
                        <li>定期备份数据库</li>
                    </ul>
                </div>
                
                <div class="complete-actions">
                    <a href="index.html" class="btn">🏠 访问前台</a>
                    <a href="admin/" class="btn btn-secondary">⚙️ 进入后台</a>
                </div>
                
                <script>
                // 5秒后自动删除安装文件提醒
                setTimeout(() => {
                    if (confirm('是否现在删除安装文件以提高安全性？')) {
                        fetch('install.php?action=cleanup', {method: 'POST'})
                            .then(() => {
                                alert('安装文件已删除！');
                                window.location.href = 'index.html';
                            });
                    }
                }, 5000);
                </script>
                
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
// 处理清理请求
if (($_GET['action'] ?? '') === 'cleanup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    @unlink('install.php');
    @unlink('database/data.sql');
    echo 'OK';
    exit;
}
?>