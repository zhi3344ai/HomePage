<?php
/**
 * 图片处理器
 * 提供高级图片处理功能，包括压缩、裁剪、水印等
 */

class ImageProcessor {
    private $supportedTypes = [
        IMAGETYPE_JPEG => 'jpeg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp'
    ];
    
    private $quality = 85;
    private $maxWidth = 2000;
    private $maxHeight = 2000;
    
    /**
     * 设置图片质量
     */
    public function setQuality($quality) {
        $this->quality = max(1, min(100, $quality));
        return $this;
    }
    
    /**
     * 设置最大尺寸
     */
    public function setMaxSize($width, $height) {
        $this->maxWidth = $width;
        $this->maxHeight = $height;
        return $this;
    }
    
    /**
     * 处理上传的图片
     */
    public function processUpload($file, $outputPath, $options = []) {
        if (!$this->validateFile($file)) {
            return ['success' => false, 'message' => '无效的图片文件'];
        }
        
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return ['success' => false, 'message' => '无法读取图片信息'];
        }
        
        $sourceImage = $this->createImageFromFile($file['tmp_name'], $imageInfo[2]);
        if (!$sourceImage) {
            return ['success' => false, 'message' => '无法创建图片资源'];
        }
        
        // 应用处理选项
        $processedImage = $this->applyProcessing($sourceImage, $imageInfo, $options);
        
        // 保存图片
        $result = $this->saveImage($processedImage, $outputPath, $imageInfo[2]);
        
        // 清理资源
        imagedestroy($sourceImage);
        if ($processedImage !== $sourceImage) {
            imagedestroy($processedImage);
        }
        
        if ($result) {
            return [
                'success' => true,
                'path' => $outputPath,
                'size' => filesize($outputPath),
                'dimensions' => getimagesize($outputPath)
            ];
        } else {
            return ['success' => false, 'message' => '图片保存失败'];
        }
    }
    
    /**
     * 验证文件
     */
    private function validateFile($file) {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($mimeType, $allowedMimes);
    }
    
    /**
     * 从文件创建图片资源
     */
    private function createImageFromFile($filePath, $imageType) {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }
    
    /**
     * 应用图片处理
     */
    private function applyProcessing($sourceImage, $imageInfo, $options) {
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // 计算新尺寸
        $newDimensions = $this->calculateNewDimensions($width, $height, $options);
        
        // 如果尺寸没有变化且没有其他处理，直接返回原图
        if ($newDimensions['width'] == $width && 
            $newDimensions['height'] == $height && 
            empty($options['crop']) && 
            empty($options['watermark'])) {
            return $sourceImage;
        }
        
        // 创建新图片
        $newImage = imagecreatetruecolor($newDimensions['width'], $newDimensions['height']);
        
        // 保持透明度
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newDimensions['width'], $newDimensions['height'], $transparent);
        }
        
        // 调整大小
        imagecopyresampled(
            $newImage, $sourceImage,
            0, 0, 0, 0,
            $newDimensions['width'], $newDimensions['height'],
            $width, $height
        );
        
        // 应用裁剪
        if (!empty($options['crop'])) {
            $newImage = $this->applyCrop($newImage, $options['crop']);
        }
        
        // 应用水印
        if (!empty($options['watermark'])) {
            $this->applyWatermark($newImage, $options['watermark']);
        }
        
        return $newImage;
    }
    
    /**
     * 计算新尺寸
     */
    private function calculateNewDimensions($width, $height, $options) {
        $maxWidth = $options['maxWidth'] ?? $this->maxWidth;
        $maxHeight = $options['maxHeight'] ?? $this->maxHeight;
        
        // 如果指定了固定尺寸
        if (!empty($options['fixedWidth']) && !empty($options['fixedHeight'])) {
            return [
                'width' => $options['fixedWidth'],
                'height' => $options['fixedHeight']
            ];
        }
        
        // 按比例缩放
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return ['width' => $width, 'height' => $height];
        }
        
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        
        return [
            'width' => round($width * $ratio),
            'height' => round($height * $ratio)
        ];
    }
    
    /**
     * 应用裁剪
     */
    private function applyCrop($image, $cropOptions) {
        $width = imagesx($image);
        $height = imagesy($image);
        
        $cropX = $cropOptions['x'] ?? 0;
        $cropY = $cropOptions['y'] ?? 0;
        $cropWidth = $cropOptions['width'] ?? $width;
        $cropHeight = $cropOptions['height'] ?? $height;
        
        // 确保裁剪区域在图片范围内
        $cropX = max(0, min($cropX, $width - 1));
        $cropY = max(0, min($cropY, $height - 1));
        $cropWidth = min($cropWidth, $width - $cropX);
        $cropHeight = min($cropHeight, $height - $cropY);
        
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
        
        // 保持透明度
        imagealphablending($croppedImage, false);
        imagesavealpha($croppedImage, true);
        
        imagecopy($croppedImage, $image, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight);
        
        imagedestroy($image);
        return $croppedImage;
    }
    
    /**
     * 应用水印
     */
    private function applyWatermark($image, $watermarkOptions) {
        $watermarkPath = $watermarkOptions['path'] ?? '';
        if (!file_exists($watermarkPath)) {
            return;
        }
        
        $watermarkInfo = getimagesize($watermarkPath);
        if (!$watermarkInfo) {
            return;
        }
        
        $watermark = $this->createImageFromFile($watermarkPath, $watermarkInfo[2]);
        if (!$watermark) {
            return;
        }
        
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);
        
        // 计算水印位置
        $position = $watermarkOptions['position'] ?? 'bottom-right';
        $margin = $watermarkOptions['margin'] ?? 10;
        
        switch ($position) {
            case 'top-left':
                $x = $margin;
                $y = $margin;
                break;
            case 'top-right':
                $x = $imageWidth - $watermarkWidth - $margin;
                $y = $margin;
                break;
            case 'bottom-left':
                $x = $margin;
                $y = $imageHeight - $watermarkHeight - $margin;
                break;
            case 'bottom-right':
            default:
                $x = $imageWidth - $watermarkWidth - $margin;
                $y = $imageHeight - $watermarkHeight - $margin;
                break;
            case 'center':
                $x = ($imageWidth - $watermarkWidth) / 2;
                $y = ($imageHeight - $watermarkHeight) / 2;
                break;
        }
        
        // 应用透明度
        $opacity = $watermarkOptions['opacity'] ?? 50;
        imagecopymerge($image, $watermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight, $opacity);
        
        imagedestroy($watermark);
    }
    
    /**
     * 保存图片
     */
    private function saveImage($image, $outputPath, $originalType) {
        // 确保输出目录存在
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // 根据原始类型保存
        switch ($originalType) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $outputPath, $this->quality);
            case IMAGETYPE_PNG:
                // PNG质量范围是0-9，需要转换
                $pngQuality = round((100 - $this->quality) / 10);
                return imagepng($image, $outputPath, $pngQuality);
            case IMAGETYPE_GIF:
                return imagegif($image, $outputPath);
            case IMAGETYPE_WEBP:
                return imagewebp($image, $outputPath, $this->quality);
            default:
                // 默认保存为JPEG
                return imagejpeg($image, $outputPath, $this->quality);
        }
    }
    
    /**
     * 生成缩略图
     */
    public function generateThumbnail($sourcePath, $thumbnailPath, $width = 150, $height = 150) {
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceImage = $this->createImageFromFile($sourcePath, $imageInfo[2]);
        if (!$sourceImage) {
            return false;
        }
        
        $thumbnail = imagecreatetruecolor($width, $height);
        
        // 保持透明度
        if ($imageInfo[2] == IMAGETYPE_PNG || $imageInfo[2] == IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $width, $height, $transparent);
        }
        
        imagecopyresampled(
            $thumbnail, $sourceImage,
            0, 0, 0, 0,
            $width, $height,
            $imageInfo[0], $imageInfo[1]
        );
        
        $result = $this->saveImage($thumbnail, $thumbnailPath, $imageInfo[2]);
        
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        
        return $result;
    }
    
    /**
     * 获取图片信息
     */
    public function getImageInfo($imagePath) {
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
            'ratio' => $imageInfo[0] / $imageInfo[1],
            'orientation' => $imageInfo[0] > $imageInfo[1] ? 'landscape' : ($imageInfo[0] < $imageInfo[1] ? 'portrait' : 'square')
        ];
    }
}

/**
 * 便捷函数：处理头像上传
 */
function processAvatarUpload($file, $outputPath = null) {
    if (!$outputPath) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'avatar_' . time() . '_' . uniqid() . '.jpg'; // 统一转换为JPG
        $outputPath = 'admin/img/' . $fileName;
    }
    
    $processor = new ImageProcessor();
    return $processor
        ->setQuality(90)
        ->setMaxSize(500, 500)
        ->processUpload($file, $outputPath, [
            'fixedWidth' => 200,
            'fixedHeight' => 200
        ]);
}

/**
 * 便捷函数：处理一般图片上传
 */
function processGeneralImageUpload($file, $outputPath = null, $maxSize = 1200) {
    if (!$outputPath) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'image_' . time() . '_' . uniqid() . '.' . $extension;
        $outputPath = 'admin/img/' . $fileName;
    }
    
    $processor = new ImageProcessor();
    return $processor
        ->setQuality(85)
        ->setMaxSize($maxSize, $maxSize)
        ->processUpload($file, $outputPath);
}
?>