<?php
/**
 * 调试脚本
 */

// 开启错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>调试信息</h1>";

echo "<h2>1. PHP版本</h2>";
echo "PHP版本: " . PHP_VERSION . "<br>";

echo "<h2>2. 包含文件测试</h2>";

try {
    echo "测试包含 php81-compat.php...<br>";
    require_once 'includes/php81-compat.php';
    echo "✅ php81-compat.php 包含成功<br>";
} catch (Exception $e) {
    echo "❌ php81-compat.php 包含失败: " . $e->getMessage() . "<br>";
}

try {
    echo "测试包含 config.php...<br>";
    require_once 'config/config.php';
    echo "✅ config.php 包含成功<br>";
} catch (Exception $e) {
    echo "❌ config.php 包含失败: " . $e->getMessage() . "<br>";
}

try {
    echo "测试包含 database.php...<br>";
    require_once 'includes/database.php';
    echo "✅ database.php 包含成功<br>";
} catch (Exception $e) {
    echo "❌ database.php 包含失败: " . $e->getMessage() . "<br>";
}

try {
    echo "测试包含 functions.php...<br>";
    require_once 'includes/functions.php';
    echo "✅ functions.php 包含成功<br>";
} catch (Exception $e) {
    echo "❌ functions.php 包含失败: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "❌ functions.php 致命错误: " . $e->getMessage() . "<br>";
}

echo "<h2>3. 函数测试</h2>";

if (function_exists('getDatabase')) {
    echo "✅ getDatabase 函数存在<br>";
    try {
        $db = getDatabase();
        if ($db) {
            echo "✅ 数据库连接成功<br>";
        } else {
            echo "❌ 数据库连接失败<br>";
        }
    } catch (Exception $e) {
        echo "❌ 数据库连接错误: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ getDatabase 函数不存在<br>";
}

if (function_exists('fetchAll')) {
    echo "✅ fetchAll 函数存在<br>";
} else {
    echo "❌ fetchAll 函数不存在<br>";
}

if (function_exists('isLoggedIn')) {
    echo "✅ isLoggedIn 函数存在<br>";
} else {
    echo "❌ isLoggedIn 函数不存在<br>";
}

echo "<h2>4. 会话测试</h2>";
session_start();
echo "会话ID: " . session_id() . "<br>";

echo "<h2>调试完成</h2>";
echo "<p><a href='index.php'>尝试访问管理后台</a></p>";
?>