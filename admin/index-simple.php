<?php
/**
 * 简化的管理后台入口文件
 */

// 关闭错误显示
error_reporting(0);
ini_set('display_errors', 'Off');

// 开启输出缓冲
ob_start();

echo "<!DOCTYPE html>";
echo "<html lang='zh-CN'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>管理后台</title>";
echo "</head>";
echo "<body>";
echo "<h1>管理后台 - 简化版本</h1>";
echo "<p>这是一个简化的管理后台页面，用于测试基本功能。</p>";

// 测试数据库连接
try {
    require_once 'config/database.php';
    require_once 'includes/database.php';
    
    $db = getDatabase();
    if ($db) {
        echo "<p>✅ 数据库连接成功</p>";
        
        // 测试查询
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>数据库表: " . implode(', ', $tables) . "</p>";
    } else {
        echo "<p>❌ 数据库连接失败</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ 错误: " . $e->getMessage() . "</p>";
}

echo "<p><a href='debug.php'>查看详细调试信息</a></p>";
echo "<p><a href='index.php'>尝试访问完整版本</a></p>";
echo "</body>";
echo "</html>";

ob_end_flush();
?>