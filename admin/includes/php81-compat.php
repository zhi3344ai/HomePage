<?php
/**
 * PHP 8.1 兼容性配置
 * 在所有 PHP 文件开头包含此文件
 */

// 错误报告配置 - 开启错误显示（调试模式）
error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');
ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// 确保日志目录存在
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// 不清理输出，以便查看错误信息
// if (ob_get_level()) {
//     ob_clean();
// }

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 设置内存限制
ini_set('memory_limit', '256M');

// 设置执行时间
ini_set('max_execution_time', 300);

// 设置字符集
ini_set('default_charset', 'UTF-8');

// 会话配置
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
?>