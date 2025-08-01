<?php
/**
 * 检查数据库表结构
 */

// 引入数据库配置
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/database.php';

// 显示所有错误
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 获取数据库连接
$db = getDatabase();

// 检查profile表结构
$stmt = $db->query("DESCRIBE profile");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Profile表结构</h1>";
echo "<table border='1'>";
echo "<tr><th>字段</th><th>类型</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>{$column['Field']}</td>";
    echo "<td>{$column['Type']}</td>";
    echo "<td>{$column['Null']}</td>";
    echo "<td>{$column['Key']}</td>";
    echo "<td>{$column['Default']}</td>";
    echo "<td>{$column['Extra']}</td>";
    echo "</tr>";
}

echo "</table>";