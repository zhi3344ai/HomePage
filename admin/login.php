<?php
/**
 * 独立登录页面
 */

// PHP 8.1 兼容性配置
require_once __DIR__ . '/includes/php81-compat.php';

// 启动会话
session_start();

// 引入配置文件
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';

// 检查是否已登录
if (isLoggedIn()) {
    header('Location: index.php?page=dashboard');
    exit;
}

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        $result = login($username, $password);
        
        if ($result['success']) {
            // 记住登录状态
            if ($remember) {
                setcookie('remember_token', generateRandomString(), time() + 86400 * 30, '/');
            }
            
            header('Location: index.php?page=dashboard');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - HomePage 管理后台</title>
    <link rel="icon" type="image/svg+xml" href="../assets/icons/favicon.svg">
    <style>
        /* 重置样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* 登录页面样式 */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-logo h1 {
            font-size: 48px;
            margin: 0 0 10px 0;
            animation: bounce 2s infinite;
        }

        .login-logo h2 {
            font-size: 24px;
            margin: 0 0 10px 0;
            font-weight: 600;
        }

        .login-logo p {
            opacity: 0.9;
            margin: 0;
            font-size: 14px;
        }

        .login-form {
            padding: 40px 30px;
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

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            z-index: 2;
            font-size: 16px;
            color: #666;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            background: white;
        }

        .input-group input:focus {
            outline: none;
            border-color: #00f5ff;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 245, 255, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            z-index: 2;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            color: #666;
        }

        .checkbox-label input[type="checkbox"] {
            display: none;
        }

        .checkbox-custom {
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-right: 8px;
            position: relative;
            transition: all 0.3s ease;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
            background: #00f5ff;
            border-color: #00f5ff;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .forgot-link {
            color: #00f5ff;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #ff0080;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00f5ff 0%, #ff0080 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            pointer-events: auto;
            z-index: 1002;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 245, 255, 0.3);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .login-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
        }

        .system-info {
            font-size: 12px;
            color: #666;
        }

        .system-info a {
            color: #00f5ff;
            text-decoration: none;
        }

        .separator {
            margin: 0 10px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        /* 背景动画 */
        .login-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
            pointer-events: none;
        }

        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .bg-circle-1 {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .bg-circle-2 {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .bg-circle-3 {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 60%;
            animation-delay: 4s;
        }

        /* 动画 */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* 响应式 */
        @media (max-width: 480px) {
            .login-container {
                padding: 10px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-form {
                padding: 30px 20px;
            }
            
            .login-footer {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <h1>🚀</h1>
                    <h2>HomePage 管理</h2>
                    <p>欢迎回来，请登录您的账号</p>
                </div>
            </div>
            
            <div class="login-form">
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">❌</span>
                        <span class="alert-message"><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="post" id="loginForm">
                    <div class="form-group">
                        <label for="username">用户名或邮箱</label>
                        <div class="input-group">
                            <span class="input-icon">👤</span>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                value="<?= htmlspecialchars($username ?? '') ?>"
                                placeholder="请输入用户名或邮箱"
                                required
                                autocomplete="username"
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">密码</label>
                        <div class="input-group">
                            <span class="input-icon">🔒</span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="请输入密码"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <span id="passwordToggleIcon">👁️</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" id="remember">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">记住我</span>
                        </label>
                        
                        <a href="#" class="forgot-link">忘记密码？</a>
                    </div>
                    
                    <button type="submit" class="login-btn" id="loginBtn">
                        <span class="btn-text">登录</span>
                        <span class="btn-loading" style="display: none;">
                            <span class="spinner"></span>
                            登录中...
                        </span>
                    </button>
                </form>
            </div>
            
            <div class="login-footer">
                <div class="system-info">
                    <span>HomePage 管理系统 v1.0.0</span>
                    <span class="separator">|</span>
                    <a href="../index.html" target="_blank">访问前台</a>
                </div>
            </div>
        </div>
        
        <!-- 背景动画 -->
        <div class="login-background">
            <div class="bg-circle bg-circle-1"></div>
            <div class="bg-circle bg-circle-2"></div>
            <div class="bg-circle bg-circle-3"></div>
        </div>
    </div>

    <script>
        // 密码显示/隐藏切换
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // 表单提交处理
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            
            btn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'flex';
        });

        // 回车键登录
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });

        console.log('独立登录页面已加载');
    </script>
</body>
</html>