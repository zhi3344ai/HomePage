<?php
/**
 * 主题设置页面
 */

// 获取主题列表
$themes = fetchAll("SELECT * FROM themes ORDER BY is_default DESC, id ASC");

// 处理设置默认主题
if (isset($_GET['action']) && $_GET['action'] === 'set_default' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // 取消所有主题的默认状态
        update('themes', ['is_default' => 0], '1=1');
        
        // 设置新的默认主题
        update('themes', ['is_default' => 1], 'id = ?', [$id]);
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => '默认主题设置成功'
        ];
    } catch (Exception $e) {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => '设置失败: ' . $e->getMessage()
        ];
    }
    
    header('Location: ?page=themes');
    exit;
}

// 处理启用/禁用主题
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $theme = fetchOne("SELECT * FROM themes WHERE id = ?", [$id]);
    
    if ($theme) {
        $newStatus = $theme['is_active'] ? 0 : 1;
        update('themes', ['is_active' => $newStatus], 'id = ?', [$id]);
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => '主题状态更新成功'
        ];
    }
    
    header('Location: ?page=themes');
    exit;
}
?>

<div class="page-content" style="padding-top: 0; margin-top: 0;">
    <div class="card">
        <div class="card-header">
            <h3>可用主题</h3>
            <p>选择一个主题作为网站的默认外观</p>
        </div>
        <div class="card-content">
            <?php if (empty($themes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎨</div>
                    <h3>暂无主题</h3>
                    <p>系统中还没有可用的主题</p>
                </div>
            <?php else: ?>
                <div class="themes-grid">
                    <?php foreach ($themes as $theme): ?>
                        <?php 
                        $colors = json_decode($theme['colors'], true);
                        $effects = json_decode($theme['effects'], true);
                        ?>
                        <div class="theme-card <?= $theme['is_default'] ? 'theme-default' : '' ?> <?= !$theme['is_active'] ? 'theme-disabled' : '' ?>" data-theme="<?= htmlspecialchars($theme['name']) ?>">
                            <?php
                            // 根据主题名称设置不同的预览样式
                            $previewStyle = '';
                            $previewContent = '';
                            
                            switch($theme['name']) {
                                case 'cyberpunk':
                                    $previewStyle = "background: linear-gradient(135deg, {$colors['primary']} 0%, {$colors['secondary']} 100%);";
                                    $previewContent = '<div class="preview-neon-line"></div>';
                                    break;
                                case 'aurora':
                                    $previewStyle = "background: linear-gradient(135deg, {$colors['primary']} 0%, {$colors['secondary']} 100%); backdrop-filter: blur(5px);";
                                    $previewContent = '<div class="preview-aurora-waves"></div>';
                                    break;
                                case 'synthwave':
                                    $previewStyle = "background: linear-gradient(180deg, {$colors['background']} 0%, {$colors['primary']} 100%);";
                                    $previewContent = '<div class="preview-grid"></div>';
                                    break;
                                case 'matrix':
                                    $previewStyle = "background: {$colors['background']}; border: 1px dashed {$colors['primary']};";
                                    $previewContent = '<div class="preview-code">01</div>';
                                    break;
                                case 'neon':
                                    $previewStyle = "background: {$colors['background']}; border: 2px double {$colors['primary']};";
                                    $previewContent = '<div class="preview-neon-glow"></div>';
                                    break;
                                default:
                                    $previewStyle = "background: linear-gradient(135deg, {$colors['primary']} 0%, {$colors['secondary']} 100%);";
                            }
                            ?>
                            <div class="theme-preview" style="<?= $previewStyle ?>">
                                <?= $previewContent ?>
                                <div class="theme-preview-content">
                                    <div class="preview-header" style="background: <?= htmlspecialchars($colors['background'] ?? '#0a0a0a') ?>; border-style: <?= $theme['name'] == 'matrix' ? 'dashed' : ($theme['name'] == 'neon' ? 'double' : 'solid') ?>; border-color: <?= htmlspecialchars($colors['primary'] ?? '#00f5ff') ?>;">
                                        <div class="preview-title" style="color: <?= htmlspecialchars($colors['text'] ?? '#ffffff') ?>; font-family: <?= $theme['name'] == 'matrix' ? '\'Courier New\', monospace' : ($theme['name'] == 'synthwave' ? '\'VT323\', monospace' : 'inherit') ?>;">
                                            <?= htmlspecialchars($theme['display_name']) ?>
                                        </div>
                                    </div>
                                    <div class="preview-body" style="background: <?= $theme['name'] == 'matrix' ? 'rgba(0, 255, 65, 0.05)' : 'transparent' ?>;">
                                        <div class="preview-button" style="background: <?= htmlspecialchars($colors['primary'] ?? '#00f5ff') ?>; color: <?= htmlspecialchars($colors['text'] ?? '#ffffff') ?>; border-radius: <?= $theme['name'] == 'synthwave' ? '0' : '4px' ?>; box-shadow: <?= $theme['name'] == 'neon' ? '0 0 10px ' . $colors['primary'] : 'none' ?>;">
                                            按钮
                                        </div>
                                        <div class="preview-accent" style="background: <?= htmlspecialchars($colors['accent'] ?? '#00ff41') ?>; border-radius: <?= $theme['name'] == 'synthwave' ? '0' : '50%' ?>;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="theme-info">
                                <div class="theme-header">
                                    <h4 class="theme-name">
                                        <?= htmlspecialchars($theme['display_name']) ?>
                                        <?php if ($theme['is_default']): ?>
                                            <span class="default-badge">默认</span>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="theme-status">
                                        <span class="status-badge <?= $theme['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                            <?= $theme['is_active'] ? '启用' : '禁用' ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="theme-description">
                                    <?php
                                    // 根据主题名称设置不同的描述
                                    switch($theme['name']) {
                                        case 'cyberpunk':
                                            echo '<p>霓虹灯效果，高对比度的青色和品红色。</p>';
                                            break;
                                        case 'aurora':
                                            echo '<p>流动渐变效果，柔和、梦幻般的蓝色和青色调。</p>';
                                            break;
                                        case 'synthwave':
                                            echo '<p>复古网格，80年代风格，紫色和橙色的强烈对比。</p>';
                                            break;
                                        case 'matrix':
                                            echo '<p>数字雨效果，终端风格，黑客美学，绿色代码。</p>';
                                            break;
                                        case 'neon':
                                            echo '<p>城市夜景风格，红色和绿色的霓虹灯效果。</p>';
                                            break;
                                        default:
                                            echo '<p>自定义主题风格。</p>';
                                    }
                                    ?>
                                </div>
                                
                                <div class="theme-colors">
                                    <div class="color-palette">
                                        <div class="color-item" style="background: <?= htmlspecialchars($colors['primary'] ?? '#00f5ff') ?>" title="主色"></div>
                                        <div class="color-item" style="background: <?= htmlspecialchars($colors['secondary'] ?? '#ff0080') ?>" title="辅色"></div>
                                        <div class="color-item" style="background: <?= htmlspecialchars($colors['accent'] ?? '#00ff41') ?>" title="强调色"></div>
                                        <div class="color-item" style="background: <?= htmlspecialchars($colors['background'] ?? '#0a0a0a') ?>" title="背景色"></div>
                                    </div>
                                </div>
                                
                                <div class="theme-actions">
                                    <?php if (!$theme['is_default'] && $theme['is_active']): ?>
                                        <a href="?page=themes&action=set_default&id=<?= $theme['id'] ?>" 
                                           class="btn btn-sm btn-primary">设为默认</a>
                                    <?php endif; ?>
                                    
                                    <a href="?page=themes&action=toggle&id=<?= $theme['id'] ?>" 
                                       class="btn btn-sm <?= $theme['is_active'] ? 'btn-secondary' : 'btn-success' ?>">
                                        <?= $theme['is_active'] ? '禁用' : '启用' ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 主题配置说明 -->
    <div class="card">
        <div class="card-header">
            <h3>主题说明</h3>
        </div>
        <div class="card-content">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">🎨</div>
                    <div class="info-content">
                        <h4>主题切换</h4>
                        <p>用户可以在前台页面右上角点击主题切换按钮，选择不同的主题。默认主题将作为首次访问时的主题。</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">🌈</div>
                    <div class="info-content">
                        <h4>颜色配置</h4>
                        <p>每个主题包含主色、辅色、强调色和背景色，用于网站的整体配色方案。</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">✨</div>
                    <div class="info-content">
                        <h4>视觉效果</h4>
                        <p>每个主题都有独特的视觉效果：赛博朋克的霓虹灯效果、极光的流动渐变、合成波的复古网格、矩阵的数字雨和霓虹的城市夜景。</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">📱</div>
                    <div class="info-content">
                        <h4>响应式设计</h4>
                        <p>所有主题都支持响应式设计，在不同设备上都有良好的显示效果。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.theme-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.theme-card:hover {
    border-color: #00f5ff;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 245, 255, 0.1);
}

.theme-default {
    border-color: #00f5ff;
    box-shadow: 0 0 0 2px rgba(0, 245, 255, 0.2);
}

.theme-disabled {
    opacity: 0.6;
}

.theme-preview {
    height: 150px;
    position: relative;
    overflow: hidden;
}

/* 主题特定预览效果 */
.preview-neon-line {
    position: absolute;
    height: 2px;
    width: 80%;
    background: linear-gradient(90deg, transparent, #00f5ff, transparent);
    top: 50%;
    left: 10%;
    box-shadow: 0 0 10px #00f5ff;
    animation: neonPulse 2s infinite;
}

.preview-aurora-waves {
    position: absolute;
    height: 100%;
    width: 100%;
    background: linear-gradient(45deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
    animation: auroraWave 3s infinite;
}

.preview-grid {
    position: absolute;
    height: 100%;
    width: 100%;
    background-image: linear-gradient(0deg, rgba(255, 0, 255, 0.1) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(255, 0, 255, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
    animation: gridMove 10s linear infinite;
}

.preview-code {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #00ff41;
    font-family: 'Courier New', monospace;
    font-size: 24px;
    text-shadow: 0 0 10px #00ff41;
    animation: blink 1.5s infinite;
}

.preview-neon-glow {
    position: absolute;
    width: 60%;
    height: 2px;
    background: #39ff14;
    top: 50%;
    left: 20%;
    box-shadow: 0 0 20px #39ff14;
    animation: neonGlow 2s infinite;
}

@keyframes neonPulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
}

@keyframes auroraWave {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.5; }
    50% { transform: translateY(-10px) scale(1.05); opacity: 0.8; }
}

@keyframes gridMove {
    0% { background-position: 0 0; }
    100% { background-position: 20px 20px; }
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

@keyframes neonGlow {
    0%, 100% { box-shadow: 0 0 10px #39ff14; }
    50% { box-shadow: 0 0 30px #39ff14; }
}

.theme-preview-content {
    position: absolute;
    top: 10px;
    left: 10px;
    right: 10px;
    bottom: 10px;
    border-radius: 8px;
    overflow: hidden;
}

.preview-header {
    height: 30px;
    display: flex;
    align-items: center;
    padding: 0 10px;
    border-width: 1px;
    border-bottom-style: solid;
}

.preview-title {
    font-size: 12px;
    font-weight: bold;
}

.preview-body {
    padding: 10px;
    display: flex;
    gap: 8px;
    align-items: center;
    height: calc(100% - 30px);
}

.preview-button {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: bold;
}

.preview-accent {
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.theme-info {
    padding: 15px;
}

.theme-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.theme-name {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.default-badge {
    background: #00f5ff;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: bold;
}

.theme-description {
    margin-bottom: 10px;
}

.theme-description p {
    margin: 0;
    font-size: 14px;
    color: #666;
}

.theme-features {
    margin-bottom: 10px;
}

.feature-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.feature-tag {
    background: #f0f0f0;
    color: #333;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.theme-colors {
    margin-bottom: 15px;
}

.color-palette {
    display: flex;
    gap: 5px;
}

.color-item {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid rgba(0,0,0,0.1);
}

.theme-actions {
    display: flex;
    gap: 8px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.info-content h4 {
    margin: 0 0 8px 0;
    color: #333;
}

.info-content p {
    margin: 0;
    color: #666;
    font-size: 14px;
    line-height: 1.5;
}
</style>
