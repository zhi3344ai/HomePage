<?php
/**
 * 导航项目管理页面
 */

// 注意：这个文件被admin/index.php包含，所以路径相对于admin目录
// 不需要重复包含，因为index.php已经包含了这些文件

// 初始化变量，避免未定义错误
$categories = [];
$navItems = [];
$editItem = null;
$error = null;
$success = null;

// 处理图标上传 - 必须在任何输出之前处理
if (isset($_POST['action']) && $_POST['action'] === 'upload_icon' && isset($_FILES['icon_file'])) {
    // 关闭所有输出缓冲区
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // 设置错误处理，防止PHP警告和通知干扰JSON输出
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // 确保没有之前的输出
    ob_start();
    
    header('Content-Type: application/json');
    
    try {
        $file = $_FILES['icon_file'];
        
        // 详细的错误检查
        if (!isset($file) || !is_array($file)) {
            echo json_encode(['success' => false, 'message' => '没有接收到文件']);
            exit;
        }
        
        // 验证文件上传错误
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                echo json_encode(['success' => false, 'message' => '文件太大']);
                exit;
            case UPLOAD_ERR_PARTIAL:
                echo json_encode(['success' => false, 'message' => '文件只有部分被上传']);
                exit;
            case UPLOAD_ERR_NO_FILE:
                echo json_encode(['success' => false, 'message' => '没有文件被上传']);
                exit;
            case UPLOAD_ERR_NO_TMP_DIR:
                echo json_encode(['success' => false, 'message' => '找不到临时文件夹']);
                exit;
            case UPLOAD_ERR_CANT_WRITE:
                echo json_encode(['success' => false, 'message' => '文件写入失败']);
                exit;
            default:
                echo json_encode(['success' => false, 'message' => '未知上传错误: ' . $file['error']]);
                exit;
        }
        
        // 验证文件是否存在
        if (!file_exists($file['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => '临时文件不存在']);
            exit;
        }
        
        // 验证文件类型
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        
        // 检查fileinfo扩展
        if (!extension_loaded('fileinfo')) {
            // 如果没有fileinfo扩展，使用文件扩展名检查
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            if (!in_array($extension, $allowedExtensions)) {
                echo json_encode(['success' => false, 'message' => '不支持的文件格式']);
                exit;
            }
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => '不支持的文件格式: ' . $mimeType]);
                exit;
            }
        }
        
        // 验证文件大小 (2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => '文件大小不能超过2MB']);
            exit;
        }
        
        // 生成文件名
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = 'icon_' . time() . '_' . uniqid() . '.' . $extension;
        $uploadPath = 'img/' . $fileName; // 相对于admin目录的路径
        
        // 确保目录存在
        if (!is_dir('img')) {
            if (!mkdir('img', 0755, true)) {
                echo json_encode(['success' => false, 'message' => '无法创建上传目录']);
                exit;
            }
        }
        
        // 检查目录权限
        if (!is_writable('img')) {
            echo json_encode(['success' => false, 'message' => '上传目录不可写']);
            exit;
        }
        
        // 移动文件
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // 返回绝对路径（用于前端显示）
            echo json_encode(['success' => true, 'path' => '/admin/img/' . $fileName, 'fileName' => $fileName]);
        } else {
            echo json_encode(['success' => false, 'message' => '文件保存失败，请检查目录权限']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '上传过程中发生错误: ' . $e->getMessage()]);
    }
    exit;
}

// 这些变量已经在上面初始化了

// 处理删除操作
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    try {
        if (function_exists('delete') && delete('nav_items', 'id = ?', [$id])) {
            $success = '导航项目删除成功';
            // 记录操作日志
            if (function_exists('logAction')) {
                logAction('delete', '删除导航项目: ID ' . $id);
            }
        } else {
            $error = '删除失败';
        }
    } catch (Exception $e) {
        $error = '删除失败: ' . $e->getMessage();
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
    $data = [
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'name' => trim($_POST['name'] ?? ''),
        'url' => trim($_POST['url'] ?? ''),
        'icon' => trim($_POST['icon'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        // 标签功能已从数据库中移除，不再包含tags字段
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'target' => $_POST['target'] ?? '_blank'
    ];
    
    if (empty($data['name']) || empty($data['url']) || $data['category_id'] <= 0) {
        $error = '项目名称、链接地址和分类不能为空';
    } else {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // 更新
                update('nav_items', $data, 'id = ?', [$id]);
                $message = '导航项目更新成功';
            } else {
                // 插入
                insert('nav_items', $data);
                $message = '导航项目添加成功';
            }
            
            // 不重定向，直接显示成功消息
            $success = $message;
            
            // 添加成功后重置表单状态
            if ($id == 0) {
                // 新添加的情况，清空编辑状态
                $editItem = null;
            }
        } catch (Exception $e) {
            $error = '保存失败: ' . $e->getMessage();
        }
    }
}

// 获取编辑的项目
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    try {
        if (function_exists('fetchOne')) {
            $editItem = fetchOne("SELECT * FROM nav_items WHERE id = ?", [(int)$_GET['id']]);
            if ($editItem && $editItem['tags']) {
                $editItem['tags'] = implode(', ', json_decode($editItem['tags'], true) ?: []);
            }
        }
    } catch (Exception $e) {
        $error = '获取编辑项目失败: ' . $e->getMessage();
    }
}

// 获取分类列表用于下拉选择
try {
    if (function_exists('fetchAll')) {
        $categories = fetchAll("SELECT * FROM nav_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        // 如果查询结果为空，使用默认分类
        if (empty($categories)) {
            $categories = [
                ['id' => 1, 'name' => '默认分类', 'color' => '#007cba']
            ];
        }
    }
} catch (Exception $e) {
    $categories = [
        ['id' => 1, 'name' => '默认分类', 'color' => '#007cba']
    ];
}

// 获取所有导航项目（在所有处理完成后）
try {
    if (function_exists('fetchAll')) {
        $navItems = fetchAll("
            SELECT n.*, c.name as category_name, c.color as category_color 
            FROM nav_items n 
            LEFT JOIN nav_categories c ON n.category_id = c.id 
            ORDER BY n.sort_order ASC, n.id ASC
        ");
    }
} catch (Exception $e) {
    $navItems = [];
}
?>

<div class="page-content" style="padding-top: 0; margin-top: 0;">
    <?php 
    // 检查数据库连接状态
    $dbConnected = getDatabase() !== false;
    if (!$dbConnected): 
    ?>
    <div class="alert alert-warning" style="margin-bottom: 20px;">
        <span class="alert-icon">⚠️</span>
        <span class="alert-message">
            数据库连接失败，部分功能可能不可用。图标上传功能仍然可以正常使用。
            <br>请检查数据库配置或运行安装程序。
        </span>
    </div>
    <?php endif; ?>
    
    <!-- 快速操作按钮 -->
    <div class="quick-actions" style="margin-bottom: 20px; text-align: right;">
        <button class="btn btn-primary" onclick="showAddForm()">
            <span class="btn-icon">➕</span>
            添加项目
        </button>
    </div>
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <span class="alert-icon">❌</span>
            <span class="alert-message"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <span class="alert-message"><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (empty($categories)): ?>
        <div class="alert alert-warning">
            <span class="alert-icon">⚠️</span>
            <span class="alert-message">
                请先 <a href="?page=categories">创建分类</a> 后再添加导航项目
            </span>
        </div>
    <?php endif; ?>
    
    <!-- 添加/编辑表单 -->
    <div class="navigation-form-card" id="itemForm" style="<?= $editItem ? '' : 'display: none;' ?>">
        <div class="form-background">
            <div class="form-particles"></div>
            <div class="form-gradient"></div>
        </div>
        <div class="card-header">
            <div class="header-icon">🧭</div>
            <h3><?= $editItem ? '✏️ 编辑项目' : '➕ 添加项目' ?></h3>
            <p>创建和管理导航链接项目</p>
            <button class="close-btn" onclick="hideForm()">×</button>
        </div>
        <div class="card-content">
            <form method="post" class="navigation-form">
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">项目名称 *</label>
                        <input type="text" id="name" name="name" 
                               value="<?= htmlspecialchars($editItem['name'] ?? '') ?>" 
                               placeholder="如：GitHub, VS Code" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">所属分类 *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">请选择分类</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= ($editItem['category_id'] ?? 0) == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="url">链接地址 *</label>
                    <input type="url" id="url" name="url" 
                           value="<?= htmlspecialchars($editItem['url'] ?? '') ?>" 
                           placeholder="https://example.com" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="icon">图标</label>
                        <div class="icon-selector">
                            <div class="icon-preview" id="iconPreview">
                                <span class="current-icon"><?= htmlspecialchars($editItem['icon'] ?? '🔗') ?></span>
                            </div>
                            <input type="text" id="icon" name="icon" 
                                   value="<?= htmlspecialchars($editItem['icon'] ?? '') ?>" 
                                   placeholder="选择或输入图标">
                            <div class="icon-buttons">
                                <button type="button" class="icon-btn network-icon-btn" onclick="showIconPicker()" title="网络图标">
                                    🌐
                                </button>
                                <button type="button" class="icon-btn local-icon-btn" onclick="selectLocalIcon()" title="本地图标">
                                    📁
                                </button>
                            </div>
                            <input type="file" id="localIconInput" accept="image/*" style="display: none;" onchange="handleLocalIconUpload(event)">
                        </div>
                        
                        <!-- 图标选择器 -->
                        <div class="icon-picker" id="iconPicker" style="display: none;">
                            <div class="icon-categories">
                                <button type="button" class="category-btn active" data-category="websites">网络图标</button>
                                <button type="button" class="category-btn" data-category="tools">工具</button>
                                <button type="button" class="category-btn" data-category="social">社交</button>
                                <button type="button" class="category-btn" data-category="other">其他</button>
                            </div>
                            <div class="icon-grid" id="iconGrid">
                                <!-- 图标将通过JavaScript动态加载 -->
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="target">打开方式</label>
                        <select id="target" name="target">
                            <option value="_blank" <?= ($editItem['target'] ?? '_blank') === '_blank' ? 'selected' : '' ?>>新窗口</option>
                            <option value="_self" <?= ($editItem['target'] ?? '') === '_self' ? 'selected' : '' ?>>当前窗口</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">项目描述</label>
                    <textarea id="description" name="description" rows="3" 
                              placeholder="简单描述这个项目的用途"><?= htmlspecialchars($editItem['description'] ?? '') ?></textarea>
                </div>
                
                <!-- 标签功能已移除 -->
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sort_order">排序</label>
                        <input type="number" id="sort_order" name="sort_order" 
                               value="<?= $editItem['sort_order'] ?? 0 ?>" min="0">
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <span class="checkbox-text">启用项目</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_featured" <?= ($editItem['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                <span class="checkbox-text">推荐项目</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon"><?= $editItem ? '💾' : '➕' ?></span>
                        <?= $editItem ? '更新项目' : '添加项目' ?>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="hideForm()">
                        <span class="btn-icon">❌</span>
                        取消
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 项目列表 -->
    <div class="navigation-list-card">
        <div class="card-header">
            <div class="header-icon">📋</div>
            <h3>导航项目列表</h3>
            <p>管理您的所有导航链接</p>
        </div>
        <div class="card-content">
            <?php if (empty($navItems)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🧭</div>
                    <h3>暂无导航项目</h3>
                    <p>点击上方"添加项目"按钮来添加您的第一个导航项目</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>项目</th>
                                <th>分类</th>
                                <th>链接</th>
                                <th>点击量</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($navItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="item-info">
                                            <span class="item-icon">
                                                <?php if (strpos($item['icon'], '/admin/img/') === 0 || strpos($item['icon'], '../img/') === 0 || strpos($item['icon'], 'admin/img/') === 0): ?>
                                                    <img src="<?= htmlspecialchars($item['icon']) ?>" alt="图标" style="width: 24px; height: 24px; object-fit: contain;">
                                                <?php else: ?>
                                                    <?= htmlspecialchars($item['icon']) ?>
                                                <?php endif; ?>
                                            </span>
                                            <div class="item-details">
                                                <div class="item-name">
                                                    <?= htmlspecialchars($item['name']) ?>
                                                    <?php if ($item['is_featured']): ?>
                                                        <span class="featured-badge">推荐</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($item['description']): ?>
                                                    <div class="item-description"><?= htmlspecialchars($item['description']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($item['category_name']): ?>
                                            <span class="category-badge" style="background-color: <?= htmlspecialchars($item['category_color']) ?>20; color: <?= htmlspecialchars($item['category_color']) ?>">
                                                <?= htmlspecialchars($item['category_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">未分类</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>" class="link-url">
                                            <?= htmlspecialchars($item['url']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="click-count"><?= $item['click_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $item['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                            <?= $item['is_active'] ? '启用' : '禁用' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?page=navigation&action=edit&id=<?= $item['id'] ?>" class="btn btn-sm btn-secondary">编辑</a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteNavItem(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name']) ?>')">删除</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('itemForm').style.display = 'block';
    document.getElementById('name').focus();
}

function hideForm() {
    const form = document.getElementById('itemForm');
    form.style.opacity = '0';
    form.style.transform = 'translateY(-20px)';
    setTimeout(() => {
        form.style.display = 'none';
        form.style.opacity = '1';
        form.style.transform = 'translateY(0)';
    }, 300);
    
    // 清空表单
    const formElement = document.querySelector('#itemForm form');
    if (!formElement.querySelector('input[name="id"]')) {
        formElement.reset();
        document.getElementById('iconPreview').querySelector('.current-icon').textContent = '🔗';
    }
}

// 图标选择器功能
const iconCategories = {
    websites: ['🌐', '💻', '📱', '🖥️', '⚡', '🔗', '📄', '📊', '📈', '📉', '🗂️', '📋'],
    tools: ['🛠️', '🔧', '⚙️', '🔨', '⚡', '🔌', '💾', '💿', '🖨️', '⌨️', '🖱️', '📡'],
    social: ['🐙', '🐦', '📘', '📷', '🎵', '📺', '💬', '📱', '🌐', '📧', '💼', '🎮'],
    other: ['⭐', '🔥', '💡', '🎯', '🚀', '💫', '🌟', '⚡', '🔮', '💝', '🎁', '🏆']
};

function showIconPicker() {
    const picker = document.getElementById('iconPicker');
    const isVisible = picker.style.display !== 'none';
    
    if (isVisible) {
        picker.style.display = 'none';
    } else {
        picker.style.display = 'block';
        loadIcons('websites');
        
        // 重新绑定分类按钮事件（确保事件正常工作）
        setTimeout(() => {
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    loadIcons(this.dataset.category);
                };
            });
        }, 100);
    }
}

function loadIcons(category) {
    const grid = document.getElementById('iconGrid');
    const icons = iconCategories[category] || iconCategories.websites;
    
    grid.innerHTML = icons.map(icon => 
        `<button type="button" class="icon-option" onclick="selectIcon('${icon}')">${icon}</button>`
    ).join('');
    
    // 更新分类按钮状态
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.category === category);
    });
}

function selectIcon(icon) {
    document.getElementById('icon').value = icon;
    
    // 更新预览显示
    const previewIcon = document.getElementById('iconPreview').querySelector('.current-icon');
    if (icon.startsWith('/admin/img/') || icon.startsWith('../img/') || icon.startsWith('admin/img/')) {
        // 本地图片
        previewIcon.innerHTML = `<img src="${icon}" alt="图标" style="width: 24px; height: 24px; object-fit: contain;">`;
    } else {
        // emoji图标
        previewIcon.textContent = icon;
    }
    
    document.getElementById('iconPicker').style.display = 'none';
}

// 页面加载完成后绑定事件
document.addEventListener('DOMContentLoaded', function() {
    // 为分类按钮添加点击事件
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const category = this.dataset.category;
            loadIcons(category);
        });
    });
    
    // 初始化图标显示
    const iconInput = document.getElementById('icon');
    const previewIcon = document.getElementById('iconPreview').querySelector('.current-icon');
    if (iconInput && previewIcon && iconInput.value) {
        const iconValue = iconInput.value;
        if (iconValue.startsWith('/admin/img/') || iconValue.startsWith('../img/') || iconValue.startsWith('admin/img/')) {
            // 本地图片
            previewIcon.innerHTML = `<img src="${iconValue}" alt="图标" style="width: 24px; height: 24px; object-fit: contain;">`;
        } else {
            // emoji图标
            previewIcon.textContent = iconValue;
        }
    }
});

// 点击外部关闭图标选择器
document.addEventListener('click', function(e) {
    const picker = document.getElementById('iconPicker');
    const selector = document.querySelector('.icon-selector');
    
    // 如果选择器没有显示，不需要处理
    if (!picker || picker.style.display === 'none') {
        return;
    }
    
    // 如果点击的是选择器内部的元素，不关闭选择器
    if (e.target.closest('.icon-selector')) {
        return;
    }
    
    // 点击外部时关闭选择器
    picker.style.display = 'none';
});

// 选择本地图标
function selectLocalIcon() {
    document.getElementById('localIconInput').click();
}

// 处理本地图标上传
function handleLocalIconUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // 验证文件类型
    if (!file.type.startsWith('image/')) {
        alert('请选择图片文件');
        return;
    }
    
    // 验证文件大小 (最大2MB)
    if (file.size > 2 * 1024 * 1024) {
        alert('图片文件大小不能超过2MB');
        return;
    }
    
    // 显示上传进度
    showUploadProgress();
    
    // 创建FormData上传文件
    const formData = new FormData();
    formData.append('action', 'upload_icon');
    formData.append('icon_file', file);
    
    // 使用正确的上传URL
    const uploadUrl = '?page=navigation'; // 提交到navigation页面处理器
    
    console.log('上传URL:', uploadUrl);
    
    fetch(uploadUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('响应状态:', response.status);
        if (!response.ok) {
            throw new Error('网络响应错误: ' + response.status);
        }
        return response.text();
    })
    .then(text => {
        console.log('服务器响应:', text);
        try {
            const data = JSON.parse(text);
            hideUploadProgress();
            if (data.success) {
                // 上传成功，更新图标显示
                const iconPath = data.path;
                console.log('上传成功，图标路径:', iconPath);
                
                document.getElementById('icon').value = iconPath;
                
                // 更新预览显示
                const previewIcon = document.getElementById('iconPreview').querySelector('.current-icon');
                console.log('预览元素:', previewIcon);
                
                if (previewIcon) {
                    const imgHtml = `<img src="${iconPath}" alt="图标" style="width: 24px; height: 24px; object-fit: contain;" onload="console.log('图片加载成功')" onerror="console.log('图片加载失败')">`;
                    console.log('设置HTML:', imgHtml);
                    previewIcon.innerHTML = imgHtml;
                } else {
                    console.error('找不到预览元素');
                }
                
                alert('图标上传成功！路径: ' + iconPath);
            } else {
                alert('上传失败: ' + (data.message || '未知错误'));
            }
        } catch (e) {
            console.error('JSON解析错误:', e);
            console.error('原始响应:', text);
            alert('服务器响应格式错误');
        }
    })
    .catch(error => {
        hideUploadProgress();
        console.error('上传错误:', error);
        alert('上传失败: ' + error.message);
    });
}

// 显示上传进度
function showUploadProgress() {
    const btn = document.querySelector('.local-icon-btn');
    btn.innerHTML = '⏳';
    btn.disabled = true;
}

// 隐藏上传进度
function hideUploadProgress() {
    const btn = document.querySelector('.local-icon-btn');
    btn.innerHTML = '📁';
    btn.disabled = false;
}

// 删除导航项目
function deleteNavItem(id, name) {
    if (confirm(`确定要删除导航项目"${name}"吗？此操作不可撤销。`)) {
        // 创建隐藏表单提交删除请求
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// 添加导航项目页面样式
const style = document.createElement('style');
style.textContent = `
/* 导航项目页面美化样式 */
.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    animation: slideInDown 0.5s ease-out;
}

.alert-error {
    background: linear-gradient(135deg, #fee 0%, #fdd 100%);
    color: #c33;
    border: 2px solid #fcc;
    box-shadow: 0 4px 15px rgba(204, 51, 51, 0.2);
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: 2px solid #b8e6c1;
    box-shadow: 0 4px 15px rgba(21, 87, 36, 0.2);
}

.alert-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.alert-message {
    flex: 1;
    font-size: 16px;
}

@keyframes slideInDown {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.quick-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-bottom: 20px;
}

.quick-actions .btn {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.quick-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.navigation-form-card {
    position: relative;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 30px;
    transition: all 0.3s ease;
}

.form-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 120px;
    overflow: hidden;
}

.form-particles {
    position: absolute;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(2px 2px at 20px 30px, rgba(0,242,254,0.3), transparent),
        radial-gradient(2px 2px at 40px 70px, rgba(84,160,255,0.2), transparent),
        radial-gradient(1px 1px at 90px 40px, rgba(0,242,254,0.4), transparent);
    background-repeat: repeat;
    background-size: 200px 100px;
    animation: particleFloat 15s linear infinite;
}

.form-gradient {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
}

.navigation-form-card .card-header {
    position: relative;
    z-index: 2;
    color: white;
    padding: 30px;
    text-align: center;
}

.navigation-list-card .card-header {
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
    color: white;
    padding: 30px;
    text-align: center;
    position: relative;
}

.header-icon {
    font-size: 32px;
    margin-bottom: 10px;
    display: block;
}

.card-header h3 {
    font-size: 24px;
    margin: 0 0 8px 0;
    font-weight: 700;
}

.card-header p {
    opacity: 0.9;
    margin: 0;
    font-size: 14px;
}

.close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.navigation-form {
    padding: 40px;
}

.navigation-form .form-group {
    margin-bottom: 25px;
    position: relative;
}

.navigation-form .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
    position: relative;
    padding-left: 20px;
}

.navigation-form .form-group label::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 16px;
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
    border-radius: 2px;
}

.navigation-form input,
.navigation-form textarea,
.navigation-form select {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.navigation-form input:focus,
.navigation-form textarea:focus,
.navigation-form select:focus {
    outline: none;
    border-color: #00d2d3;
    background: white;
    box-shadow: 0 0 0 3px rgba(0, 210, 211, 0.1);
    transform: translateY(-2px);
}

.navigation-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.navigation-form textarea {
    resize: vertical;
    min-height: 100px;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #666;
}

.form-help {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
    margin-top: 30px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 15px 30px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(0, 210, 211, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 210, 211, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
}

.btn-icon {
    font-size: 18px;
}

/* 导航项目列表美化 */
.navigation-list-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.table-responsive {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.table th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 20px 15px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 20px 15px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.table tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
    transition: all 0.3s ease;
}

.item-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.item-icon {
    font-size: 20px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #00d2d320 0%, #54a0ff20 100%);
    border-radius: 10px;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.featured-badge {
    background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
}

.item-description {
    color: #666;
    font-size: 12px;
    margin-top: 2px;
}

.category-badge {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.link-url {
    color: #00d2d3;
    text-decoration: none;
    font-family: monospace;
    background: #f8f9fa;
    padding: 5px 10px;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-size: 12px;
}

.link-url:hover {
    background: #00d2d3;
    color: white;
    transform: translateX(5px);
}

.click-count {
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 14px;
    border-radius: 8px;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 10px;
    color: #495057;
}

/* 动画效果 */
@keyframes particleFloat {
    0% { transform: translateX(0px) translateY(0px); }
    33% { transform: translateX(30px) translateY(-30px); }
    66% { transform: translateX(-20px) translateY(20px); }
    100% { transform: translateX(0px) translateY(0px); }
}

/* 图标选择器样式 */
.icon-selector {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
}

.icon-preview {
    width: 50px;
    height: 50px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    flex-shrink: 0;
}

.current-icon {
    font-size: 24px;
}

.icon-selector input {
    flex: 1;
    margin: 0;
    border: 1px solid #ddd;
    background: white;
}

.icon-buttons {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.icon-btn {
    width: 45px;
    height: 45px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    background: #f8f9fa;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.network-icon-btn:hover {
    border-color: #00f5ff;
    background: rgba(0, 245, 255, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 245, 255, 0.3);
}

.local-icon-btn:hover {
    border-color: #ff6b35;
    background: rgba(255, 107, 53, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
}

.icon-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.icon-btn:disabled:hover {
    border-color: #e9ecef;
    background: #f8f9fa;
    box-shadow: none;
}

.icon-select-btn {
    padding: 15px 20px;
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.icon-select-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 245, 255, 0.3);
}

.icon-picker {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    margin-top: 10px;
    padding: 20px;
    animation: slideDown 0.3s ease;
}

.icon-categories {
    display: flex;
    gap: 5px;
    margin-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 15px;
}

.category-btn {
    padding: 8px 16px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

.category-btn.active,
.category-btn:hover {
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    color: white;
    border-color: transparent;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
    gap: 10px;
    max-height: 200px;
    overflow-y: auto;
}

.icon-option {
    width: 50px;
    height: 50px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    background: #f8f9fa;
    cursor: pointer;
    font-size: 20px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-option:hover {
    border-color: #00f5ff;
    background: rgba(0, 245, 255, 0.1);
    transform: scale(1.1);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 响应式设计 */
@media (max-width: 768px) {
    .navigation-form {
        padding: 30px 20px;
    }
    
    .navigation-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .item-info {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .icon-selector {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .icon-preview {
        align-self: center;
    }
    
    .icon-categories {
        flex-wrap: wrap;
    }
    
    .icon-grid {
        grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
    }
        gap: 8px;
    }
}
`;
document.head.appendChild(style);

// 如果是添加成功，2秒后自动隐藏表单
<?php if (isset($success) && !$editItem): ?>
setTimeout(() => {
    hideForm();
}, 2000);
<?php endif; ?>
</script>