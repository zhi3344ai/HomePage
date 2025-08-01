<?php
/**
 * 个人信息管理页面
 */

// 引入图片处理器（优先使用高级版本，回退到简化版本）
if (extension_loaded('gd')) {
    if (file_exists('admin/includes/image-processor.php')) {
        require_once 'admin/includes/image-processor.php';
        $useAdvancedProcessor = true;
    } else {
        $useAdvancedProcessor = false;
    }
} else {
    if (file_exists('admin/includes/simple-image-handler.php')) {
        require_once 'admin/includes/simple-image-handler.php';
        $useAdvancedProcessor = false;
    } else {
        $useAdvancedProcessor = false;
    }
}

/**
 * 处理头像上传
 */
function handleAvatarUpload($file) {
    global $useAdvancedProcessor;
    
    // 删除旧头像文件
    deleteOldAvatar();
    
    if ($useAdvancedProcessor) {
        // 使用高级图片处理器
        $result = processAvatarUpload($file);
        
        if ($result['success']) {
            // 生成缩略图
            $processor = new ImageProcessor();
            $thumbnailPath = str_replace('.jpg', '_thumb.jpg', $result['path']);
            $processor->generateThumbnail($result['path'], $thumbnailPath, 80, 80);
        }
    } else {
        // 使用简化版处理器
        $result = simpleAvatarUpload($file);
    }
    
    return $result;
}

// 图片压缩功能已移至 ImageProcessor 类

/**
 * 删除旧头像
 */
function deleteOldAvatar() {
    global $useAdvancedProcessor;
    
    if ($useAdvancedProcessor) {
        // 使用高级处理器的删除方法
        $oldProfile = fetchOne("SELECT avatar FROM profile WHERE id = 1");
        if ($oldProfile && $oldProfile['avatar'] && 
            $oldProfile['avatar'] !== 'assets/images/avatar.svg' && 
            strpos($oldProfile['avatar'], 'admin/img/') === 0 &&
            file_exists($oldProfile['avatar'])) {
            unlink($oldProfile['avatar']);
        }
    } else {
        // 使用简化版处理器的删除方法
        cleanupOldAvatar();
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'status_text' => trim($_POST['status_text'] ?? ''),
        'status_type' => $_POST['status_type'] ?? 'online',
        'projects_count' => (int)($_POST['projects_count'] ?? 0),
        'experience_years' => (int)($_POST['experience_years'] ?? 0),
        'skills_count' => (int)($_POST['skills_count'] ?? 0),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'website' => trim($_POST['website'] ?? '')
    ];
    
    // 处理头像上传
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleAvatarUpload($_FILES['avatar']);
        if ($uploadResult['success']) {
            $data['avatar'] = $uploadResult['path'];
        } else {
            $error = $uploadResult['message'];
        }
    }
    
    // 数据验证
    if (empty($data['name'])) {
        $error = '姓名不能为空';
    } elseif (strlen($data['name']) > 100) {
        $error = '姓名长度不能超过100个字符';
    } elseif (strlen($data['title']) > 200) {
        $error = '职业标题长度不能超过200个字符';
    } elseif (strlen($data['status_text']) > 50) {
        $error = '状态文本长度不能超过50个字符';
    } elseif (!in_array($data['status_type'], ['online', 'away', 'busy', 'offline'])) {
        $error = '状态类型无效';
    } elseif ($data['projects_count'] < 0 || $data['projects_count'] > 9999) {
        $error = '项目数量必须在0-9999之间';
    } elseif ($data['experience_years'] < 0 || $data['experience_years'] > 99) {
        $error = '经验年数必须在0-99之间';
    } elseif ($data['skills_count'] < 0 || $data['skills_count'] > 999) {
        $error = '技能数量必须在0-999之间';
    } elseif (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } elseif (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
        $error = '网站地址格式不正确';
    } else {
        try {
            // 先检查数据库中是否已有记录
            $existingProfile = fetchOne("SELECT * FROM profile WHERE id = 1");
            
            if ($existingProfile) {
                // 更新现有记录 - 使用简化的方法
                $db = getDatabase();
                
                // 只更新非空字段，避免数据类型问题
                $updateFields = [];
                $updateParams = [];
                
                foreach ($data as $key => $value) {
                    $updateFields[] = "`{$key}` = ?";
                    $updateParams[] = $value;
                }
                
                $sql = "UPDATE `profile` SET " . implode(', ', $updateFields) . " WHERE `id` = 1";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute($updateParams);
                $affectedRows = $stmt->rowCount();
                
                if ($result) {
                    $success = '个人信息更新成功！';
                    
                    // 记录操作日志
                    logAction('update', '更新个人信息');
                } else {
                    // 获取PDO错误信息
                    $errorInfo = $stmt->errorInfo();
                    $error = '更新失败: ' . ($errorInfo[2] ?? '未知错误');
                }
            } else {
                // 插入新记录
                $db = getDatabase();
                
                $columns = array_keys($data);
                $placeholders = array_fill(0, count($columns), '?');
                
                $sql = "INSERT INTO `profile` (`id`, `" . implode('`, `', $columns) . "`) VALUES (1, " . implode(', ', $placeholders) . ")";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute(array_values($data));
                
                if ($result) {
                    $success = '个人信息创建成功！';
                    
                    // 记录操作日志
                    logAction('create', '创建个人信息');
                } else {
                    // 获取PDO错误信息
                    $errorInfo = $stmt->errorInfo();
                    $error = '创建失败: ' . ($errorInfo[2] ?? '未知错误');
                }
            }
            
        } catch (Exception $e) {
            $error = '保存失败: ' . $e->getMessage();
        }
    }
}

// 获取个人信息（在处理表单后重新获取以确保数据是最新的）
$profile = fetchOne("SELECT * FROM profile WHERE id = 1");
if (!$profile) {
    // 如果没有记录，创建默认记录
    $defaultProfile = [
        'name' => 'HomePage',
        'title' => '欢迎使用',
        'description' => '这是一个个人导航页面',
        'avatar' => 'assets/images/avatar.svg',
        'status_text' => '在线',
        'status_type' => 'online',
        'projects_count' => 0,
        'experience_years' => 0,
        'skills_count' => 0,
        'email' => '',
        'phone' => '',
        'location' => '',
        'website' => ''
    ];
    
    // 尝试插入默认记录
    try {
        $db = getDatabase();
        $columns = array_keys($defaultProfile);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO `profile` (`id`, `" . implode('`, `', $columns) . "`) VALUES (1, " . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($defaultProfile));
        
        // 重新获取插入的记录
        $profile = fetchOne("SELECT * FROM profile WHERE id = 1");
    } catch (Exception $e) {
        // 如果插入失败，使用默认数组
        $profile = array_merge(['id' => 1], $defaultProfile);
    }
}
?>

<div class="page-content" style="padding-top: 0; margin-top: 0;">
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
    
    <!-- 个人信息预览卡片 -->
    <div class="profile-preview-card">
        <div class="preview-background">
            <div class="preview-particles"></div>
            <div class="preview-gradient"></div>
        </div>
        <div class="preview-content">
            <div class="preview-avatar">
                <img src="<?= htmlspecialchars($profile['avatar']) ?>" alt="头像" class="avatar-img">
                <div class="avatar-glow"></div>
                <div class="status-indicator status-<?= htmlspecialchars($profile['status_type']) ?>"></div>
            </div>
            <div class="preview-info">
                <h2 class="preview-name"><?= htmlspecialchars($profile['name']) ?></h2>
                <p class="preview-title"><?= htmlspecialchars($profile['title']) ?></p>
                <p class="preview-status"><?= htmlspecialchars($profile['status_text']) ?></p>
            </div>
            <div class="preview-stats">
                <div class="stat-item">
                    <div class="stat-number"><?= $profile['projects_count'] ?></div>
                    <div class="stat-label">项目</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $profile['experience_years'] ?></div>
                    <div class="stat-label">年经验</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $profile['skills_count'] ?></div>
                    <div class="stat-label">技能</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 编辑表单 -->
    <div class="profile-form-card">
        <div class="card-header">
            <h3>✏️ 编辑信息</h3>
            <p>更新您的个人资料</p>
        </div>
        <div class="card-content">
            <form method="post" class="profile-form" enctype="multipart/form-data">
                <!-- 头像上传区域 -->
                <div class="form-group">
                    <label>头像</label>
                    <?php 
                    if (file_exists('admin/includes/avatar-uploader.php')) {
                        require_once 'admin/includes/avatar-uploader.php';
                        renderAvatarUploader($profile['avatar'], 'avatar', [
                            'size' => 120,
                            'showInfo' => true,
                            'allowDrag' => true,
                            'previewUpdate' => true
                        ]);
                    } else {
                        // 如果组件文件不存在，显示简单的文件上传
                        echo '<input type="file" name="avatar" accept="image/*">';
                        echo '<p>支持 JPG、PNG、GIF、WebP 格式，文件大小不超过 5MB</p>';
                    }
                    ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">姓名 *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="title">职业标题</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($profile['title']) ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">个人简介</label>
                    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($profile['description']) ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status_text">状态文本</label>
                        <input type="text" id="status_text" name="status_text" value="<?= htmlspecialchars($profile['status_text']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="status_type">状态类型</label>
                        <select id="status_type" name="status_type">
                            <option value="online" <?= $profile['status_type'] === 'online' ? 'selected' : '' ?>>在线</option>
                            <option value="away" <?= $profile['status_type'] === 'away' ? 'selected' : '' ?>>离开</option>
                            <option value="busy" <?= $profile['status_type'] === 'busy' ? 'selected' : '' ?>>忙碌</option>
                            <option value="offline" <?= $profile['status_type'] === 'offline' ? 'selected' : '' ?>>离线</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="projects_count">项目数量</label>
                        <input type="number" id="projects_count" name="projects_count" value="<?= $profile['projects_count'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="experience_years">经验年数</label>
                        <input type="number" id="experience_years" name="experience_years" value="<?= $profile['experience_years'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="skills_count">技能数量</label>
                        <input type="number" id="skills_count" name="skills_count" value="<?= $profile['skills_count'] ?>" min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">邮箱</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">电话</label>
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($profile['phone']) ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="location">所在地</label>
                        <input type="text" id="location" name="location" value="<?= htmlspecialchars($profile['location']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="website">个人网站</label>
                        <input type="url" id="website" name="website" value="<?= htmlspecialchars($profile['website']) ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">💾</span>
                        保存更改
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <span class="btn-icon">🔄</span>
                        重置
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* 个人信息页面专用样式 */
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

.profile-preview-card {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 30px;
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
}

.preview-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
}

.preview-particles {
    position: absolute;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(2px 2px at 20px 30px, rgba(255,255,255,0.3), transparent),
        radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.2), transparent),
        radial-gradient(1px 1px at 90px 40px, rgba(255,255,255,0.4), transparent),
        radial-gradient(1px 1px at 130px 80px, rgba(255,255,255,0.3), transparent),
        radial-gradient(2px 2px at 160px 30px, rgba(255,255,255,0.2), transparent);
    background-repeat: repeat;
    background-size: 200px 100px;
    animation: particleFloat 20s linear infinite;
}

.preview-gradient {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(0, 245, 255, 0.1) 0%, rgba(255, 0, 128, 0.1) 100%);
}

.preview-content {
    position: relative;
    z-index: 2;
    padding: 40px;
    display: flex;
    align-items: center;
    gap: 30px;
    color: white;
}

.preview-avatar {
    position: relative;
    flex-shrink: 0;
}

.avatar-img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgba(255, 255, 255, 0.3);
    object-fit: cover;
    position: relative;
    z-index: 2;
}

.avatar-glow {
    position: absolute;
    top: -10px;
    left: -10px;
    right: -10px;
    bottom: -10px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0, 245, 255, 0.4) 0%, transparent 70%);
    animation: avatarGlow 3s ease-in-out infinite alternate;
}

.status-indicator {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid white;
    z-index: 3;
}

.status-online { background: #00ff41; }
.status-away { background: #ffa500; }
.status-busy { background: #ff4757; }
.status-offline { background: #747d8c; }

.preview-info {
    flex: 1;
}

.preview-name {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 8px 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.preview-title {
    font-size: 18px;
    opacity: 0.9;
    margin: 0 0 8px 0;
}

.preview-status {
    font-size: 14px;
    opacity: 0.8;
    margin: 0;
}

.preview-stats {
    display: flex;
    gap: 30px;
    flex-shrink: 0;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    min-width: 80px;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 5px;
    color: #00f5ff;
    text-shadow: 0 0 10px rgba(0, 245, 255, 0.5);
}

.stat-label {
    font-size: 12px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.profile-form-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.profile-form-card .card-header {
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.profile-form-card .card-header h3 {
    font-size: 24px;
    margin: 0 0 8px 0;
}

.profile-form-card .card-header p {
    opacity: 0.9;
    margin: 0;
}

.profile-form {
    padding: 40px;
}

.profile-form .form-group {
    margin-bottom: 25px;
    position: relative;
}

.profile-form .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
    position: relative;
}

.profile-form .form-group label::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 16px;
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    border-radius: 2px;
}

.profile-form input,
.profile-form textarea,
.profile-form select {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.profile-form input:focus,
.profile-form textarea:focus,
.profile-form select:focus {
    outline: none;
    border-color: #00f5ff;
    background: white;
    box-shadow: 0 0 0 3px rgba(0, 245, 255, 0.1);
    transform: translateY(-2px);
}

.profile-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.profile-form textarea {
    resize: vertical;
    min-height: 120px;
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

/* 动画效果 */
@keyframes particleFloat {
    0% { transform: translateX(0px) translateY(0px); }
    33% { transform: translateX(30px) translateY(-30px); }
    66% { transform: translateX(-20px) translateY(20px); }
    100% { transform: translateX(0px) translateY(0px); }
}

@keyframes avatarGlow {
    0% { opacity: 0.5; transform: scale(1); }
    100% { opacity: 0.8; transform: scale(1.1); }
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

/* 响应式设计 */
@media (max-width: 768px) {
    .preview-content {
        flex-direction: column;
        text-align: center;
        padding: 30px 20px;
    }
    
    .preview-stats {
        justify-content: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .stat-item {
        min-width: 60px;
        padding: 15px;
    }
    
    .profile-form {
        padding: 30px 20px;
    }
    
    .profile-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

/* 输入框动画效果 */
.profile-form .form-group {
    position: relative;
    overflow: hidden;
}

.profile-form .form-group::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    transition: width 0.3s ease;
}

.profile-form .form-group:focus-within::after {
    width: 100%;
}

/* 头像上传区域样式已移至组件中 */
</style>

<script>
function resetForm() {
    if (confirm('确定要重置表单吗？所有未保存的更改将丢失。')) {
        document.querySelector('.profile-form').reset();
    }
}

// 实时预览更新
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const titleInput = document.getElementById('title');
    const statusInput = document.getElementById('status_text');
    const statusTypeSelect = document.getElementById('status_type');
    const projectsInput = document.getElementById('projects_count');
    const experienceInput = document.getElementById('experience_years');
    const skillsInput = document.getElementById('skills_count');
    
    const previewName = document.querySelector('.preview-name');
    const previewTitle = document.querySelector('.preview-title');
    const previewStatus = document.querySelector('.preview-status');
    const statusIndicator = document.querySelector('.status-indicator');
    const statNumbers = document.querySelectorAll('.stat-number');
    
    function updatePreview() {
        if (nameInput && previewName) previewName.textContent = nameInput.value || 'HomePage';
        if (titleInput && previewTitle) previewTitle.textContent = titleInput.value || '欢迎使用';
        if (statusInput && previewStatus) previewStatus.textContent = statusInput.value || '在线';
        
        if (statusTypeSelect && statusIndicator) {
            statusIndicator.className = 'status-indicator status-' + statusTypeSelect.value;
        }
        
        if (projectsInput && statNumbers[0]) statNumbers[0].textContent = projectsInput.value || '0';
        if (experienceInput && statNumbers[1]) statNumbers[1].textContent = experienceInput.value || '0';
        if (skillsInput && statNumbers[2]) statNumbers[2].textContent = skillsInput.value || '0';
    }
    
    // 绑定事件监听器
    [nameInput, titleInput, statusInput, statusTypeSelect, projectsInput, experienceInput, skillsInput].forEach(input => {
        if (input) {
            input.addEventListener('input', updatePreview);
            input.addEventListener('change', updatePreview);
        }
    });
    
    // 头像上传功能已移至组件中
});

// 上传进度功能已移至组件中

// 表单提交时的额外验证
document.querySelector('.profile-form').addEventListener('submit', function(e) {
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const websiteInput = document.getElementById('website');
    
    // 验证必填字段
    if (!nameInput.value.trim()) {
        e.preventDefault();
        alert('请输入姓名');
        nameInput.focus();
        return;
    }
    
    // 验证邮箱格式
    if (emailInput.value && !isValidEmail(emailInput.value)) {
        e.preventDefault();
        alert('请输入正确的邮箱地址');
        emailInput.focus();
        return;
    }
    
    // 验证网站地址格式
    if (websiteInput.value && !isValidUrl(websiteInput.value)) {
        e.preventDefault();
        alert('请输入正确的网站地址');
        websiteInput.focus();
        return;
    }
    
    // 显示提交状态
    const submitBtn = document.querySelector('.btn-primary');
    if (submitBtn) {
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span>保存中...';
        submitBtn.disabled = true;
    }
});

// 邮箱验证函数
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// URL验证函数
function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}EventListener('change', updatePreview);
        }
    });
    
    // 自动隐藏成功消息
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                successAlert.remove();
            }, 300);
        }, 3000);
    }
    
    // 头像上传功能
    const avatarContainer = document.querySelector('.current-avatar');
    const avatarInput = document.getElementById('avatar');
    const currentAvatarImg = document.getElementById('currentAvatarImg');
    
    if (avatarContainer && avatarInput) {
        // 点击头像区域触发文件选择
        avatarContainer.addEventListener('click', () => {
            avatarInput.click();
        });
        
        // 文件选择后预览
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // 验证文件类型
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('只支持 JPG、PNG、GIF、WebP 格式的图片');
                    return;
                }
                
                // 验证文件大小 (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('文件大小不能超过 5MB');
                    return;
                }
                
                // 预览图片
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentAvatarImg.src = e.target.result;
                    
                    // 同时更新预览卡片中的头像
                    const previewAvatar = document.querySelector('.avatar-img');
                    if (previewAvatar) {
                        previewAvatar.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
        
        // 拖拽上传支持
        avatarContainer.addEventListener('dragover', function(e) {
            e.preventDefault();
            avatarContainer.style.borderColor = '#00f5ff';
            avatarContainer.style.backgroundColor = 'rgba(0, 245, 255, 0.1)';
        });
        
        avatarContainer.addEventListener('dragleave', function(e) {
            e.preventDefault();
            avatarContainer.style.borderColor = '#e9ecef';
            avatarContainer.style.backgroundColor = 'transparent';
        });
        
        avatarContainer.addEventListener('drop', function(e) {
            e.preventDefault();
            avatarContainer.style.borderColor = '#e9ecef';
            avatarContainer.style.backgroundColor = 'transparent';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                avatarInput.files = files;
                avatarInput.dispatchEvent(new Event('change'));
            }
        });
    }
});
</script>