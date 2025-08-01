<?php
/**
 * 简化版图片处理器
 * 不依赖GD扩展，提供基本的文件上传和管理功能
 */

/**
 * 简单的图片上传处理
 */
function simpleImageUpload($file, $type = 'general', $options = []) {
    // 默认配置
    $defaultOptions = [
        'maxSize' => 5 * 1024 * 1024, // 5MB
        'allowedTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        'uploadDir' => 'admin/img/'
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    // 检查文件是否上传成功
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '文件上传失败'];
    }
    
    // 检查文件大小
    if ($file['size'] > $options['maxSize']) {
        $maxSizeMB = round($options['maxSize'] / 1024 / 1024, 1);
        return ['success' => false, 'message' => "文件大小不能超过 {$maxSizeMB}MB"];
    }
    
    // 检查文件类型
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $options['allowedTypes'])) {
            return ['success' => false, 'message' => '不支持的文件格式'];
        }
    } else {
        // 如果没有fileinfo扩展，通过文件扩展名检查
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions)) {
            return ['success' => false, 'message' => '不支持的文件格式'];
        }
    }
    
    // 生成文件名
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = $type . '_' . time() . '_' . uniqid() . '.' . $extension;
    $uploadPath = $options['uploadDir'] . $fileName;
    
    // 确保目录存在
    if (!is_dir($options['uploadDir'])) {
        mkdir($options['uploadDir'], 0755, true);
    }
    
    // 移动文件
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'success' => true,
            'path' => $uploadPath,
            'fileName' => $fileName,
            'size' => filesize($uploadPath),
            'originalName' => $file['name']
        ];
    } else {
        return ['success' => false, 'message' => '文件保存失败'];
    }
}

/**
 * 简单的头像上传处理
 */
function simpleAvatarUpload($file) {
    return simpleImageUpload($file, 'avatar', [
        'maxSize' => 5 * 1024 * 1024,
        'uploadDir' => 'admin/img/'
    ]);
}

/**
 * 删除图片文件
 */
function deleteImageFile($imagePath) {
    if ($imagePath && file_exists($imagePath) && strpos($imagePath, 'admin/img/') === 0) {
        return unlink($imagePath);
    }
    return false;
}

/**
 * 获取基本文件信息
 */
function getBasicFileInfo($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }
    
    $info = [
        'path' => $filePath,
        'size' => filesize($filePath),
        'modified' => filemtime($filePath),
        'extension' => strtolower(pathinfo($filePath, PATHINFO_EXTENSION))
    ];
    
    // 如果有GD扩展，获取图片尺寸
    if (function_exists('getimagesize')) {
        $imageInfo = getimagesize($filePath);
        if ($imageInfo) {
            $info['width'] = $imageInfo[0];
            $info['height'] = $imageInfo[1];
            $info['mime'] = $imageInfo['mime'];
        }
    }
    
    return $info;
}

/**
 * 验证图片文件
 */
function validateImageFile($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    // 检查文件扩展名
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }
    
    // 如果有getimagesize函数，进一步验证
    if (function_exists('getimagesize')) {
        $imageInfo = getimagesize($filePath);
        return $imageInfo !== false;
    }
    
    return true;
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

/**
 * 获取媒体文件列表
 */
function getMediaFileList($directory = 'admin/img/') {
    $files = [];
    
    if (!is_dir($directory)) {
        return $files;
    }
    
    $items = scandir($directory);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && !is_dir($directory . $item)) {
            $filePath = $directory . $item;
            if (validateImageFile($filePath)) {
                $fileInfo = getBasicFileInfo($filePath);
                if ($fileInfo) {
                    $files[] = array_merge($fileInfo, ['name' => $item]);
                }
            }
        }
    }
    
    // 按修改时间倒序排列
    usort($files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $files;
}

/**
 * 清理旧的头像文件
 */
function cleanupOldAvatar($currentAvatarPath = null) {
    if (!$currentAvatarPath) {
        // 从数据库获取当前头像路径
        try {
            require_once 'admin/includes/database.php';
            $profile = fetchOne("SELECT avatar FROM profile WHERE id = 1");
            $currentAvatarPath = $profile['avatar'] ?? '';
        } catch (Exception $e) {
            return false;
        }
    }
    
    if ($currentAvatarPath && 
        $currentAvatarPath !== 'assets/images/avatar.svg' && 
        strpos($currentAvatarPath, 'admin/img/') === 0 &&
        file_exists($currentAvatarPath)) {
        return unlink($currentAvatarPath);
    }
    
    return false;
}

/**
 * 检查系统图片处理能力
 */
function checkImageProcessingCapabilities() {
    $capabilities = [
        'fileinfo' => extension_loaded('fileinfo'),
        'gd' => extension_loaded('gd'),
        'imagick' => extension_loaded('imagick'),
        'getimagesize' => function_exists('getimagesize'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads')
    ];
    
    return $capabilities;
}
?>