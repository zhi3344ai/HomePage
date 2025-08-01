<?php
/**
 * 系统完整性测试脚本
 * 用于检查系统文件和配置是否正确
 */

echo "🔍 开始系统完整性检查...\n\n";

// 检查必要文件
$requiredFiles = [
    'index.html' => '前台主页面',
    'api/index.php' => '前台API接口',
    'install.php' => '安装向导',
    'database/data.sql' => '数据库文件',
    'admin/index.php' => '管理后台入口',
    'admin/includes/functions.php' => '后台核心函数',
    'admin/includes/database.php' => '数据库操作类',
    'css/main.css' => '主样式文件',
    'js/main.js' => '主JavaScript文件',
    '.htaccess' => '访问控制文件',
    'admin/.htaccess' => '后台访问控制',
    'pwa/manifest.json' => 'PWA配置文件',
    'pwa/service-worker.js' => 'Service Worker文件'
];

$missingFiles = [];
$existingFiles = [];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        $existingFiles[] = "✅ {$file} - {$description}";
    } else {
        $missingFiles[] = "❌ {$file} - {$description}";
    }
}

echo "📁 文件检查结果:\n";
foreach ($existingFiles as $file) {
    echo "  {$file}\n";
}

if (!empty($missingFiles)) {
    echo "\n⚠️  缺失文件:\n";
    foreach ($missingFiles as $file) {
        echo "  {$file}\n";
    }
}

// 检查目录权限
$directories = [
    'admin/uploads' => '上传目录',
    'admin/logs' => '日志目录',
    'assets/images' => '图片目录',
    'assets/icons' => '图标目录'
];

echo "\n📂 目录权限检查:\n";
foreach ($directories as $dir => $description) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "  ✅ {$dir} - {$description} (可写)\n";
        } else {
            echo "  ⚠️  {$dir} - {$description} (不可写)\n";
        }
    } else {
        echo "  ❌ {$dir} - {$description} (不存在)\n";
    }
}

// 检查PHP扩展
$requiredExtensions = [
    'pdo' => 'PDO数据库扩展',
    'pdo_mysql' => 'MySQL PDO扩展',
    'json' => 'JSON扩展',
    'gd' => 'GD图像处理扩展'
];

echo "\n🔧 PHP扩展检查:\n";
foreach ($requiredExtensions as $ext => $description) {
    if (extension_loaded($ext)) {
        echo "  ✅ {$ext} - {$description}\n";
    } else {
        echo "  ❌ {$ext} - {$description}\n";
    }
}

// 检查PHP版本
echo "\n🐘 PHP环境:\n";
echo "  版本: " . PHP_VERSION . "\n";
echo "  最低要求: 7.4.0\n";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "  ✅ PHP版本符合要求\n";
} else {
    echo "  ❌ PHP版本过低\n";
}

// 检查配置文件
echo "\n⚙️  配置文件检查:\n";
if (file_exists('admin/config/database.php')) {
    echo "  ✅ 数据库配置文件存在\n";
    
    // 尝试连接数据库
    try {
        $config = require 'admin/config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        echo "  ✅ 数据库连接成功\n";
    } catch (Exception $e) {
        echo "  ❌ 数据库连接失败: " . $e->getMessage() . "\n";
    }
} else {
    echo "  ⚠️  数据库配置文件不存在 (需要运行安装程序)\n";
}

if (file_exists('admin/config/config.php')) {
    echo "  ✅ 基本配置文件存在\n";
} else {
    echo "  ⚠️  基本配置文件不存在 (需要运行安装程序)\n";
}

// 检查JavaScript语法
echo "\n📜 JavaScript文件检查:\n";
$jsFiles = ['js/main.js', 'js/animations.js', 'js/navigation.js', 'js/particles.js'];
foreach ($jsFiles as $jsFile) {
    if (file_exists($jsFile)) {
        $content = file_get_contents($jsFile);
        // 简单的语法检查
        if (substr_count($content, '{') === substr_count($content, '}')) {
            echo "  ✅ {$jsFile} - 括号匹配\n";
        } else {
            echo "  ⚠️  {$jsFile} - 括号不匹配\n";
        }
    } else {
        echo "  ❌ {$jsFile} - 文件不存在\n";
    }
}

// 总结
echo "\n📋 检查完成!\n";
if (empty($missingFiles)) {
    echo "✅ 所有必要文件都存在\n";
} else {
    echo "⚠️  发现 " . count($missingFiles) . " 个缺失文件\n";
}

echo "\n💡 建议:\n";
echo "  1. 如果是首次安装，请访问 install.php 完成安装\n";
echo "  2. 确保所有目录都有正确的权限\n";
echo "  3. 检查服务器是否支持所需的PHP扩展\n";
echo "  4. 定期备份数据库和上传文件\n";

echo "\n🎉 系统检查完成!\n";
?>