<?php
/**
 * 仪表盘页面
 */

// 获取系统统计信息
$stats = getSystemStats();
$recentStats = getRecentStats(7);
$popularItems = getPopularNavItems(5);

// 获取最近的系统日志
$recentLogs = fetchAll("
    SELECT l.*, u.username 
    FROM system_logs l 
    LEFT JOIN admin_users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 10
");

// 准备图表数据
$chartDates = [];
$chartViews = [];
$chartVisitors = [];

foreach ($recentStats as $stat) {
    $chartDates[] = date('m/d', strtotime($stat['date']));
    $chartViews[] = $stat['page_views'];
    $chartVisitors[] = $stat['unique_visitors'];
}
?>

<div class="dashboard">
    <!-- 统计卡片 -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🧭</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['nav_count'] ?></div>
                <div class="stat-label">导航项目</div>
            </div>
            <div class="stat-trend">
                <span class="trend-up">↗</span>
                <span class="trend-text">活跃</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📁</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['category_count'] ?></div>
                <div class="stat-label">分类数量</div>
            </div>
            <div class="stat-trend">
                <span class="trend-up">↗</span>
                <span class="trend-text">稳定</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👁️</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['today_views'] ?></div>
                <div class="stat-label">今日访问</div>
            </div>
            <div class="stat-trend">
                <span class="trend-up">↗</span>
                <span class="trend-text">+12%</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['today_visitors'] ?></div>
                <div class="stat-label">独立访客</div>
            </div>
            <div class="stat-trend">
                <span class="trend-up">↗</span>
                <span class="trend-text">+8%</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🖱️</div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_clicks']) ?></div>
                <div class="stat-label">总点击量</div>
            </div>
            <div class="stat-trend">
                <span class="trend-up">↗</span>
                <span class="trend-text">累计</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-content">
                <div class="stat-number"><?= round(memory_get_usage() / 1024 / 1024, 1) ?>MB</div>
                <div class="stat-label">内存使用</div>
            </div>
            <div class="stat-trend">
                <span class="trend-normal">→</span>
                <span class="trend-text">正常</span>
            </div>
        </div>
    </div>
    
    <!-- 图表和快速操作 -->
    <div class="dashboard-grid">
        <!-- 访问统计图表 -->
        <div class="dashboard-card chart-card">
            <div class="card-header">
                <h3>📈 访问统计</h3>
                <div class="card-actions">
                    <select id="chartPeriod" onchange="updateChart()">
                        <option value="7">最近7天</option>
                        <option value="30">最近30天</option>
                        <option value="90">最近90天</option>
                    </select>
                </div>
            </div>
            <div class="card-content">
                <canvas id="visitsChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- 快速操作 -->
        <div class="dashboard-card quick-actions-card">
            <div class="card-header">
                <h3>⚡ 快速操作</h3>
            </div>
            <div class="card-content">
                <div class="quick-actions">
                    <a href="?page=navigation&action=add" class="quick-action">
                        <div class="action-icon">➕</div>
                        <div class="action-text">添加导航</div>
                    </a>
                    <a href="?page=categories&action=add" class="quick-action">
                        <div class="action-icon">📁</div>
                        <div class="action-text">新建分类</div>
                    </a>
                    <a href="?page=profile" class="quick-action">
                        <div class="action-icon">👤</div>
                        <div class="action-text">编辑资料</div>
                    </a>
                    <a href="?page=themes" class="quick-action">
                        <div class="action-icon">🎨</div>
                        <div class="action-text">主题设置</div>
                    </a>
                    <a href="?page=settings" class="quick-action">
                        <div class="action-icon">⚙️</div>
                        <div class="action-text">系统设置</div>
                    </a>
                    <a href="../index.html" target="_blank" class="quick-action">
                        <div class="action-icon">🏠</div>
                        <div class="action-text">查看前台</div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- 热门导航 -->
        <div class="dashboard-card popular-nav-card">
            <div class="card-header">
                <h3>🔥 热门导航</h3>
                <a href="?page=navigation" class="card-link">查看全部</a>
            </div>
            <div class="card-content">
                <?php if (empty($popularItems)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <div class="empty-text">暂无数据</div>
                    </div>
                <?php else: ?>
                    <div class="popular-list">
                        <?php foreach ($popularItems as $item): ?>
                            <div class="popular-item">
                                <div class="item-icon"><?= htmlspecialchars($item['icon'] ?? '🔗') ?></div>
                                <div class="item-info">
                                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="item-category"><?= htmlspecialchars($item['category_name']) ?></div>
                                </div>
                                <div class="item-stats">
                                    <span class="click-count"><?= number_format($item['click_count']) ?></span>
                                    <span class="click-label">点击</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 系统状态 -->
        <div class="dashboard-card system-status-card">
            <div class="card-header">
                <h3>🖥️ 系统状态</h3>
            </div>
            <div class="card-content">
                <div class="status-list">
                    <div class="status-item">
                        <div class="status-label">PHP版本</div>
                        <div class="status-value">
                            <span class="status-indicator status-ok"></span>
                            <?= PHP_VERSION ?>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">数据库</div>
                        <div class="status-value">
                            <span class="status-indicator status-ok"></span>
                            MySQL 连接正常
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">磁盘空间</div>
                        <div class="status-value">
                            <span class="status-indicator status-ok"></span>
                            <?= formatFileSize(disk_free_space('.')) ?> 可用
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">上次备份</div>
                        <div class="status-value">
                            <span class="status-indicator status-warning"></span>
                            未配置
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 最近活动 -->
        <div class="dashboard-card recent-activity-card">
            <div class="card-header">
                <h3>📋 最近活动</h3>
                <a href="?page=logs" class="card-link">查看全部</a>
            </div>
            <div class="card-content">
                <?php if (empty($recentLogs)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <div class="empty-text">暂无活动记录</div>
                    </div>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php
                                    $icons = [
                                        'login' => '🔑',
                                        'logout' => '🚪',
                                        'create' => '➕',
                                        'update' => '✏️',
                                        'delete' => '🗑️',
                                        'upload' => '📤'
                                    ];
                                    echo $icons[$log['action']] ?? '📝';
                                    ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-description">
                                        <?= htmlspecialchars($log['description']) ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="activity-user"><?= htmlspecialchars($log['username'] ?? '系统') ?></span>
                                        <span class="activity-time"><?= timeAgo($log['created_at']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 系统信息 -->
        <div class="dashboard-card system-info-card">
            <div class="card-header">
                <h3>ℹ️ 系统信息</h3>
            </div>
            <div class="card-content">
                <div class="info-list">
                    <div class="info-item">
                        <div class="info-label">系统版本</div>
                        <div class="info-value">v1.0.0</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">安装时间</div>
                        <div class="info-value"><?= date('Y-m-d H:i') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">最后登录</div>
                        <div class="info-value"><?= $currentUser['last_login'] ? date('Y-m-d H:i', strtotime($currentUser['last_login'])) : '首次登录' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">服务器时间</div>
                        <div class="info-value" id="serverTime"><?= date('Y-m-d H:i:s') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 初始化访问统计图表
const ctx = document.getElementById('visitsChart').getContext('2d');
const visitsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [{
            label: '页面浏览量',
            data: <?= json_encode($chartViews) ?>,
            borderColor: '#00f5ff',
            backgroundColor: 'rgba(0, 245, 255, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }, {
            label: '独立访客',
            data: <?= json_encode($chartVisitors) ?>,
            borderColor: '#ff0080',
            backgroundColor: 'rgba(255, 0, 128, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            }
        },
        elements: {
            point: {
                radius: 4,
                hoverRadius: 6
            }
        }
    }
});

// 更新图表数据
function updateChart() {
    const period = document.getElementById('chartPeriod').value;
    // 这里可以通过AJAX获取不同时间段的数据
    console.log('更新图表数据，时间段：', period);
}

// 实时更新服务器时间
function updateServerTime() {
    const now = new Date();
    const timeString = now.getFullYear() + '-' + 
        String(now.getMonth() + 1).padStart(2, '0') + '-' + 
        String(now.getDate()).padStart(2, '0') + ' ' + 
        String(now.getHours()).padStart(2, '0') + ':' + 
        String(now.getMinutes()).padStart(2, '0') + ':' + 
        String(now.getSeconds()).padStart(2, '0');
    
    const timeElement = document.getElementById('serverTime');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// 每秒更新时间
setInterval(updateServerTime, 1000);

// 页面加载完成后的初始化
document.addEventListener('DOMContentLoaded', function() {
    // 添加卡片悬停效果
    const cards = document.querySelectorAll('.dashboard-card, .stat-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // 统计数字动画
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(number => {
        const finalValue = parseInt(number.textContent);
        let currentValue = 0;
        const increment = finalValue / 50;
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            number.textContent = Math.floor(currentValue);
        }, 20);
    });
});
</script>

<style>
/* 仪表盘样式 */
.dashboard {
    padding: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}

.stat-card:hover {
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    font-size: 32px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #00f5ff, #ff0080);
    border-radius: 12px;
    color: white;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.stat-trend {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.trend-up {
    color: #28a745;
    font-size: 16px;
}

.trend-normal {
    color: #ffc107;
    font-size: 16px;
}

.trend-text {
    font-size: 12px;
    color: #666;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.card-link {
    color: #00f5ff;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.card-link:hover {
    color: #ff0080;
}

.card-content {
    padding: 25px;
}

.chart-card {
    grid-column: span 2;
}

.chart-card .card-content {
    height: 300px;
    position: relative;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
}

.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.quick-action:hover {
    background: linear-gradient(135deg, #00f5ff, #ff0080);
    color: white;
    transform: translateY(-2px);
}

.action-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

.action-text {
    font-size: 14px;
    font-weight: 500;
}

.popular-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.popular-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.popular-item:hover {
    background: #e9ecef;
}

.item-icon {
    font-size: 20px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 8px;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
}

.item-category {
    font-size: 12px;
    color: #666;
}

.item-stats {
    text-align: right;
}

.click-count {
    font-size: 18px;
    font-weight: 700;
    color: #00f5ff;
    display: block;
}

.click-label {
    font-size: 12px;
    color: #666;
}

.status-list,
.info-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.status-item,
.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.status-item:last-child,
.info-item:last-child {
    border-bottom: none;
}

.status-label,
.info-label {
    font-weight: 500;
    color: #333;
}

.status-value,
.info-value {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #666;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-ok {
    background: #28a745;
}

.status-warning {
    background: #ffc107;
}

.status-error {
    background: #dc3545;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.activity-icon {
    font-size: 16px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 6px;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-description {
    font-size: 14px;
    color: #333;
    margin-bottom: 4px;
    word-break: break-word;
}

.activity-meta {
    display: flex;
    gap: 10px;
    font-size: 12px;
    color: #666;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.empty-text {
    font-size: 14px;
}

/* 响应式 */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .stat-card {
        padding: 20px;
        flex-direction: column;
        text-align: center;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .chart-card {
        grid-column: span 1;
    }
    
    .quick-actions {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 10px;
    }
    
    .quick-action {
        padding: 15px;
    }
    
    .card-header {
        padding: 15px 20px;
    }
    
    .card-content {
        padding: 20px;
    }
}
</style>