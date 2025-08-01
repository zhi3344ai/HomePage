<?php
/**
 * 前台API接口
 * 为前台页面提供数据
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 检查是否已安装
if (!file_exists('../admin/config/database.php')) {
    http_response_code(503);
    echo json_encode(['error' => '系统未安装']);
    exit;
}

// 确保数据库配置文件存在
$dbConfigPath = '../admin/config/database.php';
if (!file_exists($dbConfigPath)) {
    http_response_code(503);
    echo json_encode(['error' => '系统未安装或配置文件不存在']);
    exit;
}

// 引入必要文件
if (file_exists('../admin/config/config.php')) {
    require_once '../admin/config/config.php';
}

// 手动设置配置文件路径
define('DB_CONFIG_PATH', realpath($dbConfigPath));

require_once '../admin/includes/database.php';
require_once '../admin/includes/functions.php';

// 获取请求参数
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // 路由处理
    switch ($action) {
        case 'profile':
            handleProfile();
            break;
            
        case 'social':
            handleSocialLinks();
            break;
            
        case 'navigation':
            handleNavigation();
            break;
            
        case 'categories':
            handleCategories();
            break;
            
        case 'theme':
            handleTheme();
            break;
            
        case 'settings':
            handleSettings();
            break;
            
        case 'click':
            handleClick();
            break;
            
        case 'visit':
            handleVisit();
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => '接口不存在']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '服务器错误: ' . $e->getMessage()]);
}

/**
 * 处理个人信息请求
 */
function handleProfile() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => '方法不允许']);
        return;
    }
    
    $profile = fetchOne("SELECT * FROM profile WHERE id = 1");
    
    if (!$profile) {
        $profile = [
            'name' => '个人导航',
            'title' => '欢迎使用',
            'description' => '这是一个个人导航页面',
            'avatar' => 'assets/images/avatar.svg',
            'status_text' => '在线',
            'status_type' => 'online',
            'projects_count' => 0,
            'experience_years' => 0,
            'skills_count' => 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $profile
    ]);
}

/**
 * 处理社交链接请求
 */
function handleSocialLinks() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => '方法不允许']);
        return;
    }
    
    $links = fetchAll("
        SELECT platform, url, icon, color 
        FROM social_links 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");
    
    echo json_encode([
        'success' => true,
        'data' => $links
    ]);
}

/**
 * 处理导航数据请求
 */
function handleNavigation() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => '方法不允许']);
        return;
    }
    
    try {
        $categoryId = $_GET['category_id'] ?? null;
        $featured = $_GET['featured'] ?? false;
        
        // 尝试从数据库获取数据
        try {
            // 构建查询条件
            $where = ['n.is_active = 1'];
            $params = [];
            
            if ($categoryId) {
                $where[] = 'n.category_id = ?';
                $params[] = $categoryId;
            }
            
            if ($featured) {
                $where[] = 'n.is_featured = 1';
            }
            
            $whereClause = implode(' AND ', $where);
            
            $items = fetchAll("
                SELECT 
                    n.id,
                    n.name,
                    n.url,
                    n.icon,
                    n.description,
                    n.click_count,
                    n.is_featured,
                    n.target,
                    c.name as category_name,
                    c.color as category_color
                FROM nav_items n
                LEFT JOIN nav_categories c ON n.category_id = c.id
                WHERE {$whereClause}
                ORDER BY n.sort_order ASC, n.created_at DESC
            ", $params);
            
            // 添加空标签数组，兼容前端代码
            foreach ($items as &$item) {
                $item['tags'] = [];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $items
            ]);
            return;
        } catch (Exception $e) {
            // 如果数据库查询失败，使用静态数据作为备份
            error_log("导航数据库查询失败: " . $e->getMessage());
        }
        
        // 使用静态数据作为备份
        $items = [
            [
                'id' => 1,
                'name' => '哈勃网',
                'url' => 'https://rabi.com',
                'icon' => '🌐',
                'description' => '测试网站',
                'tags' => ['测试', '网站'],
                'click_count' => 0,
                'is_featured' => 1,
                'target' => '_blank',
                'category_name' => '欧罗网络',
                'category_color' => '#ff0000'
            ],
            [
                'id' => 2,
                'name' => 'Home',
                'url' => 'https://rabi.com',
                'icon' => '🏠',
                'description' => '首页网站',
                'tags' => ['首页'],
                'click_count' => 0,
                'is_featured' => 0,
                'target' => '_blank',
                'category_name' => '欧罗网络',
                'category_color' => '#ff0000'
            ],
            [
                'id' => 3,
                'name' => 'GitHub',
                'url' => 'https://github.com',
                'icon' => '🐙',
                'description' => '全球最大的代码托管平台',
                'tags' => ['开发工具', '代码托管'],
                'click_count' => 0,
                'is_featured' => 1,
                'target' => '_blank',
                'category_name' => '开发工具',
                'category_color' => '#00ff00'
            ],
            [
                'id' => 4,
                'name' => 'Figma',
                'url' => 'https://figma.com',
                'icon' => '🎨',
                'description' => '在线设计工具',
                'tags' => ['设计', 'UI'],
                'click_count' => 0,
                'is_featured' => 1,
                'target' => '_blank',
                'category_name' => '设计资源',
                'category_color' => '#ff00ff'
            ],
            [
                'id' => 5,
                'name' => 'MDN Web Docs',
                'url' => 'https://developer.mozilla.org',
                'icon' => '📚',
                'description' => 'Web开发文档',
                'tags' => ['文档', '学习'],
                'click_count' => 0,
                'is_featured' => 1,
                'target' => '_blank',
                'category_name' => '学习资源',
                'category_color' => '#0000ff'
            ],
            [
                'id' => 6,
                'name' => 'Google',
                'url' => 'https://google.com',
                'icon' => '🔍',
                'description' => '全球最大的搜索引擎',
                'tags' => ['搜索', '工具'],
                'click_count' => 0,
                'is_featured' => 1,
                'target' => '_blank',
                'category_name' => '常用工具',
                'category_color' => '#ffff00'
            ],
            [
                'id' => 7,
                'name' => 'YouTube',
                'url' => 'https://youtube.com',
                'icon' => '📺',
                'description' => '视频分享平台',
                'tags' => ['视频', '娱乐'],
                'click_count' => 0,
                'is_featured' => 0,
                'target' => '_blank',
                'category_name' => '娱乐休闲',
                'category_color' => '#ff0000'
            ],
            [
                'id' => 8,
                'name' => 'VS Code',
                'url' => 'https://code.visualstudio.com',
                'icon' => '💻',
                'description' => '微软开发的代码编辑器',
                'tags' => ['开发工具', '编辑器'],
                'click_count' => 0,
                'is_featured' => 1,
                'target' => '_blank',
                'category_name' => '开发工具',
                'category_color' => '#00ff00'
            ],
            [
                'id' => 9,
                'name' => 'Adobe Creative Cloud',
                'url' => 'https://www.adobe.com/creativecloud.html',
                'icon' => '🎭',
                'description' => 'Adobe创意套件',
                'tags' => ['设计', '创意'],
                'click_count' => 0,
                'is_featured' => 0,
                'target' => '_blank',
                'category_name' => '设计资源',
                'category_color' => '#ff00ff'
            ],
            [
                'id' => 10,
                'name' => '菜鸟教程',
                'url' => 'https://www.runoob.com',
                'icon' => '🐣',
                'description' => '编程学习网站',
                'tags' => ['学习', '编程'],
                'click_count' => 0,
                'is_featured' => 0,
                'target' => '_blank',
                'category_name' => '学习资源',
                'category_color' => '#0000ff'
            ],
            [
                'id' => 11,
                'name' => '百度翻译',
                'url' => 'https://fanyi.baidu.com',
                'icon' => '🔄',
                'description' => '在线翻译工具',
                'tags' => ['翻译', '工具'],
                'click_count' => 0,
                'is_featured' => 0,
                'target' => '_blank',
                'category_name' => '常用工具',
                'category_color' => '#ffff00'
            ]
        ];
        
        // 如果有分类ID过滤
        if ($categoryId) {
            $categoryName = '';
            switch ($categoryId) {
                case 1: $categoryName = '欧罗网络'; break;
                case 2: $categoryName = '开发工具'; break;
                case 3: $categoryName = '设计资源'; break;
                case 4: $categoryName = '学习资源'; break;
                case 5: $categoryName = '常用工具'; break;
                case 6: $categoryName = '娱乐休闲'; break;
            }
            
            $items = array_filter($items, function($item) use ($categoryName) {
                return $item['category_name'] === $categoryName;
            });
        }
        
        // 如果只要推荐项目
        if ($featured) {
            $items = array_filter($items, function($item) {
                return $item['is_featured'] == 1;
            });
        }
        
        echo json_encode([
            'success' => true,
            'data' => array_values($items)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '服务器错误: ' . $e->getMessage()]);
    }
}

/**
 * 处理分类数据请求
 */
function handleCategories() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => '方法不允许']);
        return;
    }
    
    try {
        $withCount = $_GET['with_count'] ?? false;
        
        // 尝试从数据库获取数据
        try {
            if ($withCount) {
                $categories = fetchAll("
                    SELECT 
                        c.*,
                        COUNT(n.id) as item_count
                    FROM nav_categories c
                    LEFT JOIN nav_items n ON c.id = n.category_id AND n.is_active = 1
                    WHERE c.is_active = 1
                    GROUP BY c.id
                    ORDER BY c.sort_order ASC
                ");
            } else {
                $categories = fetchAll("
                    SELECT * FROM nav_categories 
                    WHERE is_active = 1 
                    ORDER BY sort_order ASC
                ");
            }
            
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
            return;
        } catch (Exception $e) {
            // 如果数据库查询失败，使用静态数据作为备份
            error_log("分类数据库查询失败: " . $e->getMessage());
        }
        
        // 使用静态数据作为备份
        $categories = [
            [
                'id' => 1,
                'name' => '欧罗网络',
                'icon' => '🌐',
                'color' => '#ff0000',
                'sort_order' => 1,
                'is_active' => 1,
                'item_count' => 2
            ],
            [
                'id' => 2,
                'name' => '开发工具',
                'icon' => '🔧',
                'color' => '#00ff00',
                'sort_order' => 2,
                'is_active' => 1,
                'item_count' => 2
            ],
            [
                'id' => 3,
                'name' => '设计资源',
                'icon' => '🎨',
                'color' => '#ff00ff',
                'sort_order' => 3,
                'is_active' => 1,
                'item_count' => 2
            ],
            [
                'id' => 4,
                'name' => '学习资源',
                'icon' => '📚',
                'color' => '#0000ff',
                'sort_order' => 4,
                'is_active' => 1,
                'item_count' => 2
            ],
            [
                'id' => 5,
                'name' => '常用工具',
                'icon' => '🔍',
                'color' => '#ffff00',
                'sort_order' => 5,
                'is_active' => 1,
                'item_count' => 2
            ],
            [
                'id' => 6,
                'name' => '娱乐休闲',
                'icon' => '📺',
                'color' => '#ff0000',
                'sort_order' => 6,
                'is_active' => 1,
                'item_count' => 1
            ],
            [
                'id' => 7,
                'name' => '测试分类2',
                'icon' => '📊',
                'color' => '#00ffff',
                'sort_order' => 7,
                'is_active' => 1,
                'item_count' => 0
            ]
        ];
        
        // 如果不需要计数，移除item_count字段
        if (!$withCount) {
            foreach ($categories as &$category) {
                unset($category['item_count']);
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '服务器错误: ' . $e->getMessage()]);
    }
}

/**
 * 处理主题数据请求
 */
function handleTheme() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => '方法不允许']);
        return;
    }
    
    $themeId = $_GET['id'] ?? null;
    
    if ($themeId) {
        // 获取指定主题
        $theme = fetchOne("SELECT * FROM themes WHERE id = ? AND is_active = 1", [$themeId]);
        
        if (!$theme) {
            http_response_code(404);
            echo json_encode(['error' => '主题不存在']);
            return;
        }
        
        $theme['colors'] = json_decode($theme['colors'], true);
        $theme['effects'] = json_decode($theme['effects'] ?? '{}', true);
        
        echo json_encode([
            'success' => true,
            'data' => $theme
        ]);
    } else {
        // 获取所有可用主题
        $themes = fetchAll("SELECT * FROM themes WHERE is_active = 1 ORDER BY is_default DESC, created_at ASC");
        
        foreach ($themes as &$theme) {
            $theme['colors'] = json_decode($theme['colors'], true);
            $theme['effects'] = json_decode($theme['effects'] ?? '{}', true);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $themes
        ]);
    }
}

/**
 * 处理系统设置请求
 */
function handleSettings() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => '方法不允许']);
        return;
    }
    
    $key = $_GET['key'] ?? null;
    
    if ($key) {
        // 获取指定设置
        $setting = fetchOne("SELECT value FROM settings WHERE key_name = ?", [$key]);
        
        if (!$setting) {
            http_response_code(404);
            echo json_encode(['error' => '设置不存在']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => json_decode($setting['value'], true)
        ]);
    } else {
        // 获取所有前台相关设置
        $frontendKeys = [
            'site_title',
            'site_description',
            'animations_enabled',
            'particles_enabled',
            'particle_count',
            'effects_quality'
        ];
        
        $settings = [];
        foreach ($frontendKeys as $key) {
            $setting = fetchOne("SELECT value FROM settings WHERE key_name = ?", [$key]);
            if ($setting) {
                $settings[$key] = json_decode($setting['value'], true);
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
    }
}

/**
 * 处理点击统计
 */
function handleClick() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => '方法不允许']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $itemId = $input['item_id'] ?? null;
    
    if (!$itemId) {
        http_response_code(400);
        echo json_encode(['error' => '缺少参数']);
        return;
    }
    
    // 检查导航项目是否存在
    $item = fetchOne("SELECT id FROM nav_items WHERE id = ? AND is_active = 1", [$itemId]);
    
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => '导航项目不存在']);
        return;
    }
    
    // 增加点击次数
    update('nav_items', ['click_count' => 'click_count + 1'], 'id = ?', [$itemId]);
    
    // 记录到统计表（可选）
    $today = date('Y-m-d');
    $stats = fetchOne("SELECT nav_clicks FROM visit_stats WHERE date = ?", [$today]);
    
    if ($stats) {
        $navClicks = json_decode($stats['nav_clicks'] ?? '{}', true);
        $navClicks[$itemId] = ($navClicks[$itemId] ?? 0) + 1;
        
        update('visit_stats', [
            'nav_clicks' => json_encode($navClicks)
        ], 'date = ?', [$today]);
    } else {
        insert('visit_stats', [
            'date' => $today,
            'page_views' => 0,
            'unique_visitors' => 0,
            'nav_clicks' => json_encode([$itemId => 1])
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '统计成功'
    ]);
}

/**
 * 处理访问统计
 */
function handleVisit() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => '方法不允许']);
        return;
    }
    
    $today = date('Y-m-d');
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // 检查今天的统计记录
    $stats = fetchOne("SELECT * FROM visit_stats WHERE date = ?", [$today]);
    
    if ($stats) {
        // 更新页面浏览量
        update('visit_stats', [
            'page_views' => 'page_views + 1'
        ], 'date = ?', [$today]);
        
        // 更新用户代理统计
        $userAgents = json_decode($stats['user_agents'] ?? '{}', true);
        $userAgents[$userAgent] = ($userAgents[$userAgent] ?? 0) + 1;
        
        // 更新来源统计
        $referrers = json_decode($stats['referrers'] ?? '{}', true);
        if ($referrer) {
            $referrers[$referrer] = ($referrers[$referrer] ?? 0) + 1;
        }
        
        update('visit_stats', [
            'user_agents' => json_encode($userAgents),
            'referrers' => json_encode($referrers)
        ], 'date = ?', [$today]);
        
    } else {
        // 创建新的统计记录
        insert('visit_stats', [
            'date' => $today,
            'page_views' => 1,
            'unique_visitors' => 1,
            'user_agents' => json_encode([$userAgent => 1]),
            'referrers' => json_encode($referrer ? [$referrer => 1] : [])
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '访问统计成功'
    ]);
}

/**
 * 记录错误日志
 */
function logError($message, $context = []) {
    $logFile = '../admin/logs/api_error.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = $context ? json_encode($context) : '';
    $logMessage = "[{$timestamp}] {$message} {$contextStr}" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
?>