<?php
/**
 * 系统设置页面
 */

// 获取系统设置
$settings = [];
$settingsData = fetchAll("SELECT * FROM settings ORDER BY group_name, key_name");
foreach ($settingsData as $setting) {
    $settings[$setting['key_name']] = json_decode($setting['value'], true);
}

// 获取当前活动的标签
$activeTab = $_GET['tab'] ?? 'basic';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 获取提交时的活动标签
        $activeTab = $_POST['active_tab'] ?? 'basic';
        
        $updates = [
            'site_title' => trim($_POST['site_title'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'site_keywords' => trim($_POST['site_keywords'] ?? ''),
            'animations_enabled' => isset($_POST['animations_enabled']),
            'particles_enabled' => isset($_POST['particles_enabled']),
            'particle_count' => (int)($_POST['particle_count'] ?? 80),
            'effects_quality' => $_POST['effects_quality'] ?? 'high',
            'stats_enabled' => isset($_POST['stats_enabled']),
            'click_tracking' => isset($_POST['click_tracking']),
            'auto_backup' => isset($_POST['auto_backup']),
            'backup_interval' => (int)($_POST['backup_interval'] ?? 7)
        ];
        
        foreach ($updates as $key => $value) {
            $jsonValue = json_encode($value);
            
            // 检查设置是否存在
            $exists = fetchOne("SELECT id FROM settings WHERE key_name = ?", [$key]);
            
            if ($exists) {
                // 更新
                update('settings', ['value' => $jsonValue], 'key_name = ?', [$key]);
            } else {
                // 插入
                insert('settings', [
                    'key_name' => $key,
                    'value' => $jsonValue,
                    'description' => '',
                    'group_name' => 'general'
                ]);
            }
        }
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => '系统设置保存成功'
        ];
        
        // 重定向时保留活动标签
        header('Location: ?page=settings&tab=' . $activeTab);
        exit;
    } catch (Exception $e) {
        $error = '保存失败: ' . $e->getMessage();
    }
}
?>

<div class="page-content settings-page">
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <span class="alert-icon">❌</span>
            <span class="alert-message"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>
    
    <div class="settings-header">
        <div class="settings-title">
            <h2>系统设置</h2>
            <p>配置您的网站参数和系统选项</p>
        </div>
        <div class="settings-tabs">
            <button type="button" class="tab-btn <?= $activeTab === 'basic' ? 'active' : '' ?>" data-tab="basic">基本设置</button>
            <button type="button" class="tab-btn <?= $activeTab === 'display' ? 'active' : '' ?>" data-tab="display">显示设置</button>
            <button type="button" class="tab-btn <?= $activeTab === 'function' ? 'active' : '' ?>" data-tab="function">功能设置</button>
            <button type="button" class="tab-btn <?= $activeTab === 'system' ? 'active' : '' ?>" data-tab="system">系统设置</button>
        </div>
    </div>
    
    <form method="post" class="settings-form">
        <!-- 隐藏字段，用于传递当前活动的标签 -->
        <input type="hidden" name="active_tab" id="active_tab" value="<?= htmlspecialchars($activeTab) ?>">
        
        <!-- 基本设置 -->
        <div class="settings-section <?= $activeTab === 'basic' ? 'active' : '' ?>" id="basic-section">
            <div class="settings-card">
                <div class="card-header">
                    <div class="header-icon">🌐</div>
                    <div class="header-text">
                        <h3>基本设置</h3>
                        <p>网站的基本信息配置</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="form-group">
                        <label for="site_title">网站标题</label>
                        <input type="text" id="site_title" name="site_title" 
                               value="<?= htmlspecialchars($settings['site_title'] ?? 'HomePage | 个人导航') ?>" 
                               placeholder="网站标题">
                        <small class="form-help">显示在浏览器标题栏和搜索结果中</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">网站描述</label>
                        <textarea id="site_description" name="site_description" rows="3" 
                                  placeholder="网站描述"><?= htmlspecialchars($settings['site_description'] ?? '一个具有未来科技感设计的个人导航页面') ?></textarea>
                        <small class="form-help">用于SEO优化和社交媒体分享</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_keywords">网站关键词</label>
                        <input type="text" id="site_keywords" name="site_keywords" 
                               value="<?= htmlspecialchars($settings['site_keywords'] ?? '个人导航,科技感,导航页,未来主义,响应式设计') ?>" 
                               placeholder="用逗号分隔关键词">
                        <small class="form-help">用于搜索引擎优化，多个关键词用逗号分隔</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 显示设置 -->
        <div class="settings-section <?= $activeTab === 'display' ? 'active' : '' ?>" id="display-section">
            <div class="settings-card">
                <div class="card-header">
                    <div class="header-icon">🎨</div>
                    <div class="header-text">
                        <h3>显示设置</h3>
                        <p>控制网站的视觉效果和动画</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="settings-grid">
                        <div class="form-group switch-group">
                            <div class="switch-label">
                                <span>启用动画效果</span>
                                <small class="form-help">控制页面加载和交互动画</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="animations_enabled" 
                                       <?= ($settings['animations_enabled'] ?? true) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="form-group switch-group">
                            <div class="switch-label">
                                <span>启用粒子效果</span>
                                <small class="form-help">背景粒子动画效果</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="particles_enabled" 
                                       <?= ($settings['particles_enabled'] ?? true) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="settings-grid">
                        <div class="form-group">
                            <label for="particle_count">粒子数量</label>
                            <div class="range-input-group">
                                <input type="range" id="particle_count" name="particle_count" 
                                       value="<?= $settings['particle_count'] ?? 80 ?>" 
                                       min="0" max="200" oninput="this.nextElementSibling.value = this.value">
                                <output><?= $settings['particle_count'] ?? 80 ?></output>
                            </div>
                            <small class="form-help">粒子数量越多效果越好，但可能影响性能</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="effects_quality">特效质量</label>
                            <div class="select-wrapper">
                                <select id="effects_quality" name="effects_quality">
                                    <option value="low" <?= ($settings['effects_quality'] ?? 'high') === 'low' ? 'selected' : '' ?>>低</option>
                                    <option value="medium" <?= ($settings['effects_quality'] ?? 'high') === 'medium' ? 'selected' : '' ?>>中</option>
                                    <option value="high" <?= ($settings['effects_quality'] ?? 'high') === 'high' ? 'selected' : '' ?>>高</option>
                                </select>
                                <div class="select-arrow">▼</div>
                            </div>
                            <small class="form-help">影响模糊、发光等视觉效果的质量</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 功能设置 -->
        <div class="settings-section <?= $activeTab === 'function' ? 'active' : '' ?>" id="function-section">
            <div class="settings-card">
                <div class="card-header">
                    <div class="header-icon">🔧</div>
                    <div class="header-text">
                        <h3>功能设置</h3>
                        <p>控制网站的功能模块</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="settings-grid">
                        <div class="form-group switch-group">
                            <div class="switch-label">
                                <span>启用访问统计</span>
                                <small class="form-help">记录页面访问量和用户行为</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="stats_enabled" 
                                       <?= ($settings['stats_enabled'] ?? true) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="form-group switch-group">
                            <div class="switch-label">
                                <span>启用点击统计</span>
                                <small class="form-help">统计导航项目的点击次数</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="click_tracking" 
                                       <?= ($settings['click_tracking'] ?? true) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 系统设置 -->
        <div class="settings-section <?= $activeTab === 'system' ? 'active' : '' ?>" id="system-section">
            <div class="settings-card">
                <div class="card-header">
                    <div class="header-icon">🛠️</div>
                    <div class="header-text">
                        <h3>系统设置</h3>
                        <p>系统维护和备份相关设置</p>
                    </div>
                </div>
                <div class="card-content">
                    <div class="settings-grid">
                        <div class="form-group switch-group">
                            <div class="switch-label">
                                <span>启用自动备份</span>
                                <small class="form-help">定期自动备份数据库</small>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="auto_backup" 
                                       <?= ($settings['auto_backup'] ?? false) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="backup_interval">备份间隔（天）</label>
                            <div class="range-input-group">
                                <input type="range" id="backup_interval" name="backup_interval" 
                                       value="<?= $settings['backup_interval'] ?? 7 ?>" 
                                       min="1" max="30" oninput="this.nextElementSibling.value = this.value">
                                <output><?= $settings['backup_interval'] ?? 7 ?></output>
                            </div>
                            <small class="form-help">自动备份的时间间隔</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <span class="btn-icon">💾</span>
                保存设置
            </button>
            <button type="reset" class="btn btn-secondary">
                <span class="btn-icon">↩️</span>
                重置
            </button>
        </div>
    </form>
</div>

<style>
/* 设置页面样式 */
.settings-page {
    padding: 0;
    margin: 0;
    max-width: 100%;
}

.settings-header {
    margin-bottom: 30px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.settings-title {
    margin-bottom: 10px;
}

.settings-title h2 {
    font-size: 28px;
    margin: 0 0 8px 0;
    color: #333;
    font-weight: 700;
}

.settings-title p {
    color: #666;
    margin: 0;
    font-size: 16px;
}

.settings-tabs {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
    overflow-x: auto;
    scrollbar-width: thin;
}

.settings-tabs::-webkit-scrollbar {
    height: 4px;
}

.settings-tabs::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
}

.tab-btn {
    padding: 10px 20px;
    border: none;
    background: none;
    font-size: 16px;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.tab-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #333;
}

.tab-btn.active {
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(0, 210, 211, 0.3);
}

.settings-form {
    width: 100%;
    max-width: 100%;
}

.settings-section {
    display: none;
    animation: fadeIn 0.5s ease;
}

.settings-section.active {
    display: block;
}

.settings-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-bottom: 30px;
    transition: all 0.3s ease;
}

.settings-card:hover {
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.settings-card .card-header {
    padding: 25px 30px;
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
    color: white;
    display: flex;
    align-items: center;
    gap: 20px;
}

.settings-card .header-icon {
    font-size: 32px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.settings-card .header-text h3 {
    margin: 0 0 5px 0;
    font-size: 22px;
    font-weight: 600;
}

.settings-card .header-text p {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
}

.settings-card .card-content {
    padding: 30px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #333;
    font-size: 16px;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-group input[type="text"]:focus,
.form-group input[type="number"]:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #00d2d3;
    background: white;
    box-shadow: 0 0 0 3px rgba(0, 210, 211, 0.1);
}

.form-help {
    display: block;
    margin-top: 8px;
    color: #666;
    font-size: 13px;
    line-height: 1.4;
}

/* 开关样式 */
.switch-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.switch-label {
    flex: 1;
}

.switch-label span {
    display: block;
    font-weight: 600;
    color: #333;
    font-size: 16px;
    margin-bottom: 5px;
}

.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
}

input:focus + .slider {
    box-shadow: 0 0 1px #00d2d3;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

/* 范围输入样式 */
.range-input-group {
    display: flex;
    align-items: center;
    gap: 15px;
}

.range-input-group input[type="range"] {
    flex: 1;
    -webkit-appearance: none;
    height: 8px;
    border-radius: 4px;
    background: #e9ecef;
    outline: none;
}

.range-input-group input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.range-input-group output {
    width: 40px;
    text-align: center;
    font-weight: 600;
    color: #333;
    background: #f8f9fa;
    padding: 5px 10px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

/* 下拉框样式 */
.select-wrapper {
    position: relative;
}

.select-wrapper select {
    appearance: none;
    padding-right: 40px;
}

.select-arrow {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #666;
    font-size: 12px;
}

/* 按钮样式 */
.form-actions {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
    display: flex;
    gap: 15px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 30px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(0, 210, 211, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 210, 211, 0.4);
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

/* 动画 */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* 响应式设计 */
@media (max-width: 768px) {
    .settings-header {
        flex-direction: column;
    }
    
    .settings-tabs {
        justify-content: flex-start;
    }
    
    .settings-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .settings-card .card-header {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .settings-card .card-content {
        padding: 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 标签切换功能
    const tabBtns = document.querySelectorAll('.tab-btn');
    const sections = document.querySelectorAll('.settings-section');
    const activeTabInput = document.getElementById('active_tab');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // 移除所有标签的活动状态
            tabBtns.forEach(b => b.classList.remove('active'));
            // 隐藏所有内容区域
            sections.forEach(section => section.classList.remove('active'));
            
            // 激活当前标签
            this.classList.add('active');
            
            // 获取当前标签ID
            const tabId = this.getAttribute('data-tab');
            
            // 显示对应的内容区域
            document.getElementById(tabId + '-section').classList.add('active');
            
            // 更新隐藏字段的值
            activeTabInput.value = tabId;
            
            // 更新URL，但不刷新页面
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        });
    });
});
</script>
