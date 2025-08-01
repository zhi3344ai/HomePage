<?php
/**
 * 数据库配置文件
 * 
 * 此文件由安装程序自动生成，包含数据库连接信息
 * 请勿手动修改此文件，除非您确切知道您在做什么
 */

return [
    'host' => '{{DB_HOST}}',
    'port' => '{{DB_PORT}}', 
    'database' => '{{DB_NAME}}',
    'username' => '{{DB_USER}}',
    'password' => '{{DB_PASSWORD}}',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];