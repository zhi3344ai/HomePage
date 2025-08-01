<?php
/**
 * 个人信息管理页面 - 简化版
 */

/**
 * 简单的头像上传处理
 */
function handleSimpleAvatarUpload($file) {
    // 检查文件大小 (最大5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '文件大小不能超过5MB'];
    }
    
    // 检查文件类型
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => '只支持 JPG、PNG、GIF、WebP 格式的图片'];
    }
    
    // 生成唯一文件名
    $fileName = 'avatar_' . time() . '_' . uniqid() . '.' . $extension;
    $uploadPath = 'admin/img/' . $fileName;
    
    // 确保目录存在
    if (!is_dir('admin/img')) {
        mkdir('admin/img', 0755, true);
    }
    
    // 删除旧头像文件
    deleteOldAvatar();
    
    // 移动文件
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'path' => $uploadPath];
    } else {
        return ['success' => false, 'message' => '文件上传失败，请检查目录权限'];
    }
}

/**
 * 删除旧头像
 */
function deleteOldAvatar() {
    try {
        $oldProfile = fetchOne("SELECT avatar FROM profile WHERE id = 1");
        if ($oldProfile && $oldProfile['avatar'] && 
            $oldProfile['avatar'] !== 'assets/images/avatar.svg' && 
            strpos($oldProfile['avatar'], 'admin/img/') === 0 &&
            file_exists($oldProfile['avatar'])) {
            unlink($oldProfile['avatar']);
        }
    } catch (Exception $e) {
        // 忽略错误，继续执行
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
        $uploadResult = handleSimpleAvatarUpload($_FILES['avatar']);
        if ($uploadResult['success']) {
            $data['avatar'] = $uploadResult['path'];
        } else {
            $error = $uploadResult['message'];
        }
    }
    
    // 数据验证
    if (empty($data['name'])) {
        $error = '姓名不能为空';
    } elseif (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } elseif (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
        $error = '网站地址格式不正确';
    } elseif (strlen($data['phone']) > 20) {
        $error = '电话号码不能超过20个字符';
    } else {
        try {
            // 先检查数据库中是否已有记录
            $existingProfile = fetchOne("SELECT * FROM profile WHERE id = 1");
            
            if ($existingProfile) {
                // 更新现有记录
                $updateFields = [];
                $updateParams = [];
                
                foreach ($data as $key => $value) {
                    $updateFields[] = "`{$key}` = ?";
                    $updateParams[] = $value;
                }
                
                $sql = "UPDATE `profile` SET " . implode(', ', $updateFields) . " WHERE `id` = 1";
                
                $stmt = getDatabase()->prepare($sql);
                $result = $stmt->execute($updateParams);
                
                if ($result) {
                    $success = '个人信息更新成功！';
                    logAction('update', '更新个人信息');
                } else {
                    $error = '更新失败';
                }
            } else {
                // 插入新记录
                $columns = array_keys($data);
                $placeholders = array_fill(0, count($columns), '?');
                
                $sql = "INSERT INTO `profile` (`id`, `" . implode('`, `', $columns) . "`) VALUES (1, " . implode(', ', $placeholders) . ")";
                
                $stmt = getDatabase()->prepare($sql);
                $result = $stmt->execute(array_values($data));
                
                if ($result) {
                    $success = '个人信息创建成功！';
                    logAction('create', '创建个人信息');
                } else {
                    $error = '创建失败';
                }
            }
            
        } catch (Exception $e) {
            $error = '保存失败: ' . $e->getMessage();
        }
    }
}

// 获取个人信息
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
    
    $profile = array_merge(['id' => 1], $defaultProfile);
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
        <div class="preview-content">
            <div class="preview-avatar">
                <img src="<?= htmlspecialchars($profile['avatar']) ?>" alt="头像" class="avatar-img">
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
                    <div class="avatar-upload-simple">
                        <div class="current-avatar-simple">
                            <img src="<?= htmlspecialchars($profile['avatar']) ?>" alt="当前头像" id="avatarPreview">
                        </div>
                        <div class="upload-info">
                            <input type="file" name="avatar" id="avatarInput" accept="image/*">
                            <p>支持 JPG、PNG、GIF、WebP 格式</p>
                            <p>文件大小不超过 5MB</p>
                        </div>
                    </div>
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
                        <label for="projects_count">项目</label>
                        <input type="number" id="projects_count" name="projects_count" value="<?= $profile['projects_count'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="experience_years">经验</label>
                        <input type="number" id="experience_years" name="experience_years" value="<?= $profile['experience_years'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="skills_count">技能</label>
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
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" maxlength="20">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="location">地域</label>
                        <input type="text" id="location" name="location" value="<?= htmlspecialchars($profile['location']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="website">网站</label>
                        <input type="url" id="website" name="website" value="<?= htmlspecialchars($profile['website'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">💾</span>
                        保存更改
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* 简化版样式 */
.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
}

.alert-error {
    background: #fee;
    color: #c33;
    border: 2px solid #fcc;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 2px solid #b8e6c1;
}

.profile-preview-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    margin-bottom: 30px;
    color: white;
}

.preview-content {
    padding: 40px;
    display: flex;
    align-items: center;
    gap: 30px;
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
}

.status-indicator {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid white;
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
    min-width: 80px;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 5px;
    color: #00f5ff;
}

.stat-label {
    font-size: 12px;
    opacity: 0.8;
    text-transform: uppercase;
}

.profile-form-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.card-header h3 {
    font-size: 24px;
    margin: 0 0 8px 0;
}

.card-header p {
    opacity: 0.9;
    margin: 0;
}

.profile-form {
    padding: 40px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
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
}

.profile-form textarea {
    resize: vertical;
    min-height: 120px;
}

.avatar-upload-simple {
    display: flex;
    align-items: center;
    gap: 20px;
}

.current-avatar-simple img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

.upload-info p {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
}

.form-actions {
    display: flex;
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

.btn-icon {
    font-size: 18px;
}

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
    
    .profile-form {
        padding: 30px 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .avatar-upload-simple {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// 头像预览功能
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
            // 同时更新预览卡片中的头像
            const previewAvatar = document.querySelector('.preview-content .avatar-img');
            if (previewAvatar) {
                previewAvatar.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
});

// 实时预览更新
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['name', 'title', 'status_text', 'status_type', 'projects_count', 'experience_years', 'skills_count'];
    const previews = ['.preview-name', '.preview-title', '.preview-status', '.status-indicator', '.stat-number'];
    
    inputs.forEach((inputId, index) => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', function() {
                if (inputId === 'name') {
                    document.querySelector('.preview-name').textContent = this.value || 'HomePage';
                } else if (inputId === 'title') {
                    document.querySelector('.preview-title').textContent = this.value || '欢迎使用';
                } else if (inputId === 'status_text') {
                    document.querySelector('.preview-status').textContent = this.value || '在线';
                } else if (inputId === 'status_type') {
                    const indicator = document.querySelector('.status-indicator');
                    indicator.className = 'status-indicator status-' + this.value;
                } else if (inputId === 'projects_count') {
                    document.querySelectorAll('.stat-number')[0].textContent = this.value || '0';
                } else if (inputId === 'experience_years') {
                    document.querySelectorAll('.stat-number')[1].textContent = this.value || '0';
                } else if (inputId === 'skills_count') {
                    document.querySelectorAll('.stat-number')[2].textContent = this.value || '0';
                }
            });
        }
    });
});
</script>