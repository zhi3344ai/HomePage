<?php
/**
 * 导航数据API - 带缓存的版本
 * 优先从数据库获取数据，失败时使用缓存数据
 */

// 缓存文件路径
define('CACHE_DIR', __DIR__ . '/cache');
define('NAV_CACHE_FILE', CACHE_DIR . '/navigation_cache.json');
define('CAT_CACHE_FILE', CACHE_DIR . '/categories_cache.json');

// 确保缓存目录存在
if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

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

// 获取请求参数
$action = $_GET['action'] ?? 'navigation';
$categoryId = $_GET['category_id'] ?? null;
$featured = $_GET['featured'] ?? false;

try {
    // 根据请求类型返回不同的数据
    switch ($action) {
        case 'navigation':
            handleNavigation($categoryId, $featured);
            break;
            
        case 'categories':
            handleCategories($_GET['with_count'] ?? false);
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
 * 处理导航数据请求
 */
function handleNavigation($categoryId = null, $featured = false) {
    // 尝试从数据库获取数据
    try {
        // 检查数据库配置文件是否存在
        $dbConfigPath = '../admin/config/database.php';
        if (file_exists($dbConfigPath)) {
            // 引入数据库连接
            require_once $dbConfigPath;
            require_once '../admin/includes/database.php';
            require_once '../admin/includes/functions.php';
            
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
                    c.color as category_color,
                    n.category_id,
                    c.icon as category_icon
                FROM nav_items n
                LEFT JOIN nav_categories c ON n.category_id = c.id
                WHERE {$whereClause}
                ORDER BY n.sort_order ASC, n.created_at DESC
            ", $params);
            
            // 处理图标路径和添加空标签数组（兼容前端代码）
            foreach ($items as &$item) {
                // 添加空标签数组，兼容前端代码
                $item['tags'] = [];
                
                // 处理图标路径
                if (isset($item['icon']) && !empty($item['icon'])) {
                    // 检查是否是文件路径
                    if (strpos($item['icon'], '/') !== false || strpos($item['icon'], '\\') !== false) {
                        // 确保路径正确
                        if (strpos($item['icon'], 'admin/img/') === 0) {
                            // 已经是相对路径，保持不变
                        } else if (strpos($item['icon'], '/admin/img/') === 0) {
                            // 去掉开头的斜杠
                            $item['icon'] = substr($item['icon'], 1);
                        }
                    }
                }
            }
            
            // 保存到缓存
            saveNavigationCache($items);
            
            echo json_encode([
                'success' => true,
                'data' => $items
            ]);
            return;
        }
    } catch (Exception $e) {
        // 记录错误但继续使用缓存数据
        error_log('数据库查询失败，尝试使用缓存数据: ' . $e->getMessage());
    }
    
    // 尝试从缓存获取数据
    $cachedItems = getNavigationCache();
    if ($cachedItems !== false) {
        // 如果有缓存数据，应用过滤条件
        if ($categoryId) {
            $cachedItems = array_filter($cachedItems, function($item) use ($categoryId) {
                return $item['category_id'] == $categoryId;
            });
        }
        
        if ($featured) {
            $cachedItems = array_filter($cachedItems, function($item) {
                return $item['is_featured'] == 1;
            });
        }
        
        echo json_encode([
            'success' => true,
            'data' => array_values($cachedItems),
            'from_cache' => true
        ]);
        return;
    }
    
    // 如果没有缓存或缓存失效，使用静态数据作为最后的备份
    $items = [
        [
            'id' => 1,
            'name' => '哈勃网',
            'url' => 'https://rabi.com',
            'icon' => '🌐', // 使用Emoji作为图标
            'description' => '测试网站',
            'tags' => ['测试', '网站'],
            'click_count' => 0,
            'is_featured' => 1,
            'target' => '_blank',
            'category_name' => '欧罗网络',
            'category_color' => '#ff0000',
            'category_id' => 1
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
            'category_color' => '#ff0000',
            'category_id' => 1
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
            'category_color' => '#00ff00',
            'category_id' => 2
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
            'category_color' => '#ff00ff',
            'category_id' => 3
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
            'category_color' => '#0000ff',
            'category_id' => 4
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
            'category_color' => '#ffff00',
            'category_id' => 5
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
            'category_color' => '#ff0000',
            'category_id' => 6
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
            'category_color' => '#00ff00',
            'category_id' => 2
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
            'category_color' => '#ff00ff',
            'category_id' => 3
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
            'category_color' => '#0000ff',
            'category_id' => 4
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
            'category_color' => '#ffff00',
            'category_id' => 5
        ]
    ];
    
    // 如果有分类ID过滤
    if ($categoryId) {
        $items = array_filter($items, function($item) use ($categoryId) {
            return $item['category_id'] == $categoryId;
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
}

/**
 * 保存导航数据到缓存
 */
function saveNavigationCache($data) {
    file_put_contents(NAV_CACHE_FILE, json_encode($data));
}

/**
 * 从缓存获取导航数据
 */
function getNavigationCache() {
    if (file_exists(NAV_CACHE_FILE)) {
        $cacheTime = filemtime(NAV_CACHE_FILE);
        // 缓存有效期为24小时
        if (time() - $cacheTime < 86400) {
            $data = json_decode(file_get_contents(NAV_CACHE_FILE), true);
            return $data;
        }
    }
    return false;
}

/**
 * 保存分类数据到缓存
 */
function saveCategoriesCache($data) {
    file_put_contents(CAT_CACHE_FILE, json_encode($data));
}

/**
 * 从缓存获取分类数据
 */
function getCategoriesCache() {
    if (file_exists(CAT_CACHE_FILE)) {
        $cacheTime = filemtime(CAT_CACHE_FILE);
        // 缓存有效期为24小时
        if (time() - $cacheTime < 86400) {
            $data = json_decode(file_get_contents(CAT_CACHE_FILE), true);
            return $data;
        }
    }
    return false;
}

/**
 * 处理分类数据请求
 */
function handleCategories($withCount = false) {
    // 尝试从数据库获取数据
    try {
        // 检查数据库配置文件是否存在
        $dbConfigPath = '../admin/config/database.php';
        if (file_exists($dbConfigPath)) {
            // 引入数据库连接
            require_once $dbConfigPath;
            require_once '../admin/includes/database.php';
            require_once '../admin/includes/functions.php';
            
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
            
            // 处理图标路径
            foreach ($categories as &$category) {
                if (isset($category['icon']) && !empty($category['icon'])) {
                    // 检查是否是文件路径
                    if (strpos($category['icon'], '/') !== false || strpos($category['icon'], '\\') !== false) {
                        // 确保路径正确
                        if (strpos($category['icon'], 'admin/img/') === 0) {
                            // 已经是相对路径，保持不变
                        } else if (strpos($category['icon'], '/admin/img/') === 0) {
                            // 去掉开头的斜杠
                            $category['icon'] = substr($category['icon'], 1);
                        }
                    }
                }
            }
            
            // 保存到缓存
            saveCategoriesCache($categories);
            
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
            return;
        }
    } catch (Exception $e) {
        // 记录错误但继续使用缓存数据
        error_log('分类数据库查询失败，尝试使用缓存数据: ' . $e->getMessage());
    }
    
    // 尝试从缓存获取数据
    $cachedCategories = getCategoriesCache();
    if ($cachedCategories !== false) {
        // 如果不需要计数，移除item_count字段
        if (!$withCount) {
            foreach ($cachedCategories as &$category) {
                if (isset($category['item_count'])) {
                    unset($category['item_count']);
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $cachedCategories,
            'from_cache' => true
        ]);
        return;
    }
    
    // 如果没有缓存或缓存失效，使用静态数据作为最后的备份
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
}