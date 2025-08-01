<?php
/**
 * 头像上传组件
 * 可复用的头像上传界面组件
 */

/**
 * 渲染头像上传器
 * 
 * @param string $currentAvatar 当前头像路径
 * @param string $inputName 输入框名称
 * @param array $options 配置选项
 */
function renderAvatarUploader($currentAvatar = '', $inputName = 'avatar', $options = []) {
    $defaultOptions = [
        'size' => 120,
        'showInfo' => true,
        'allowDrag' => true,
        'previewUpdate' => true,
        'maxSize' => '5MB',
        'formats' => 'JPG、PNG、GIF、WebP',
        'recommended' => '200x200 像素'
    ];
    
    $options = array_merge($defaultOptions, $options);
    $uploaderId = 'uploader_' . uniqid();
    
    ?>
    <div class="avatar-uploader" id="<?= $uploaderId ?>">
        <div class="avatar-upload-container">
            <div class="current-avatar" style="width: <?= $options['size'] ?>px; height: <?= $options['size'] ?>px;">
                <img src="<?= htmlspecialchars($currentAvatar ?: 'assets/images/avatar.svg') ?>" 
                     alt="当前头像" 
                     class="avatar-preview-img">
                <div class="avatar-overlay">
                    <span class="upload-icon">📷</span>
                    <span class="upload-text">点击上传</span>
                </div>
                <div class="upload-progress" style="display: none;">
                    <div class="upload-progress-bar"></div>
                </div>
            </div>
            
            <?php if ($options['showInfo']): ?>
            <div class="avatar-info">
                <div class="info-item">
                    <strong>支持格式：</strong><?= $options['formats'] ?>
                </div>
                <div class="info-item">
                    <strong>文件大小：</strong>不超过 <?= $options['maxSize'] ?>
                </div>
                <div class="info-item">
                    <strong>建议尺寸：</strong><?= $options['recommended'] ?>
                </div>
                <?php if ($options['allowDrag']): ?>
                <div class="info-item">
                    <strong>上传方式：</strong>点击选择或拖拽文件
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <input type="file" 
               name="<?= htmlspecialchars($inputName) ?>" 
               class="avatar-input" 
               accept="image/*" 
               style="display: none;">
    </div>
    
    <style>
    .avatar-uploader {
        margin-bottom: 25px;
    }
    
    .avatar-upload-container {
        display: flex;
        align-items: flex-start;
        gap: 30px;
    }
    
    .current-avatar {
        position: relative;
        border-radius: 50%;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 4px solid #e9ecef;
        flex-shrink: 0;
    }
    
    .current-avatar:hover {
        border-color: #00f5ff;
        transform: scale(1.05);
    }
    
    .current-avatar.dragover {
        border-color: #ff0080;
        transform: scale(1.1);
        box-shadow: 0 0 20px rgba(255, 0, 128, 0.3);
    }
    
    .avatar-preview-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .current-avatar:hover .avatar-preview-img {
        transform: scale(1.1);
    }
    
    .avatar-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        color: white;
    }
    
    .current-avatar:hover .avatar-overlay {
        opacity: 1;
    }
    
    .upload-icon {
        font-size: 24px;
        margin-bottom: 5px;
    }
    
    .upload-text {
        font-size: 12px;
        font-weight: 600;
    }
    
    .upload-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: rgba(0, 0, 0, 0.3);
    }
    
    .upload-progress-bar {
        height: 100%;
        background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
        width: 0%;
        transition: width 0.3s ease;
    }
    
    .avatar-info {
        flex: 1;
        padding: 10px 0;
    }
    
    .info-item {
        margin: 8px 0;
        font-size: 14px;
        color: #666;
    }
    
    .info-item strong {
        color: #333;
        margin-right: 5px;
    }
    
    .info-item:first-child strong {
        color: #00f5ff;
    }
    
    /* 响应式设计 */
    @media (max-width: 768px) {
        .avatar-upload-container {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .current-avatar {
            margin-bottom: 20px;
        }
    }
    </style>
    
    <script>
    (function() {
        const uploader = document.getElementById('<?= $uploaderId ?>');
        const avatarContainer = uploader.querySelector('.current-avatar');
        const avatarInput = uploader.querySelector('.avatar-input');
        const previewImg = uploader.querySelector('.avatar-preview-img');
        const progressContainer = uploader.querySelector('.upload-progress');
        const progressBar = uploader.querySelector('.upload-progress-bar');
        
        // 点击上传
        avatarContainer.addEventListener('click', function() {
            avatarInput.click();
        });
        
        // 文件选择
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                validateAndPreview(file);
            }
        });
        
        <?php if ($options['allowDrag']): ?>
        // 拖拽上传
        avatarContainer.addEventListener('dragover', function(e) {
            e.preventDefault();
            avatarContainer.classList.add('dragover');
        });
        
        avatarContainer.addEventListener('dragleave', function(e) {
            e.preventDefault();
            avatarContainer.classList.remove('dragover');
        });
        
        avatarContainer.addEventListener('drop', function(e) {
            e.preventDefault();
            avatarContainer.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    // 创建新的FileList
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    avatarInput.files = dt.files;
                    
                    validateAndPreview(file);
                } else {
                    alert('请选择图片文件');
                }
            }
        });
        <?php endif; ?>
        
        function validateAndPreview(file) {
            // 验证文件类型
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('只支持 <?= $options['formats'] ?> 格式的图片');
                return;
            }
            
            // 验证文件大小
            const maxSize = <?= intval($options['maxSize']) * 1024 * 1024 ?>;
            if (file.size > maxSize) {
                alert('文件大小不能超过 <?= $options['maxSize'] ?>');
                return;
            }
            
            // 预览图片
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                
                <?php if ($options['previewUpdate']): ?>
                // 更新其他预览区域的头像
                const otherPreviews = document.querySelectorAll('.preview-content .avatar-img, .profile-preview .avatar-img');
                otherPreviews.forEach(img => {
                    if (img !== previewImg) {
                        img.src = e.target.result;
                    }
                });
                <?php endif; ?>
            };
            reader.readAsDataURL(file);
            
            // 显示上传进度
            showProgress();
        }
        
        function showProgress() {
            progressContainer.style.display = 'block';
            let progress = 0;
            
            const interval = setInterval(() => {
                progress += Math.random() * 20;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    setTimeout(() => {
                        progressContainer.style.display = 'none';
                        progressBar.style.width = '0%';
                    }, 1000);
                }
                progressBar.style.width = progress + '%';
            }, 150);
        }
    })();
    </script>
    <?php
}

/**
 * 渲染简化版头像上传器（仅显示当前头像和上传按钮）
 */
function renderSimpleAvatarUploader($currentAvatar = '', $inputName = 'avatar', $size = 80) {
    renderAvatarUploader($currentAvatar, $inputName, [
        'size' => $size,
        'showInfo' => false,
        'allowDrag' => false,
        'previewUpdate' => false
    ]);
}

/**
 * 渲染头像选择器（从媒体库选择）
 */
function renderAvatarSelector($currentAvatar = '', $inputName = 'avatar_path') {
    $uploaderId = 'selector_' . uniqid();
    ?>
    <div class="avatar-selector" id="<?= $uploaderId ?>">
        <div class="selected-avatar">
            <img src="<?= htmlspecialchars($currentAvatar ?: 'assets/images/avatar.svg') ?>" 
                 alt="选中的头像" 
                 class="avatar-preview">
            <button type="button" class="change-avatar-btn" onclick="openAvatarLibrary('<?= $uploaderId ?>')">
                更换头像
            </button>
        </div>
        <input type="hidden" name="<?= htmlspecialchars($inputName) ?>" value="<?= htmlspecialchars($currentAvatar) ?>">
    </div>
    
    <style>
    .avatar-selector {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .selected-avatar {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .avatar-preview {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e9ecef;
    }
    
    .change-avatar-btn {
        padding: 8px 16px;
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.3s ease;
    }
    
    .change-avatar-btn:hover {
        background: #5a6268;
    }
    </style>
    
    <script>
    function openAvatarLibrary(uploaderId) {
        // 这里可以打开媒体库选择界面
        alert('媒体库选择功能开发中...');
    }
    </script>
    <?php
}
?>