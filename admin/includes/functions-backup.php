<?php
/**
 * 备份的通用函数库 - 只包含核心函数
 */

/**
 * 检查用户是否已登录
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * 获取当前登录用户信息
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = getDatabase();
        if (!$db) return null;
        
        $stmt = $db->prepare("SELECT id, username, email, avatar, last_login FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return ['username' => 'Admin', 'avatar' => 'assets/images/default-avatar.svg'];
    }
}

/**
 * 执行查询并返回所有结果
 */
function fetchAll($sql, $params = []) {
    try {
        $db = getDatabase();
        if (!$db) return [];
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('fetchAll错误: ' . $e->getMessage());
        return [];
    }
}

/**
 * 执行查询并返回单个结果
 */
function fetchOne($sql, $params = []) {
    try {
        $db = getDatabase();
        if (!$db) return null;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log('fetchOne错误: ' . $e->getMessage());
        return null;
    }
}

/**
 * 插入数据
 */
function insert($table, $data) {
    try {
        $db = getDatabase();
        if (!$db) return false;
        
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($data);
    } catch (Exception $e) {
        error_log('insert错误: ' . $e->getMessage());
        return false;
    }
}

/**
 * 更新数据
 */
function update($table, $data, $where, $whereParams = []) {
    try {
        $db = getDatabase();
        if (!$db) return false;
        
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log('update错误: ' . $e->getMessage());
        return false;
    }
}

/**
 * 删除数据
 */
function delete($table, $where, $whereParams = []) {
    try {
        $db = getDatabase();
        if (!$db) return false;
        
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $db->prepare($sql);
        return $stmt->execute($whereParams);
    } catch (Exception $e) {
        error_log('delete错误: ' . $e->getMessage());
        return false;
    }
}

/**
 * 用户登出
 */
function logout() {
    session_destroy();
}

/**
 * 记录系统日志
 */
function logAction($action, $description, $userId = null) {
    try {
        $db = getDatabase();
        if (!$db) return;
        
        $userId = $userId ?? ($_SESSION['admin_id'] ?? null);
        
        $stmt = $db->prepare("
            INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("日志记录失败: " . $e->getMessage());
    }
}