<?php
/**
 * 数据库连接和操作类
 */

// 数据库连接实例
$database = null;

/**
 * 获取数据库连接
 */
function getDatabase() {
    global $database;
    
    if ($database === null) {
        try {
            // 如果API入口文件定义了配置文件路径常量，则使用它
            if (defined('DB_CONFIG_PATH') && file_exists(DB_CONFIG_PATH)) {
                $configPath = DB_CONFIG_PATH;
            } else {
                // 否则尝试多种可能的路径
                $configPath = __DIR__ . '/../config/database.php';
                
                // 如果文件不存在，尝试其他可能的路径
                if (!file_exists($configPath)) {
                    $possiblePaths = [
                        $_SERVER['DOCUMENT_ROOT'] . '/admin/config/database.php',
                        dirname(dirname(__DIR__)) . '/admin/config/database.php',
                        dirname(__DIR__) . '/config/database.php'
                    ];
                    
                    foreach ($possiblePaths as $path) {
                        if (file_exists($path)) {
                            $configPath = $path;
                            break;
                        }
                    }
                }
            }
            
            if (!file_exists($configPath)) {
                error_log('数据库配置文件不存在，尝试过的路径: ' . $configPath);
                return false;
            }
            
            $config = require $configPath;
            
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            $database = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
        } catch (PDOException $e) {
            // 记录错误但不终止程序
            error_log('数据库连接失败: ' . $e->getMessage());
            return false;
        }
    }
    
    return $database;
}

/**
 * 执行查询并返回结果
 */
function query($sql, $params = []) {
    $db = getDatabase();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * 获取单行数据
 * 注意：此函数已在functions.php中定义，这里不再重复定义
 */
// 使用functions.php中的fetchOne函数

/**
 * 获取多行数据
 * 注意：此函数已在functions.php中定义，这里不再重复定义
 */
// 使用functions.php中的fetchAll函数

/**
 * 执行插入操作
 * 注意：此函数已在functions.php中定义，这里不再重复定义
 */
// 使用functions.php中的insert函数

/**
 * 执行更新操作
 * 注意：此函数已在functions.php中定义，这里不再重复定义
 */
// 使用functions.php中的update函数

/**
 * 执行删除操作
 * 注意：此函数已在functions.php中定义，这里不再重复定义
 */
// 使用functions.php中的delete函数

/**
 * 检查记录是否存在
 */
function exists($table, $where, $whereParams = []) {
    $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
    $result = fetchOne($sql, $whereParams);
    return $result !== false;
}

/**
 * 获取记录总数
 */
function countRecords($table, $where = '1=1', $whereParams = []) {
    $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
    $result = fetchOne($sql, $whereParams);
    return $result ? (int)$result['count'] : 0;
}

/**
 * 开始事务
 */
function beginTransaction() {
    $db = getDatabase();
    return $db->beginTransaction();
}

/**
 * 提交事务
 */
function commit() {
    $db = getDatabase();
    return $db->commit();
}

/**
 * 回滚事务
 */
function rollback() {
    $db = getDatabase();
    return $db->rollback();
}

/**
 * 个人信息相关操作
 */
class ProfileModel {
    
    /**
     * 获取个人信息
     */
    public static function get() {
        return fetchOne("SELECT * FROM profile WHERE id = 1");
    }
    
    /**
     * 更新个人信息
     */
    public static function update($data) {
        return update('profile', $data, 'id = 1');
    }
}

/**
 * 社交链接相关操作
 */
class SocialLinkModel {
    
    /**
     * 获取所有社交链接
     */
    public static function getAll() {
        return fetchAll("SELECT * FROM social_links ORDER BY sort_order ASC");
    }
    
    /**
     * 根据ID获取社交链接
     */
    public static function getById($id) {
        return fetchOne("SELECT * FROM social_links WHERE id = ?", [$id]);
    }
    
    /**
     * 创建社交链接
     */
    public static function create($data) {
        return insert('social_links', $data);
    }
    
    /**
     * 更新社交链接
     */
    public static function update($id, $data) {
        return update('social_links', $data, 'id = ?', [$id]);
    }
    
    /**
     * 删除社交链接
     */
    public static function delete($id) {
        return delete('social_links', 'id = ?', [$id]);
    }
    
    /**
     * 更新排序
     */
    public static function updateSort($id, $sortOrder) {
        return update('social_links', ['sort_order' => $sortOrder], 'id = ?', [$id]);
    }
}

/**
 * 导航分类相关操作
 */
class CategoryModel {
    
    /**
     * 获取所有分类
     */
    public static function getAll() {
        return fetchAll("SELECT * FROM nav_categories ORDER BY sort_order ASC");
    }
    
    /**
     * 根据ID获取分类
     */
    public static function getById($id) {
        return fetchOne("SELECT * FROM nav_categories WHERE id = ?", [$id]);
    }
    
    /**
     * 创建分类
     */
    public static function create($data) {
        return insert('nav_categories', $data);
    }
    
    /**
     * 更新分类
     */
    public static function update($id, $data) {
        return update('nav_categories', $data, 'id = ?', [$id]);
    }
    
    /**
     * 删除分类
     */
    public static function delete($id) {
        // 检查是否有关联的导航项目
        $count = countRecords('nav_items', 'category_id = ?', [$id]);
        if ($count > 0) {
            return false;
        }
        
        return delete('nav_categories', 'id = ?', [$id]);
    }
    
    /**
     * 获取分类统计
     */
    public static function getStats() {
        return fetchAll("
            SELECT c.*, COUNT(n.id) as item_count
            FROM nav_categories c
            LEFT JOIN nav_items n ON c.id = n.category_id AND n.is_active = 1
            GROUP BY c.id
            ORDER BY c.sort_order ASC
        ");
    }
}

/**
 * 导航项目相关操作
 */
class NavItemModel {
    
    /**
     * 获取所有导航项目
     */
    public static function getAll($categoryId = null, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $where = $categoryId ? "WHERE n.category_id = ?" : "";
        $params = $categoryId ? [$categoryId] : [];
        
        $sql = "
            SELECT n.*, c.name as category_name, c.color as category_color
            FROM nav_items n
            LEFT JOIN nav_categories c ON n.category_id = c.id
            {$where}
            ORDER BY n.sort_order ASC, n.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * 获取导航项目总数
     */
    public static function getCount($categoryId = null) {
        $where = $categoryId ? "WHERE category_id = ?" : "";
        $params = $categoryId ? [$categoryId] : [];
        
        return countRecords('nav_items', $where, $params);
    }
    
    /**
     * 根据ID获取导航项目
     */
    public static function getById($id) {
        return fetchOne("
            SELECT n.*, c.name as category_name
            FROM nav_items n
            LEFT JOIN nav_categories c ON n.category_id = c.id
            WHERE n.id = ?
        ", [$id]);
    }
    
    /**
     * 创建导航项目
     */
    public static function create($data) {
        return insert('nav_items', $data);
    }
    
    /**
     * 更新导航项目
     */
    public static function update($id, $data) {
        return update('nav_items', $data, 'id = ?', [$id]);
    }
    
    /**
     * 删除导航项目
     */
    public static function delete($id) {
        return delete('nav_items', 'id = ?', [$id]);
    }
    
    /**
     * 增加点击次数
     */
    public static function incrementClick($id) {
        return update('nav_items', ['click_count' => 'click_count + 1'], 'id = ?', [$id]);
    }
    
    /**
     * 搜索导航项目
     */
    public static function search($keyword, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT n.*, c.name as category_name, c.color as category_color
            FROM nav_items n
            LEFT JOIN nav_categories c ON n.category_id = c.id
            WHERE n.name LIKE ? OR n.description LIKE ? OR n.tags LIKE ?
            ORDER BY n.sort_order ASC, n.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        
        $searchTerm = "%{$keyword}%";
        return fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
    }
}

/**
 * 主题相关操作
 */
class ThemeModel {
    
    /**
     * 获取所有主题
     */
    public static function getAll() {
        return fetchAll("SELECT * FROM themes ORDER BY is_default DESC, created_at ASC");
    }
    
    /**
     * 根据ID获取主题
     */
    public static function getById($id) {
        return fetchOne("SELECT * FROM themes WHERE id = ?", [$id]);
    }
    
    /**
     * 获取默认主题
     */
    public static function getDefault() {
        return fetchOne("SELECT * FROM themes WHERE is_default = 1");
    }
    
    /**
     * 创建主题
     */
    public static function create($data) {
        return insert('themes', $data);
    }
    
    /**
     * 更新主题
     */
    public static function update($id, $data) {
        return update('themes', $data, 'id = ?', [$id]);
    }
    
    /**
     * 删除主题
     */
    public static function delete($id) {
        // 不能删除默认主题
        $theme = self::getById($id);
        if ($theme && $theme['is_default']) {
            return false;
        }
        
        return delete('themes', 'id = ?', [$id]);
    }
    
    /**
     * 设置默认主题
     */
    public static function setDefault($id) {
        beginTransaction();
        
        try {
            // 取消所有主题的默认状态
            update('themes', ['is_default' => 0], '1=1');
            
            // 设置新的默认主题
            update('themes', ['is_default' => 1], 'id = ?', [$id]);
            
            commit();
            return true;
        } catch (Exception $e) {
            rollback();
            return false;
        }
    }
}

/**
 * 系统设置相关操作
 */
class SettingModel {
    
    /**
     * 获取设置值
     */
    public static function get($key, $default = null) {
        $result = fetchOne("SELECT value FROM settings WHERE key_name = ?", [$key]);
        
        if ($result) {
            return json_decode($result['value'], true);
        }
        
        return $default;
    }
    
    /**
     * 设置值
     */
    public static function set($key, $value, $description = '') {
        $jsonValue = json_encode($value);
        
        if (exists('settings', 'key_name = ?', [$key])) {
            return update('settings', ['value' => $jsonValue], 'key_name = ?', [$key]);
        } else {
            return insert('settings', [
                'key_name' => $key,
                'value' => $jsonValue,
                'description' => $description
            ]);
        }
    }
    
    /**
     * 获取所有设置
     */
    public static function getAll() {
        $results = fetchAll("SELECT * FROM settings ORDER BY group_name, key_name");
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['key_name']] = json_decode($row['value'], true);
        }
        
        return $settings;
    }
    
    /**
     * 按分组获取设置
     */
    public static function getByGroup($group) {
        $results = fetchAll("SELECT * FROM settings WHERE group_name = ? ORDER BY key_name", [$group]);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['key_name']] = [
                'value' => json_decode($row['value'], true),
                'description' => $row['description']
            ];
        }
        
        return $settings;
    }
}

/**
 * 访问统计相关操作
 */
class StatsModel {
    
    /**
     * 记录访问
     */
    public static function recordVisit() {
        $today = date('Y-m-d');
        
        if (exists('visit_stats', 'date = ?', [$today])) {
            update('visit_stats', [
                'page_views' => 'page_views + 1'
            ], 'date = ?', [$today]);
        } else {
            insert('visit_stats', [
                'date' => $today,
                'page_views' => 1,
                'unique_visitors' => 1
            ]);
        }
    }
    
    /**
     * 获取统计数据
     */
    public static function getStats($days = 30) {
        return fetchAll("
            SELECT date, page_views, unique_visitors
            FROM visit_stats
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ORDER BY date ASC
        ", [$days]);
    }
    
    /**
     * 获取总统计
     */
    public static function getTotalStats() {
        return fetchOne("
            SELECT 
                SUM(page_views) as total_views,
                SUM(unique_visitors) as total_visitors,
                AVG(page_views) as avg_daily_views
            FROM visit_stats
        ");
    }
}
?>