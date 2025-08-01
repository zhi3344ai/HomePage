<?php
/**
 * 访问统计页面
 */

// 获取统计数据
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$lastWeek = date('Y-m-d', strtotime('-7 days'));
$lastMonth = date('Y-m-d', strtotime('-30 days'));

// 今日统计
$todayStats = fetchOne("SELECT * FROM visit_stats WHERE date = ?", [$today]) ?: [
    'page_views' => 0,
    'unique_visitors' => 0,
    'nav_clicks' => '{}',
    'user_agents' => '{}',
    'referrers' => '{}'
];

// 昨日统计
$yesterdayStats = fetchOne("SELECT * FROM visit_stats WHERE date = ?", [$yesterday]) ?: [
    'page_views' => 0,
    'unique_visitors' => 0
];

// 最近7天统计
$weekStats = fetchAll("SELECT * FROM visit_stats WHERE date >= ? ORDER BY date ASC", [$lastWeek]);

// 最近30天统计
$monthStats = fetchAll("SELECT * FROM visit_stats WHERE date >= ? ORDER BY date ASC", [$lastMonth]);

// 计算总计
$totalViews = fetchOne("SELECT SUM(page_views) as total FROM visit_stats")['total'] ?? 0;
$totalVisitors = fetchOne("SELECT SUM(unique_visitors) as total FROM visit_stats")['total'] ?? 0;

// 热门导航项目 - 只显示前5个热门项目
$popularItems = fetchAll("
    SELECT n.name, n.url, n.click_count, c.name as category_name 
    FROM nav_items n 
    LEFT JOIN nav_categories c ON n.category_id = c.id 
    WHERE n.is_active = 1 
    ORDER BY n.click_count DESC 
    LIMIT 5
");

// 准备图表数据
$chartDates = [];
$chartViews = [];
$chartVisitors = [];

foreach ($weekStats as $stat) {
    $chartDates[] = date('m/d', strtotime($stat['date']));
    $chartViews[] = $stat['page_views'];
    $chartVisitors[] = $stat['unique_visitors'];
}

// 如果数据不足7天，补充空数据
for ($i = count($chartDates); $i < 7; $i++) {
    $date = date('m/d', strtotime("-" . (6 - $i) . " days"));
    array_unshift($chartDates, $date);
    array_unshift($chartViews, 0);
    array_unshift($chartVisitors, 0);
}
?>

<div class="page-content" style="padding-top: 0; margin-top: 0;">
    <!-- 统计概览 -->
    <div class="stats-overview">
        <div class="stat-card">
            <div class="stat-icon">👁️</div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($todayStats['page_views']) ?></div>
                <div class="stat-label">今日浏览量</div>
                <div class="stat-change">
                    <?php 
                    $change = $todayStats['page_views'] - $yesterdayStats['page_views'];
                    $changeClass = $change >= 0 ? 'positive' : 'negative';
                    $changeIcon = $change >= 0 ? '↗️' : '↘️';
                    ?>
                    <span class="change-<?= $changeClass ?>">
                        <?= $changeIcon ?> <?= $change >= 0 ? '+' : '' ?><?= $change ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($todayStats['unique_visitors']) ?></div>
                <div class="stat-label">今日访客</div>
                <div class="stat-change">
                    <?php 
                    $change = $todayStats['unique_visitors'] - $yesterdayStats['unique_visitors'];
                    $changeClass = $change >= 0 ? 'positive' : 'negative';
                    $changeIcon = $change >= 0 ? '↗️' : '↘️';
                    ?>
                    <span class="change-<?= $changeClass ?>">
                        <?= $changeIcon ?> <?= $change >= 0 ? '+' : '' ?><?= $change ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($totalViews) ?></div>
                <div class="stat-label">总浏览量</div>
                <div class="stat-change">
                    <span class="change-neutral">累计数据</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🌍</div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($totalVisitors) ?></div>
                <div class="stat-label">总访客数</div>
                <div class="stat-change">
                    <span class="change-neutral">累计数据</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 访问趋势图表 -->
    <div class="card">
        <div class="card-header">
            <h3>📈 最近7天访问趋势</h3>
        </div>
        <div class="card-content">
            <canvas id="visitsChart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <div class="stats-grid">
        <!-- 热门导航项目 -->
        <div class="card">
            <div class="card-header">
                <h3>🔥 热门导航项目</h3>
            </div>
            <div class="card-content">
                <?php if (empty($popularItems)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <p>暂无点击数据</p>
                    </div>
                <?php else: ?>
                    <div class="popular-items">
                        <?php foreach ($popularItems as $index => $item): ?>
                            <div class="popular-item">
                                <div class="item-rank"><?= $index + 1 ?></div>
                                <div class="item-info">
                                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="item-category"><?= htmlspecialchars($item['category_name'] ?? '未分类') ?></div>
                                </div>
                                <div class="item-clicks"><?= number_format($item['click_count']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 最近30天统计 -->
        <div class="card">
            <div class="card-header">
                <h3>📅 最近30天统计</h3>
            </div>
            <div class="card-content">
                <?php if (empty($monthStats)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <p>暂无统计数据</p>
                    </div>
                <?php else: ?>
                    <?php
                    $monthViews = array_sum(array_column($monthStats, 'page_views'));
                    $monthVisitors = array_sum(array_column($monthStats, 'unique_visitors'));
                    $avgDaily = count($monthStats) > 0 ? round($monthViews / count($monthStats), 1) : 0;
                    ?>
                    <div class="month-stats">
                        <div class="month-stat">
                            <div class="stat-value"><?= number_format($monthViews) ?></div>
                            <div class="stat-desc">总浏览量</div>
                        </div>
                        <div class="month-stat">
                            <div class="stat-value"><?= number_format($monthVisitors) ?></div>
                            <div class="stat-desc">总访客数</div>
                        </div>
                        <div class="month-stat">
                            <div class="stat-value"><?= $avgDaily ?></div>
                            <div class="stat-desc">日均浏览</div>
                        </div>
                        <div class="month-stat">
                            <div class="stat-value"><?= count($monthStats) ?></div>
                            <div class="stat-desc">活跃天数</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// 创建访问趋势图表
const ctx = document.getElementById('visitsChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartDates) ?>,
        datasets: [{
            label: '浏览量',
            data: <?= json_encode($chartViews) ?>,
            borderColor: '#00f5ff',
            backgroundColor: 'rgba(0, 245, 255, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: '访客数',
            data: <?= json_encode($chartVisitors) ?>,
            borderColor: '#ff0080',
            backgroundColor: 'rgba(255, 0, 128, 0.1)',
            tension: 0.4,
            fill: true
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
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<style>
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 32px;
    opacity: 0.8;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.stat-change {
    font-size: 12px;
}

.change-positive {
    color: #28a745;
}

.change-negative {
    color: #dc3545;
}

.change-neutral {
    color: #6c757d;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.popular-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.popular-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

.item-rank {
    width: 24px;
    height: 24px;
    background: #00f5ff;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    flex-shrink: 0;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 500;
    margin-bottom: 2px;
}

.item-category {
    font-size: 12px;
    color: #666;
}

.item-clicks {
    font-weight: bold;
    color: #00f5ff;
}

.month-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.month-stat {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-value {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.stat-desc {
    font-size: 12px;
    color: #666;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-overview {
        grid-template-columns: 1fr 1fr;
    }
}
</style>