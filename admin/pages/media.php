<?php
/**
 * 媒体文件管理页面
 * 统一管理所有上传的图片和文件
 */

// 引入图片管理工具（优先使用高级版本，回退到简化版本）
if (extension_loaded('gd')) {
    require_once __DIR__ . '/../includes/image-helper.php';
    $useAdvancedProcessor = true;
} else {
    require_once __DIR__ . '/../includes/simple-image-handler.php';
    $useAdvancedProcessor = false;
}

// 处理文件操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload':
            if (isset($_FILES['files'])) {
                $results = [];
                $files = $_FILES['files'];
                
                // 处理多文件上传
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        if ($useAdvancedProcessor) {
                            // 确保上传到正确的目录
                            $options = ['uploadDir' => __DIR__ . '/../img/'];
                            $result = uploadImage($file, 'media', $options);
                        } else {
                            $result = simpleImageUpload($file, 'media');
                        }
                        $results[] = $result;
                    }
                }
                
                $uploadResults = $results;
            }
            break;
            
        case 'delete':
            $imagePath = $_POST['image_path'] ?? '';
            if ($useAdvancedProcessor) {
                $deleteResult = deleteImage($imagePath);
            } else {
                $deleteResult = deleteImageFile($imagePath);
            }
            
            if ($deleteResult) {
                $success = '文件删除成功';
            } else {
                $error = '文件删除失败';
            }
            break;
    }
}

// 获取所有图片文件
function getMediaFiles() {
    global $useAdvancedProcessor;
    
    if ($useAdvancedProcessor) {
        $mediaDir = __DIR__ . '/../img/';
        $files = [];
        
        if (is_dir($mediaDir)) {
            $items = scandir($mediaDir);
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && !is_dir($mediaDir . $item)) {
                    $filePath = $mediaDir . $item;
                    $webPath = '/admin/img/' . $item; // 用于网页显示的路径
                    // 确保没有双斜杠
                    $webPath = str_replace('//', '/', $webPath);
                    $imageInfo = getImageInfo($filePath);
                    if ($imageInfo) {
                        $files[] = [
                            'name' => $item,
                            'path' => $webPath,
                            'info' => $imageInfo,
                            'created' => filemtime($filePath)
                        ];
                    }
                }
            }
        }
        
        // 按创建时间倒序排列
        usort($files, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $files;
    } else {
        // 使用简化版处理器
        $mediaFiles = getMediaFileList();
        $files = [];
        
        foreach ($mediaFiles as $file) {
            $files[] = [
                'name' => $file['name'],
                'path' => $file['path'],
                'info' => [
                    'width' => $file['width'] ?? 0,
                    'height' => $file['height'] ?? 0,
                    'size' => $file['size'],
                    'mime' => $file['mime'] ?? 'image/unknown'
                ],
                'created' => $file['modified']
            ];
        }
        
        return $files;
    }
}

$mediaFiles = getMediaFiles();
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
    
    <?php if (isset($uploadResults)): ?>
        <div class="upload-results">
            <?php foreach ($uploadResults as $result): ?>
                <?php if ($result['success']): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">✅</span>
                        <span class="alert-message">文件 <?= htmlspecialchars($result['fileName']) ?> 上传成功</span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">❌</span>
                        <span class="alert-message"><?= htmlspecialchars($result['message']) ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- 系统能力检查 -->
    <?php if (!$useAdvancedProcessor): ?>
        <div class="alert alert-warning">
            <span class="alert-icon">⚠️</span>
            <span class="alert-message">
                GD扩展未安装，图片处理功能受限。建议安装GD扩展以获得完整的图片处理能力。
                <a href="?page=settings" style="color: #0066cc; text-decoration: underline;">查看系统信息</a>
            </span>
        </div>
    <?php endif; ?>
    
    <!-- 上传区域 -->
    <div class="media-upload-card">
        <div class="card-header">
            <h3>📁 文件上传</h3>
            <p>拖拽文件到此处或点击选择文件</p>
        </div>
        <div class="card-content">
            <form method="post" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="action" value="upload">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">📷</div>
                    <div class="upload-text">
                        <p><strong>点击选择文件</strong> 或拖拽到此处</p>
                        <p>支持 JPG、PNG、GIF、WebP 格式，单个文件最大 5MB</p>
                    </div>
                    <input type="file" name="files[]" id="fileInput" multiple accept="image/*" style="display: none;">
                </div>
                <div class="upload-actions">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('fileInput').click()">
                        <span class="btn-icon">📁</span>
                        选择文件
                    </button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn" style="display: none;">
                        <span class="btn-icon">⬆️</span>
                        开始上传
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 媒体库 -->
    <div class="media-library-card">
        <div class="card-header">
            <h3>🖼️ 媒体库</h3>
            <p>管理所有上传的图片文件 (共 <?= count($mediaFiles) ?> 个文件)</p>
        </div>
        <div class="card-content">
            <?php if (empty($mediaFiles)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📷</div>
                    <h4>暂无媒体文件</h4>
                    <p>上传一些图片文件开始使用媒体库</p>
                </div>
            <?php else: ?>
                <div class="media-grid">
                    <?php foreach ($mediaFiles as $file): ?>
                        <div class="media-item" data-path="<?= htmlspecialchars($file['path']) ?>">
                            <div class="media-preview">
                                <img src="<?= htmlspecialchars($file['path']) ?>" alt="<?= htmlspecialchars($file['name']) ?>" loading="lazy">
                                <div class="media-overlay">
                                    <button type="button" class="btn-icon-small" onclick="previewImage('<?= htmlspecialchars($file['path']) ?>')" title="预览">
                                        👁️
                                    </button>
                                    <button type="button" class="btn-icon-small" onclick="copyImageUrl('<?= htmlspecialchars($file['path']) ?>')" title="复制链接">
                                        📋
                                    </button>
                                    <button type="button" class="btn-icon-small btn-danger" onclick="deleteImage('<?= htmlspecialchars($file['path']) ?>')" title="删除">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                            <div class="media-info">
                                <div class="media-name" title="<?= htmlspecialchars($file['name']) ?>">
                                    <?= htmlspecialchars(strlen($file['name']) > 20 ? substr($file['name'], 0, 17) . '...' : $file['name']) ?>
                                </div>
                                <div class="media-details">
                                    <span><?= $file['info']['width'] ?>×<?= $file['info']['height'] ?></span>
                                    <span><?= formatFileSize($file['info']['size']) ?></span>
                                </div>
                                <div class="media-date">
                                    <?= date('Y-m-d H:i', $file['created']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 图片预览模态框 -->
<div id="imageModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h4>图片预览</h4>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <img id="modalImage" src="" alt="预览图片">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">关闭</button>
        </div>
    </div>
</div>

<style>
/* 媒体管理页面样式 */

/* 警告提示样式 */
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

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    color: #856404;
    border: 2px solid #ffeaa7;
    box-shadow: 0 4px 15px rgba(133, 100, 4, 0.2);
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
.media-upload-card,
.media-library-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.media-upload-card .card-header,
.media-library-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.media-upload-card .card-header h3,
.media-library-card .card-header h3 {
    font-size: 24px;
    margin: 0 0 8px 0;
}

.media-upload-card .card-header p,
.media-library-card .card-header p {
    opacity: 0.9;
    margin: 0;
}

.upload-area {
    border: 3px dashed #ddd;
    border-radius: 15px;
    padding: 60px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.upload-area:hover,
.upload-area.dragover {
    border-color: #00f5ff;
    background: rgba(0, 245, 255, 0.05);
    transform: scale(1.02);
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 20px;
    opacity: 0.6;
}

.upload-text p {
    margin: 8px 0;
    color: #666;
}

.upload-text p:first-child {
    font-size: 18px;
    color: #333;
}

.upload-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 30px;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.media-item {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.media-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.media-preview {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
}

.media-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.media-item:hover .media-preview img {
    transform: scale(1.1);
}

.media-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.media-item:hover .media-overlay {
    opacity: 1;
}

.btn-icon-small {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    color: #333;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-icon-small:hover {
    background: white;
    transform: scale(1.1);
}

.btn-icon-small.btn-danger:hover {
    background: #ff4757;
    color: white;
}

.media-info {
    padding: 15px;
}

.media-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
}

.media-details {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.media-date {
    font-size: 11px;
    color: #999;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-icon {
    font-size: 64px;
    opacity: 0.3;
    margin-bottom: 20px;
}

.empty-state h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.empty-state p {
    margin: 0;
    opacity: 0.8;
}

/* 模态框样式 */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background: white;
    border-radius: 15px;
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    animation: slideIn 0.3s ease;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    border-bottom: 1px solid #eee;
}

.modal-header h4 {
    margin: 0;
    color: #333;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
    text-align: center;
}

.modal-body img {
    max-width: 100%;
    max-height: 70vh;
    border-radius: 8px;
}

.modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #eee;
    text-align: right;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: scale(0.9) translateY(-20px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}

/* 响应式设计 */
@media (max-width: 768px) {
    .media-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .upload-area {
        padding: 40px 15px;
    }
    
    .upload-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .modal-content {
        margin: 20px;
        max-width: calc(100vw - 40px);
    }
}

/* 上传进度样式 */
.upload-progress {
    width: 100%;
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 15px;
}

.upload-progress-bar {
    height: 100%;
    background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
    width: 0%;
    transition: width 0.3s ease;
}
</style>

<script>
// 文件上传功能
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const uploadBtn = document.getElementById('uploadBtn');
    
    // 点击上传区域选择文件
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // 文件选择后显示上传按钮
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            uploadBtn.style.display = 'inline-flex';
            updateUploadText();
        }
    });
    
    // 拖拽上传
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            uploadBtn.style.display = 'inline-flex';
            updateUploadText();
        }
    });
    
    function updateUploadText() {
        const fileCount = fileInput.files.length;
        const uploadText = uploadArea.querySelector('.upload-text p:first-child');
        uploadText.innerHTML = `<strong>已选择 ${fileCount} 个文件</strong>`;
    }
});

// 预览图片
function previewImage(imagePath) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    
    modalImage.src = imagePath;
    modal.style.display = 'flex';
    
    // 点击模态框背景关闭
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
}

// 关闭模态框
function closeModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
}

// 复制图片链接
function copyImageUrl(imagePath) {
    // 确保路径格式正确
    if (imagePath.startsWith('/')) {
        // 已经是绝对路径，直接使用
        const fullUrl = window.location.origin + imagePath;
        copyToClipboard(fullUrl);
    } else {
        // 相对路径，添加斜杠
        const fullUrl = window.location.origin + '/' + imagePath;
        copyToClipboard(fullUrl);
    }
}

// 辅助函数：复制到剪贴板
function copyToClipboard(text) {
    // 确保没有双斜杠（除了http://之后）
    text = text.replace(/(https?:\/\/)|(\/\/)/g, function(match, p1) {
        return p1 || '/';
    });
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('图片链接已复制到剪贴板');
        });
    } else {
        // 兼容旧浏览器
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('图片链接已复制到剪贴板');
    }
}

// 删除图片
function deleteImage(imagePath) {
    if (confirm('确定要删除这个图片吗？此操作不可撤销。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="image_path" value="${imagePath}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// 显示提示消息
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #333;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 1001;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// 添加动画样式
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// ESC键关闭模态框
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php
// 检查formatFileSize函数是否已经存在，如果不存在才定义它
if (!function_exists('formatFileSize')) {
    // 格式化文件大小
    function formatFileSize($bytes) {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}
?>
