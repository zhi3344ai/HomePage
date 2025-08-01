<?php
/**
 * 社交链接管理页面
 */

// 初始化变量
$socialLinks = [];
$editLink = null;

// 处理删除操作
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    if (delete('social_links', 'id = ?', [$id])) {
        $success = '社交链接删除成功';
        // 记录操作日志
        if (function_exists('logAction')) {
            logAction('delete', '删除社交链接: ID ' . $id);
        }
    } else {
        $error = '删除失败';
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
    $data = [
        'platform' => trim($_POST['platform'] ?? ''),
        'url' => trim($_POST['url'] ?? ''),
        'icon' => trim($_POST['icon'] ?? ''),
        'color' => trim($_POST['color'] ?? '#333333'),
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (empty($data['platform']) || empty($data['url'])) {
        $error = '平台名称和联系方式不能为空';
    } else {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // 更新
                update('social_links', $data, 'id = ?', [$id]);
                $message = '社交链接更新成功';
            } else {
                // 插入
                insert('social_links', $data);
                $message = '社交链接添加成功';
            }
            
            // 不重定向，直接显示成功消息
            $success = $message;
            
            // 添加成功后重置表单状态
            if ($id == 0) {
                // 新添加的情况，清空编辑状态
                $editLink = null;
            }
        } catch (Exception $e) {
            $error = '保存失败: ' . $e->getMessage();
        }
    }
}

// 获取编辑的链接
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editLink = fetchOne("SELECT * FROM social_links WHERE id = ?", [(int)$_GET['id']]);
}

// 获取所有社交链接（在所有处理完成后）
$socialLinks = fetchAll("SELECT * FROM social_links ORDER BY sort_order ASC, id ASC");
?>

<div class="page-content" style="padding-top: 0; margin-top: 0;">
    <!-- 快速操作按钮 -->
    <div class="quick-actions" style="margin-bottom: 20px; text-align: right;">
        <button class="btn btn-primary" onclick="showAddForm()">
            <span class="btn-icon">➕</span>
            添加联系方式
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
    <div class="social-form-card" id="linkForm" style="<?= $editLink ? '' : 'display: none;' ?>">
        <div class="form-background">
            <div class="form-particles"></div>
            <div class="form-gradient"></div>
        </div>
        <div class="card-header">
            <div class="header-icon">🔗</div>
            <h3><?= $editLink ? '✏️ 编辑联系方式' : '➕ 添加联系方式' ?></h3>
            <p>管理您的社交媒体、联系方式和其他链接</p>
            <button class="close-btn" onclick="hideForm()">×</button>
        </div>
        <div class="card-content">
            <form method="post" class="social-form">
                <?php if ($editLink): ?>
                    <input type="hidden" name="id" value="<?= $editLink['id'] ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="platform">平台名称 *</label>
                        <input type="text" id="platform" name="platform" 
                               value="<?= htmlspecialchars($editLink['platform'] ?? '') ?>" 
                               placeholder="如：GitHub, Twitter, 微信" required>
                    </div>
                    <div class="form-group">
                        <label for="icon">图标</label>
                        <div class="icon-selector">
                            <div class="icon-preview" id="iconPreview">
                                <span class="current-icon"><?= htmlspecialchars($editLink['icon'] ?? '🔗') ?></span>
                            </div>
                            <input type="text" id="icon" name="icon" 
                                   value="<?= htmlspecialchars($editLink['icon'] ?? '') ?>" 
                                   placeholder="选择或输入图标">
                            <button type="button" class="icon-select-btn" onclick="showIconPicker()">选择图标</button>
                        </div>
                        
                        <!-- 图标选择器 -->
                        <div class="icon-picker" id="iconPicker" style="display: none;">
                            <div class="picker-header">
                                <h4>选择图标</h4>
                                <p>点击下方图标进行选择，或在输入框中直接输入emoji</p>
                            </div>
                            <div class="icon-categories">
                                <button type="button" class="category-btn active" data-category="social">社交</button>
                                <button type="button" class="category-btn" data-category="contact">联系</button>
                                <button type="button" class="category-btn" data-category="tech">技术</button>
                                <button type="button" class="category-btn" data-category="other">其他</button>
                            </div>
                            <div class="icon-grid" id="iconGrid">
                                <!-- 图标将通过JavaScript动态加载 -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="url">联系方式 *</label>
                    <input type="text" id="url" name="url" 
                           value="<?= htmlspecialchars($editLink['url'] ?? '') ?>" 
                           placeholder="如：https://github.com/username、13800138000、user@email.com、微信号等" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="color">主题色</label>
                        <input type="color" id="color" name="color" 
                               value="<?= htmlspecialchars($editLink['color'] ?? '#333333') ?>">
                    </div>
                    <div class="form-group">
                        <label for="sort_order">排序</label>
                        <input type="number" id="sort_order" name="sort_order" 
                               value="<?= $editLink['sort_order'] ?? 0 ?>" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?= ($editLink['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="checkbox-text">启用此链接</span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon"><?= $editLink ? '💾' : '➕' ?></span>
                        <?= $editLink ? '更新联系方式' : '添加联系方式' ?>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="hideForm()">
                        <span class="btn-icon">❌</span>
                        取消
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 链接列表 -->
    <div class="social-list-card">
        <div class="card-header">
            <div class="header-icon">📋</div>
            <h3>社交链接列表</h3>
            <p>管理您的所有社交媒体链接</p>
        </div>
        <div class="card-content">
            <?php if (empty($socialLinks)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔗</div>
                    <h3>暂无社交链接</h3>
                    <p>点击上方"添加联系方式"按钮来添加您的第一个联系方式</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>平台</th>
                                <th>联系方式</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($socialLinks as $link): ?>
                                <tr>
                                    <td>
                                        <div class="platform-info">
                                            <span class="platform-icon"><?= htmlspecialchars($link['icon']) ?></span>
                                            <span class="platform-name"><?= htmlspecialchars($link['platform']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $url = $link['url'];
                                        $displayUrl = $url;
                                        $linkHref = $url;
                                        
                                        // 根据内容类型设置不同的链接格式
                                        if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
                                            $linkHref = 'mailto:' . $url;
                                        } elseif (preg_match('/^[\d\s\-\+\(\)]+$/', $url)) {
                                            $linkHref = 'tel:' . preg_replace('/[^\d\+]/', '', $url);
                                        } elseif (!preg_match('/^https?:\/\//', $url) && !preg_match('/^mailto:/', $url) && !preg_match('/^tel:/', $url)) {
                                            // 如果不是完整URL且不是邮箱或电话，可能是用户名或ID，不添加链接
                                            $linkHref = '#';
                                        }
                                        ?>
                                        
                                        <?php if ($linkHref !== '#'): ?>
                                            <a href="<?= htmlspecialchars($linkHref) ?>" 
                                               target="<?= strpos($linkHref, 'http') === 0 ? '_blank' : '_self' ?>" 
                                               class="link-url">
                                                <?= htmlspecialchars($displayUrl) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="link-text">
                                                <?= htmlspecialchars($displayUrl) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $link['sort_order'] ?></td>
                                    <td>
                                        <span class="status-badge <?= $link['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                            <?= $link['is_active'] ? '启用' : '禁用' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?page=social&action=edit&id=<?= $link['id'] ?>" class="btn btn-sm btn-secondary">编辑</a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteSocialLink(<?= $link['id'] ?>, '<?= htmlspecialchars($link['platform']) ?>')">删除</button>
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
    document.getElementById('linkForm').style.display = 'block';
    document.getElementById('platform').focus();
}

function hideForm() {
    const form = document.getElementById('linkForm');
    form.style.opacity = '0';
    form.style.transform = 'translateY(-20px)';
    setTimeout(() => {
        form.style.display = 'none';
        form.style.opacity = '1';
        form.style.transform = 'translateY(0)';
    }, 300);
    
    // 清空表单
    const formElement = document.querySelector('#linkForm form');
    if (!formElement.querySelector('input[name="id"]')) {
        formElement.reset();
        document.getElementById('iconPreview').querySelector('.current-icon').textContent = '🔗';
    }
}

// 图标选择器功能
const iconCategories = {
    social: ['🐙', '🐦', '📘', '📷', '🎵', '📺', '💬', '📱', '🌐', '📧', '💼', '🎮'],
    contact: ['📞', '📧', '💬', '📱', '📠', '📮', '📬', '📭', '📫', '📪', '📨', '📩'],
    tech: ['💻', '⌨️', '🖥️', '🖨️', '📱', '⚡', '🔧', '⚙️', '🛠️', '🔌', '💾', '💿'],
    other: ['🏠', '🏢', '🎯', '⭐', '❤️', '👍', '🔥', '💡', '🎨', '📝', '📊', '📈']
};

function showIconPicker() {
    const picker = document.getElementById('iconPicker');
    const isVisible = picker.style.display !== 'none';
    
    if (isVisible) {
        picker.style.display = 'none';
    } else {
        picker.style.display = 'block';
        loadIcons('social');
        
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

// 点击预览区域也能打开图标选择器
document.addEventListener('DOMContentLoaded', function() {
    const iconPreview = document.getElementById('iconPreview');
    if (iconPreview) {
        iconPreview.addEventListener('click', showIconPicker);
    }
    
    // 为分类按钮添加点击事件
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const category = this.dataset.category;
            loadIcons(category);
        });
    });
});

function loadIcons(category) {
    const grid = document.getElementById('iconGrid');
    const icons = iconCategories[category] || iconCategories.social;
    
    if (!grid || !icons) {
        return;
    }
    
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
    document.getElementById('iconPreview').querySelector('.current-icon').textContent = icon;
    document.getElementById('iconPicker').style.display = 'none';
}

// 分类按钮事件已移至DOMContentLoaded中

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

// 删除社交链接
function deleteSocialLink(id, platform) {
    if (confirm(`确定要删除社交链接"${platform}"吗？此操作不可撤销。`)) {
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

// 添加页面样式
const style = document.createElement('style');
style.textContent = `
/* 社交链接页面美化样式 */
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

.social-form-card {
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
        radial-gradient(2px 2px at 20px 30px, rgba(0,245,255,0.3), transparent),
        radial-gradient(2px 2px at 40px 70px, rgba(255,0,128,0.2), transparent),
        radial-gradient(1px 1px at 90px 40px, rgba(0,245,255,0.4), transparent);
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
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
}

.social-form-card .card-header {
    position: relative;
    z-index: 2;
    color: white;
    padding: 30px;
    text-align: center;
}

.social-list-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.social-form {
    padding: 40px;
}

.social-form .form-group {
    margin-bottom: 25px;
    position: relative;
}

.social-form .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
    position: relative;
    padding-left: 20px;
}

.social-form .form-group label::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 16px;
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    border-radius: 2px;
}

.social-form input,
.social-form textarea,
.social-form select {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.social-form input:focus,
.social-form textarea:focus,
.social-form select:focus {
    outline: none;
    border-color: #00f5ff;
    background: white;
    box-shadow: 0 0 0 3px rgba(0, 245, 255, 0.1);
    transform: translateY(-2px);
}

.social-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
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
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(0, 245, 255, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 245, 255, 0.4);
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

/* 链接列表美化 */
.social-list-card {
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

.platform-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.platform-icon {
    font-size: 20px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #00f5ff20 0%, #ff008020 100%);
    border-radius: 10px;
}

.platform-name {
    font-weight: 600;
    color: #333;
}

.link-url {
    color: #00f5ff;
    text-decoration: none;
    font-family: monospace;
    background: #f8f9fa;
    padding: 5px 10px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.link-url:hover {
    background: #00f5ff;
    color: white;
    transform: translateX(5px);
}

.link-text {
    color: #495057;
    font-family: monospace;
    background: #f8f9fa;
    padding: 5px 10px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    display: inline-block;
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
    gap: 15px;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 12px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.icon-selector:hover {
    border-color: #00f5ff;
    background: white;
}

.icon-preview {
    width: 60px;
    height: 60px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    flex-shrink: 0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.icon-preview:hover {
    border-color: #00f5ff;
    transform: scale(1.05);
}

.current-icon {
    font-size: 28px;
    transition: transform 0.3s ease;
}

.icon-preview:hover .current-icon {
    transform: scale(1.1);
}

.icon-selector input {
    flex: 1;
    margin: 0;
    border: 1px solid #ddd;
    background: white;
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
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    margin-top: 10px;
    padding: 25px;
    animation: slideDown 0.3s ease;
    max-height: 400px;
    overflow: hidden;
}

.icon-picker::before {
    content: '';
    position: absolute;
    top: -8px;
    left: 30px;
    width: 16px;
    height: 16px;
    background: white;
    border: 2px solid #e9ecef;
    border-bottom: none;
    border-right: none;
    transform: rotate(45deg);
}

.picker-header {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.picker-header h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 18px;
    font-weight: 700;
}

.picker-header p {
    margin: 0;
    color: #666;
    font-size: 14px;
    opacity: 0.8;
}

.icon-categories {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 20px;
}

.category-btn {
    padding: 10px 18px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.category-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    transition: left 0.3s ease;
    z-index: -1;
}

.category-btn.active::before,
.category-btn:hover::before {
    left: 0;
}

.category-btn.active,
.category-btn:hover {
    color: white;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 245, 255, 0.3);
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(55px, 1fr));
    gap: 12px;
    max-height: 250px;
    overflow-y: auto;
    padding: 10px 0;
}

.icon-grid::-webkit-scrollbar {
    width: 6px;
}

.icon-grid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.icon-grid::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    border-radius: 3px;
}

.icon-option {
    width: 55px;
    height: 55px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background: #f8f9fa;
    cursor: pointer;
    font-size: 24px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.icon-option:hover {
    border-color: #00f5ff;
    background: rgba(0, 245, 255, 0.1);
    transform: scale(1.15);
    box-shadow: 0 5px 15px rgba(0, 245, 255, 0.3);
}

.icon-option:active {
    transform: scale(1.05);
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
    .social-form {
        padding: 30px 20px;
    }
    
    .social-form .form-row {
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
<?php if (isset($success) && !$editLink): ?>
setTimeout(() => {
    hideForm();
}, 2000);
<?php endif; ?>
</script>