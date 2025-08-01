<?php
/**
 * 图片管理辅助函数
 * 统一处理后台所有图片上传和管理功能
 */

/**
 * 上传图片到指定目录
 * 
 * @param array $file $_FILES中的文件信息
 * @param string $type 图片类型 (avatar, icon, banner等)
 * @param array $options 上传选项
 * @return array 上传结果
 */
function uploadImage($file, $type = 'general', $options = []) {
    // 默认配置
    $defaultOptions = [
        'maxSize' => 5 * 1024 * 1024, // 5MB
        'allowedTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        'maxWidth' => 2000,
        'maxHeight' => 2000,
        'quality' => 85
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
        // 使用fileinfo扩展检查MIME类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } else {
        // 回退方法：使用文件扩展名判断类型
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        $mimeType = $mimeMap[$extension] ?? 'application/octet-stream';
    }
    
    if (!in_array($mimeType, $options['allowedTypes'])) {
        return ['success' => false, 'message' => '不支持的文件格式: ' . $mimeType];
    }
    
    // 获取图片信息
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return ['success' => false, 'message' => '无效的图片文件'];
    }
    
    // 生成文件名
    $extension = getExtensionFromMimeType($mimeType);
    $fileName = generateImageFileName($type, $extension);
    
    // 处理上传目录
    $uploadDir = $options['uploadDir'] ?? 'admin/img/';
    $uploadPath = $uploadDir . $fileName;
    
    // 确保目录存在
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 处理图片（可选的压缩和调整大小）
    $processedImage = processImage($file['tmp_name'], $imageInfo, $options);
    
    if ($processedImage) {
        // 保存处理后的图片
        if (imagejpeg($processedImage, $uploadPath, $options['quality'])) {
            imagedestroy($processedImage);
            return [
                'success' => true, 
                'path' => $uploadPath,
                'fileName' => $fileName,
                'size' => filesize($uploadPath),
                'dimensions' => getimagesize($uploadPath)
            ];
        } else {
            imagedestroy($processedImage);
            return ['success' => false, 'message' => '图片保存失败'];
        }
    } else {
        // 直接移动文件
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => true, 
                'path' => $uploadPath,
                'fileName' => $fileName,
                'size' => filesize($uploadPath),
                'dimensions' => $imageInfo
            ];
        } else {
            return ['success' => false, 'message' => '文件移动失败'];
        }
    }
}

/**
 * 处理图片（压缩、调整大小）
 */
function processImage($sourcePath, $imageInfo, $options) {
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    // 如果图片尺寸在限制范围内，不需要处理
    if ($width <= $options['maxWidth'] && $height <= $options['maxHeight']) {
        return null;
    }
    
    // 创建源图片资源
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return null;
    }
    
    if (!$source) {
        return null;
    }
    
    // 计算新尺寸
    $ratio = min($options['maxWidth'] / $width, $options['maxHeight'] / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // 创建新图片
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // 保持透明度
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // 调整图片大小
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    imagedestroy($source);
    return $newImage;
}

/**
 * 根据MIME类型获取文件扩展名
 */
function getExtensionFromMimeType($mimeType) {
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    return $extensions[$mimeType] ?? 'jpg';
}

/**
 * 生成图片文件名
 */
function generateImageFileName($type, $extension) {
    $timestamp = time();
    $random = uniqid();
    return "{$type}_{$timestamp}_{$random}.{$extension}";
}

/**
 * 删除图片文件
 */
function deleteImage($imagePath) {
    // 处理网页路径转换为服务器路径
    if (strpos($imagePath, '/admin/img/') === 0) {
        // 将网页路径转换为服务器路径
        $serverPath = __DIR__ . '/../img/' . basename($imagePath);
        if (file_exists($serverPath)) {
            return unlink($serverPath);
        }
    } elseif ($imagePath && file_exists($imagePath)) {
        // 直接使用提供的路径
        return unlink($imagePath);
    }
    return false;
}

/**
 * 获取图片信息
 */
function getImageInfo($imagePath) {
    if (!file_exists($imagePath)) {
        return null;
    }
    
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        return null;
    }
    
    return [
        'width' => $imageInfo[0],
        'height' => $imageInfo[1],
        'type' => $imageInfo[2],
        'mime' => $imageInfo['mime'],
        'size' => filesize($imagePath),
        'url' => $imagePath
    ];
}

/**
 * 生成缩略图
 */
function generateThumbnail($sourcePath, $width = 150, $height = 150) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $type = $imageInfo[2];
    
    // 创建源图片资源
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // 创建缩略图
    $thumbnail = imagecreatetruecolor($width, $height);
    
    // 保持透明度
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $width, $height, $transparent);
    }
    
    // 调整图片大小
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $imageInfo[0], $imageInfo[1]);
    
    // 生成缩略图文件名
    $pathInfo = pathinfo($sourcePath);
    $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['filename'] . '.jpg';
    
    // 保存缩略图
    $result = imagejpeg($thumbnail, $thumbnailPath, 85);
    
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $result ? $thumbnailPath : false;
}
?>