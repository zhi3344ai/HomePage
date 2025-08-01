<?php
/**
 * 专用图标上传处理文件
 */

// 开启输出缓冲
ob_start();

// 设置错误报告级别
error_reporting(E_ALL);
ini_set('display_errors', 0); // 不显示错误到输出

// 清理输出缓冲区
ob_clean();

// 设置响应头
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit;
}

// 检查是否是上传请求
if (!isset($_POST['action']) || $_POST['action'] !== 'upload_icon') {
    echo json_encode(['success' => false, 'message' => '无效的操作']);
    exit;
}

// 检查文件是否上传
if (!isset($_FILES['icon_file'])) {
    echo json_encode(['success' => false, 'message' => '没有接收到文件']);
    exit;
}

try {
    $file = $_FILES['icon_file'];
    
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
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!in_array($extension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => '不支持的文件格式: ' . $extension]);
        exit;
    }
    
    // 验证文件大小 (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => '文件大小不能超过2MB']);
        exit;
    }
    
    // 生成文件名
    $fileName = 'icon_' . time() . '_' . uniqid() . '.' . $extension;
    $uploadPath = 'img/' . $fileName;
    
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
    $fullUploadPath = __DIR__ . '/' . $uploadPath;
    
    // 调试信息
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $debugInfo = [
        'current_dir' => __DIR__,
        'upload_path' => $uploadPath,
        'full_upload_path' => $fullUploadPath,
        'tmp_name' => $file['tmp_name'],
        'tmp_exists' => file_exists($file['tmp_name']),
        'target_dir_exists' => is_dir(dirname($fullUploadPath)),
        'target_dir_writable' => is_writable(dirname($fullUploadPath)),
        'referer' => $referer,
        'is_admin_pages' => strpos($referer, '/admin/pages/') !== false
    ];
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // 验证文件是否真的保存了
        $fileExists = file_exists($uploadPath);
        $fileSize = $fileExists ? filesize($uploadPath) : 0;
        
        // 根据请求来源返回正确的路径
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        if (strpos($referer, '/admin/pages/') !== false) {
            // 从admin/pages/目录访问，使用相对路径
            $relativePath = '../' . $uploadPath;
        } else {
            // 从项目根目录访问，使用admin/路径
            $relativePath = 'admin/' . $uploadPath;
        }
        echo json_encode([
            'success' => true, 
            'path' => $relativePath, 
            'fileName' => $fileName,
            'size' => $file['size'],
            'originalName' => $file['name'],
            'debug' => $debugInfo,
            'file_exists' => $fileExists,
            'saved_size' => $fileSize,
            'final_path' => $relativePath,
            'server_path' => $uploadPath
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => '文件保存失败，请检查目录权限',
            'debug' => $debugInfo,
            'error' => error_get_last()
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '上传过程中发生错误: ' . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => '系统错误: ' . $e->getMessage()]);
}

// 结束输出缓冲
ob_end_flush();
?>