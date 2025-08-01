<?php
/**
 * 分类管理页面
 */

// 注意：这个文件被admin/index.php包含，所以路径相对于admin目录
// 不需要重复包含，因为index.php已经包含了这些文件

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
        
        // 确保上传目录存在且可写
        if (!is_dir('img')) {
            if (!mkdir('img', 0755, true)) {
                echo json_encode(['success' => false, 'message' => '无法创建上传目录']);
                ob_end_flush();
                exit;
            }
        }
        
        // 检查目录权限
        if (!is_writable('img')) {
            echo json_encode(['success' => false, 'message' => '上传目录不可写', 'dir' => realpath('img')]);
            ob_end_flush();
            exit;
        }
        
        // 移动文件
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // 返回完整的URL路径（用于前端显示）
            $iconUrl = '/admin/' . $uploadPath; // 使用绝对路径，从网站根目录开始
            echo json_encode(['success' => true, 'path' => $iconUrl, 'fileName' => $fileName]);
        } else {
            echo json_encode(['success' => false, 'message' => '文件保存失败，请检查目录权限', 'path' => $uploadPath]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '上传过程中发生错误: ' . $e->getMessage()]);
    }
    
    // 确保输出并退出
    ob_end_flush();
    exit;
}

// 初始化变量
$categories = [];
$editCategory = null;

// 处理删除操作
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    // 检查是否有关联的导航项目
    $itemCount = fetchOne("SELECT COUNT(*) as count FROM nav_items WHERE category_id = ?", [$id])['count'];
    
    if ($itemCount > 0) {
        $error = '无法删除：该分类下还有 ' . $itemCount . ' 个导航项目';
    } else {
        if (delete('nav_categories', 'id = ?', [$id])) {
            $success = '分类删除成功';
            // 记录操作日志
            if (function_exists('logAction')) {
                logAction('delete', '删除分类: ID ' . $id);
            }
        } else {
            $error = '删除失败';
        }
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'icon' => trim($_POST['icon'] ?? ''),
        'color' => trim($_POST['color'] ?? '#00f5ff'),
        'description' => trim($_POST['description'] ?? ''),
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (empty($data['name'])) {
        $error = '分类名称不能为空';
    } else {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // 更新
                update('nav_categories', $data, 'id = ?', [$id]);
                $message = '分类更新成功';
            } else {
                // 插入
                insert('nav_categories', $data);
                $message = '分类添加成功';
            }
            
            // 不重定向，直接显示成功消息
            $success = $message;
            
            // 添加成功后重置表单状态
            if ($id == 0) {
                // 新添加的情况，清空编辑状态
                $editCategory = null;
            }
        } catch (Exception $e) {
            $error = '保存失败: ' . $e->getMessage();
        }
    }
}

// 获取编辑的分类
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editCategory = fetchOne("SELECT * FROM nav_categories WHERE id = ?", [(int)$_GET['id']]);
}

// 获取所有分类（在所有处理完成后）
$categories = fetchAll("
    SELECT c.*, COUNT(n.id) as item_count 
    FROM nav_categories c 
    LEFT JOIN nav_items n ON c.id = n.category_id AND n.is_active = 1 
    GROUP BY c.id 
    ORDER BY c.sort_order ASC, c.id ASC
");
?>

<div class="page-content" style="padding-top: 0; margin-top: 0;">
    <!-- 快速操作按钮 -->
    <div class="quick-actions" style="margin-bottom: 20px; text-align: right;">
        <button class="btn btn-primary" onclick="showAddForm()">
            <span class="btn-icon">➕</span>
            添加分类
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
    
    <!-- 添加/编辑表单 -->
    <div class="category-form-card" id="categoryForm" style="<?= $editCategory ? '' : 'display: none;' ?>">
        <div class="form-background">
            <div class="form-particles"></div>
            <div class="form-gradient"></div>
        </div>
        <div class="card-header">
            <div class="header-icon">📁</div>
            <h3><?= $editCategory ? '✏️ 编辑分类' : '➕ 添加分类' ?></h3>
            <p>创建和管理导航分类</p>
            <button class="close-btn" onclick="hideForm()">×</button>
        </div>
        <div class="card-content">
            <form method="post" class="category-form">
                <?php if ($editCategory): ?>
                    <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">分类名称 *</label>
                        <input type="text" id="name" name="name" 
                               value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>" 
                               placeholder="如：开发工具、设计资源" required>
                    </div>
                    <div class="form-group">
                        <label for="icon">图标</label>
                        <div class="icon-selector">
                            <div class="icon-preview" id="iconPreview">
                                <span class="current-icon"><?= htmlspecialchars($editCategory['icon'] ?? '📁') ?></span>
                            </div>
                            <input type="text" id="icon" name="icon" 
                                   value="<?= htmlspecialchars($editCategory['icon'] ?? '') ?>" 
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
                                <button type="button" class="category-btn active" data-category="folders">文件夹</button>
                                <button type="button" class="category-btn" data-category="tools">工具</button>
                                <button type="button" class="category-btn" data-category="design">设计</button>
                                <button type="button" class="category-btn" data-category="other">其他</button>
                            </div>
                            <div class="icon-grid" id="iconGrid">
                                <!-- 图标将通过JavaScript动态加载 -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">分类描述</label>
                    <textarea id="description" name="description" rows="3" 
                              placeholder="简单描述这个分类的用途"><?= htmlspecialchars($editCategory['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="color">主题色</label>
                        <input type="color" id="color" name="color" 
                               value="<?= htmlspecialchars($editCategory['color'] ?? '#00f5ff') ?>">
                    </div>
                    <div class="form-group">
                        <label for="sort_order">排序</label>
                        <input type="number" id="sort_order" name="sort_order" 
                               value="<?= $editCategory['sort_order'] ?? 0 ?>" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?= ($editCategory['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="checkbox-text">启用此分类</span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon"><?= $editCategory ? '💾' : '➕' ?></span>
                        <?= $editCategory ? '更新分类' : '添加分类' ?>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="hideForm()">
                        <span class="btn-icon">❌</span>
                        取消
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 分类列表 -->
    <div class="category-list-card">
        <div class="card-header">
            <div class="header-icon">📋</div>
            <h3>分类列表</h3>
            <p>管理您的所有导航分类</p>
        </div>
        <div class="card-content">
            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📁</div>
                    <h3>暂无分类</h3>
                    <p>点击上方"添加分类"按钮来创建您的第一个分类</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>分类</th>
                                <th>描述</th>
                                <th>项目数</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="category-info">
                                            <span class="category-icon" style="color: <?= htmlspecialchars($category['color']) ?>">
                                                <?php if (strpos($category['icon'], '/img/') !== false || strpos($category['icon'], 'admin/img/') !== false): ?>
                                                    <img src="<?= htmlspecialchars($category['icon']) ?>" alt="图标" style="width: 24px; height: 24px; object-fit: contain;">
                                                <?php else: ?>
                                                    <?= htmlspecialchars($category['icon']) ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="category-name"><?= htmlspecialchars($category['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="category-description">
                                        <?= htmlspecialchars($category['description']) ?>
                                    </td>
                                    <td>
                                        <span class="item-count"><?= $category['item_count'] ?></span>
                                    </td>
                                    <td><?= $category['sort_order'] ?></td>
                                    <td>
                                        <span class="status-badge <?= $category['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                            <?= $category['is_active'] ? '启用' : '禁用' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?page=categories&action=edit&id=<?= $category['id'] ?>" class="btn btn-sm btn-secondary">编辑</a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', <?= $category['item_count'] ?>)">删除</button>
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
    document.getElementById('categoryForm').style.display = 'block';
    document.getElementById('name').focus();
}

function hideForm() {
    const form = document.getElementById('categoryForm');
    form.style.opacity = '0';
    form.style.transform = 'translateY(-20px)';
    setTimeout(() => {
        form.style.display = 'none';
        form.style.opacity = '1';
        form.style.transform = 'translateY(0)';
    }, 300);
    
    // 清空表单
    const formElement = document.querySelector('#categoryForm form');
    if (!formElement.querySelector('input[name="id"]')) {
        formElement.reset();
        document.getElementById('iconPreview').querySelector('.current-icon').textContent = '📁';
    }
}

// 图标选择器功能
const iconCategories = {
    folders: ['📁', '📂', '🗂️', '🗃️', '📋', '📊', '📈', '📉', '📄', '📃', '📑', '🗒️'],
    tools: ['🛠️', '🔧', '⚙️', '🔨', '⚡', '🔌', '💻', '⌨️', '🖥️', '🖨️', '📱', '⚡'],
    design: ['🎨', '🖌️', '✏️', '📐', '📏', '🎭', '🎪', '🎨', '🖼️', '🌈', '💎', '✨'],
    other: ['⭐', '🔥', '💡', '🎯', '🚀', '💫', '🌟', '⚡', '🔮', '💝', '🎁', '🏆']
};

function showIconPicker() {
    const picker = document.getElementById('iconPicker');
    const isVisible = picker.style.display !== 'none';
    
    if (isVisible) {
        picker.style.display = 'none';
    } else {
        picker.style.display = 'block';
        loadIcons('folders');
        
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
    const icons = iconCategories[category] || iconCategories.folders;
    
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
    const uploadUrl = '?page=categories'; // 提交到categories页面处理器
    
    console.log('上传URL:', uploadUrl);
    
    // 添加时间戳防止缓存
    const cacheBuster = new Date().getTime();
    const finalUrl = uploadUrl + (uploadUrl.includes('?') ? '&' : '?') + '_=' + cacheBuster;
    
    fetch(finalUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        cache: 'no-cache'
    })
    .then(response => {
        console.log('响应状态:', response.status);
        console.log('响应头:', response.headers);
        
        // 检查Content-Type
        const contentType = response.headers.get('content-type');
        console.log('Content-Type:', contentType);
        
        if (!response.ok) {
            throw new Error('网络响应错误: ' + response.status);
        }
        return response.text();
    })
    .then(text => {
        console.log('服务器响应:', text);
        try {
            // 尝试解析JSON
            const data = JSON.parse(text);
            hideUploadProgress();
            if (data.success) {
                // 上传成功，更新图标显示
                const iconPath = data.path;
                document.getElementById('icon').value = iconPath;
                
                // 更新预览显示
                const previewIcon = document.getElementById('iconPreview').querySelector('.current-icon');
                previewIcon.innerHTML = `<img src="${iconPath}" alt="图标" style="width: 24px; height: 24px; object-fit: contain;">`;
                
                alert('图标上传成功！');
            } else {
                alert('上传失败: ' + (data.message || '未知错误'));
            }
        } catch (e) {
            // JSON解析错误，可能是PHP错误或警告
            console.error('JSON解析错误:', e);
            console.error('原始响应:', text);
            
            // 检查是否包含PHP错误信息
            if (text.includes('Warning:') || text.includes('Notice:') || text.includes('Fatal error:')) {
                alert('服务器返回了PHP错误，请检查服务器日志。\n\n错误信息: ' + text.substring(0, 200) + '...');
            } else {
                alert('服务器响应格式错误，无法解析为JSON。请检查服务器配置和PHP错误日志。');
            }
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

// 删除分类
function deleteCategory(id, name, itemCount) {
    let message = `确定要删除分类"${name}"吗？`;
    if (itemCount > 0) {
        message += `\n\n⚠️ 注意：该分类下还有 ${itemCount} 个导航项目！\n删除分类后，这些项目将无法正常显示。`;
    }
    message += '\n\n此操作不可撤销。';
    
    if (confirm(message)) {
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

// 添加分类页面样式
const style = document.createElement('style');
style.textContent = `
/* 分类管理页面美化样式 */
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

.category-form-card {
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
        radial-gradient(2px 2px at 20px 30px, rgba(79,172,254,0.3), transparent),
        radial-gradient(2px 2px at 40px 70px, rgba(0,242,254,0.2), transparent),
        radial-gradient(1px 1px at 90px 40px, rgba(79,172,254,0.4), transparent);
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
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.category-form-card .card-header {
    position: relative;
    z-index: 2;
    color: white;
    padding: 30px;
    text-align: center;
}

.category-list-card .card-header {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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

.category-form {
    padding: 40px;
}

.category-form .form-group {
    margin-bottom: 25px;
    position: relative;
}

.category-form .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
    position: relative;
    padding-left: 20px;
}

.category-form .form-group label::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 16px;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border-radius: 2px;
}

.category-form input,
.category-form textarea,
.category-form select {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.category-form input:focus,
.category-form textarea:focus,
.category-form select:focus {
    outline: none;
    border-color: #4facfe;
    background: white;
    box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
    transform: translateY(-2px);
}

.category-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.category-form textarea {
    resize: vertical;
    min-height: 100px;
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
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
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

/* 分类列表美化 */
.category-list-card {
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

.category-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.category-icon {
    font-size: 20px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(79, 172, 254, 0.1);
    border-radius: 10px;
    border: 2px solid currentColor;
}

.category-name {
    font-weight: 600;
    color: #333;
}

.category-description {
    color: #666;
    font-size: 14px;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.item-count {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
    .category-form {
        padding: 30px 20px;
    }
    
    .category-form .form-row {
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
}
`;
document.head.appendChild(style);

// 如果是添加成功，2秒后自动隐藏表单
<?php if (isset($success) && !$editCategory): ?>
setTimeout(() => {
    hideForm();
}, 2000);
<?php endif; ?>
</script>