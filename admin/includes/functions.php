<?php
/**
 * 通用函数库
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
    
    $db = getDatabase();
    $stmt = $db->prepare("SELECT id, username, email, avatar, last_login FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}

/**
 * 用户登录
 */
function login($username, $password) {
    $db = getDatabase();
    
    $stmt = $db->prepare("SELECT id, username, email, password, status FROM admin_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => '用户名或密码错误'];
    }
    
    if ($user['status'] != 1) {
        return ['success' => false, 'message' => '账号已被禁用'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => '用户名或密码错误'];
    }
    
    // 登录成功
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    
    // 更新登录信息
    $stmt = $db->prepare("UPDATE admin_users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // 记录日志
    logAction('login', '用户登录成功');
    
    return ['success' => true, 'message' => '登录成功'];
}

/**
 * 用户登出
 */
function logout() {
    logAction('logout', '用户退出登录');
    session_destroy();
}

/**
 * 设置消息提示
 */
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type
    ];
}

/**
 * 记录系统日志
 */
function logAction($action, $description, $userId = null) {
    try {
        $db = getDatabase();
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

/**
 * 安全的文件上传
 */
function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'], $maxSize = 5242880) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => '没有选择文件'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '文件上传失败'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '文件大小超出限制'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => '不支持的文件类型'];
    }
    
    // 生成唯一文件名
    $filename = uniqid() . '.' . $extension;
    $uploadPath = '../assets/images/' . $filename;
    
    // 确保上传目录存在
    $uploadDir = dirname($uploadPath);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => 'assets/images/' . $filename,
            'url' => '../assets/images/' . $filename
        ];
    }
    
    return ['success' => false, 'message' => '文件保存失败'];
}

/**
 * 删除文件
 */
function deleteFile($path) {
    $fullPath = '../' . $path;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * 获取系统统计信息
 */
function getSystemStats() {
    $db = getDatabase();
    
    // 导航项目总数
    $stmt = $db->query("SELECT COUNT(*) as count FROM nav_items WHERE is_active = 1");
    $navCount = $stmt->fetch()['count'];
    
    // 分类总数
    $stmt = $db->query("SELECT COUNT(*) as count FROM nav_categories WHERE is_active = 1");
    $categoryCount = $stmt->fetch()['count'];
    
    // 今日访问量
    $stmt = $db->prepare("SELECT page_views, unique_visitors FROM visit_stats WHERE date = CURDATE()");
    $stmt->execute();
    $todayStats = $stmt->fetch();
    
    // 总点击量
    $stmt = $db->query("SELECT SUM(click_count) as total FROM nav_items");
    $totalClicks = $stmt->fetch()['total'] ?? 0;
    
    return [
        'nav_count' => $navCount,
        'category_count' => $categoryCount,
        'today_views' => $todayStats['page_views'] ?? 0,
        'today_visitors' => $todayStats['unique_visitors'] ?? 0,
        'total_clicks' => $totalClicks
    ];
}

/**
 * 获取最近访问统计
 */
function getRecentStats($days = 7) {
    $db = getDatabase();
    
    $stmt = $db->prepare("
        SELECT date, page_views, unique_visitors 
        FROM visit_stats 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ORDER BY date ASC
    ");
    $stmt->execute([$days]);
    
    return $stmt->fetchAll();
}

/**
 * 获取热门导航项目
 */
function getPopularNavItems($limit = 10) {
    $db = getDatabase();
    
    $stmt = $db->prepare("
        SELECT n.name, n.url, n.click_count, c.name as category_name
        FROM nav_items n
        LEFT JOIN nav_categories c ON n.category_id = c.id
        WHERE n.is_active = 1
        ORDER BY n.click_count DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}

/**
 * 验证CSRF令牌
 */
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 生成CSRF令牌
 */
function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 清理HTML输入
 */
function cleanInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * 验证邮箱格式
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 验证URL格式
 */
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * 生成随机字符串
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * 时间格式化
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return '刚刚';
    if ($time < 3600) return floor($time/60) . '分钟前';
    if ($time < 86400) return floor($time/3600) . '小时前';
    if ($time < 2592000) return floor($time/86400) . '天前';
    if ($time < 31536000) return floor($time/2592000) . '个月前';
    
    return floor($time/31536000) . '年前';
}

/**
 * 分页函数
 */
function paginate($total, $page = 1, $perPage = 20) {
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
        'prev_page' => $page - 1,
        'next_page' => $page + 1
    ];
}

/**
 * 生成分页HTML
 */
function renderPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // 上一页
    if ($pagination['has_prev']) {
        $html .= '<a href="' . $baseUrl . '&page=' . $pagination['prev_page'] . '" class="page-btn">‹ 上一页</a>';
    }
    
    // 页码
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '&page=1" class="page-num">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-dots">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $class = $i === $pagination['current_page'] ? 'page-num active' : 'page-num';
        $html .= '<a href="' . $baseUrl . '&page=' . $i . '" class="' . $class . '">' . $i . '</a>';
    }
    
    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) {
            $html .= '<span class="page-dots">...</span>';
        }
        $html .= '<a href="' . $baseUrl . '&page=' . $pagination['total_pages'] . '" class="page-num">' . $pagination['total_pages'] . '</a>';
    }
    
    // 下一页
    if ($pagination['has_next']) {
        $html .= '<a href="' . $baseUrl . '&page=' . $pagination['next_page'] . '" class="page-btn">下一页 ›</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * 通用数据库操作函数
 */

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