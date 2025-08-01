<?php
/**
 * 系统日志页面
 */

// 分页参数
$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// 初始化调试信息
$debug = [];

// 筛选参数
$action = $_GET['action_filter'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// 默认表名
$logsTable = 'system_logs';
$debug['using_table'] = $logsTable;

// 检查system_logs表是否存在
try {
    $tableCheck = fetchAll("SHOW TABLES LIKE 'system_logs'");
    $systemLogsExists = !empty($tableCheck);
    
    // 检查admin_logs表是否存在
    $adminLogsCheck = fetchAll("SHOW TABLES LIKE 'admin_logs'");
    if (!empty($adminLogsCheck)) {
        $adminLogsCount = fetchOne("SELECT COUNT(*) as count FROM admin_logs")['count'];
        if ($adminLogsCount > 0) {
            $logsTable = 'admin_logs';
        }
    }
} catch (Exception $e) {
    // 如果出错，使用默认表名
}

// 确定表结构
$actionField = 'action';
$dateField = 'created_at';

// 检查表结构，确定正确的字段名
try {
    $tableStructure = fetchAll("DESCRIBE {$logsTable}");
    if (!empty($tableStructure)) {
        $columns = array_map(function($row) {
            return $row['Field'];
        }, $tableStructure);
        
        // 检查操作类型字段
        if (!in_array('action', $columns) && in_array('type', $columns)) {
            $actionField = 'type';
        }
        
        // 检查日期字段
        if (!in_array('created_at', $columns)) {
            if (in_array('time', $columns)) {
                $dateField = 'time';
            } elseif (in_array('date', $columns)) {
                $dateField = 'date';
            }
        }
    }
    
    $debug['action_field'] = $actionField;
    $debug['date_field'] = $dateField;
} catch (Exception $e) {
    $debug['field_detection_error'] = $e->getMessage();
}

// 构建查询条件
$where = '1=1';
$params = [];

if ($action) {
    $where .= " AND {$actionField} = ?";
    $params[] = $action;
}

if ($dateFrom) {
    // 确保日期格式正确
    $formattedDateFrom = date('Y-m-d', strtotime($dateFrom));
    $where .= " AND DATE({$dateField}) >= ?";
    $params[] = $formattedDateFrom;
    $debug['formatted_date_from'] = $formattedDateFrom;
}

if ($dateTo) {
    // 确保日期格式正确，并设置为当天的结束时间
    $formattedDateTo = date('Y-m-d', strtotime($dateTo));
    $where .= " AND DATE({$dateField}) <= ?";
    $params[] = $formattedDateTo;
    $debug['formatted_date_to'] = $formattedDateTo;
}

// 更新调试信息
$debug['where'] = $where;
$debug['params'] = $params;
$debug['query'] = "SELECT l.* FROM {$logsTable} l WHERE {$where} ORDER BY l.created_at DESC LIMIT {$limit} OFFSET {$offset}";

// 测试数据库连接
try {
    $testConnection = fetchOne("SELECT 1 as test");
    $debug['db_connection'] = ($testConnection && $testConnection['test'] == 1) ? '正常' : '异常';
} catch (Exception $e) {
    $debug['db_connection_error'] = $e->getMessage();
}

// 检查表结构
try {
    // 检查system_logs表结构
    $tableStructure = fetchAll("DESCRIBE system_logs");
    if (!empty($tableStructure)) {
        $debug['system_logs_columns'] = array_map(function($row) {
            return $row['Field'];
        }, $tableStructure);
    }
    
    // 检查admin_logs表结构（可能的替代表）
    $adminLogsCheck = fetchAll("SHOW TABLES LIKE 'admin_logs'");
    if (!empty($adminLogsCheck)) {
        $adminLogsStructure = fetchAll("DESCRIBE admin_logs");
        $debug['admin_logs_columns'] = array_map(function($row) {
            return $row['Field'];
        }, $adminLogsStructure);
    }
} catch (Exception $e) {
    $debug['table_structure_error'] = $e->getMessage();
}

// 检查所有表
try {
    $allTables = fetchAll("SHOW TABLES");
    $debug['all_tables'] = array_map(function($row) {
        return reset($row); // 获取第一个元素
    }, $allTables);
    
    // 检查system_logs表是否存在
    $tableCheck = fetchAll("SHOW TABLES LIKE 'system_logs'");
    $debug['system_logs_exists'] = !empty($tableCheck);
    
    // 检查logs表是否存在
    $logsTableCheck = fetchAll("SHOW TABLES LIKE 'logs'");
    $debug['logs_exists'] = !empty($logsTableCheck);
    
    // 如果system_logs表存在，检查记录总数
    if (!empty($tableCheck)) {
        $allLogs = fetchOne("SELECT COUNT(*) as count FROM system_logs")['count'];
        $debug['total_logs_in_db'] = $allLogs;
    }
    
    // 如果logs表存在，检查记录总数
    if (!empty($logsTableCheck)) {
        $allLogsAlt = fetchOne("SELECT COUNT(*) as count FROM logs")['count'];
        $debug['total_logs_in_logs_table'] = $allLogsAlt;
    }
} catch (Exception $e) {
    $debug['table_error'] = $e->getMessage();
}

// 记录使用的表名
$debug['using_table'] = $logsTable;

// 获取日志列表
try {
    // 构建动态查询
    $query = "SELECT l.* FROM {$logsTable} l WHERE {$where} ORDER BY l.created_at DESC LIMIT {$limit} OFFSET {$offset}";
    $logs = fetchAll($query, $params);
    $debug['query'] = $query;
    $debug['query_success'] = true;
} catch (Exception $e) {
    $logs = [];
    $debug['query_error'] = $e->getMessage();
}

// 获取总数
try {
    $total = fetchOne("SELECT COUNT(*) as count FROM {$logsTable} WHERE {$where}", $params)['count'];
    $totalPages = ceil($total / $limit);
    $debug['count_success'] = true;
} catch (Exception $e) {
    $total = 0;
    $totalPages = 0;
    $debug['count_error'] = $e->getMessage();
}

// 获取操作类型列表
try {
    $actions = fetchAll("SELECT DISTINCT {$actionField} FROM {$logsTable} ORDER BY {$actionField}");
    $debug['actions_query_success'] = true;
} catch (Exception $e) {
    $actions = [];
    $debug['actions_query_error'] = $e->getMessage();
}

// 处理清理日志
if (isset($_GET['action']) && $_GET['action'] === 'clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $days = (int)($_POST['days'] ?? 30);
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $deleted = delete('system_logs', 'created_at < ?', [$cutoffDate]);
    
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => "已清理 {$days} 天前的日志"
    ];
    
    header('Location: ?page=logs');
    exit;
}
?>

<div class="page-content" style="padding-top: 0; margin-top: 0;">
    <!-- 快速操作按钮 -->
    <div class="quick-actions" style="margin-bottom: 20px; text-align: right;">
        <button class="btn btn-secondary" onclick="showClearModal()">
            <span class="btn-icon">🗑️</span>
            清理日志
        </button>
    </div>
    <!-- 筛选器 -->
    <div class="card">
        <div class="card-header">
            <h3>🔍 筛选条件</h3>
        </div>
        <div class="card-content">
            <form method="get" class="filter-form">
                <input type="hidden" name="page" value="logs">
                
<div class="form-row">
                    <div class="form-group filter-item">
                        <label for="action_filter">操作类型</label>
                        <select id="action_filter" name="action_filter">
                            <option value="">全部</option>
                            <?php foreach ($actions as $actionItem): ?>
                                <?php 
                                $actionValue = $actionItem[$actionField] ?? '';
                                if (empty($actionValue)) continue;
                                ?>
                                <option value="<?= htmlspecialchars($actionValue) ?>" 
                                        <?= $action === $actionValue ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($actionValue) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group filter-item">
                        <label for="date_from">开始日期</label>
                        <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    
                    <div class="form-group filter-item">
                        <label for="date_to">结束日期</label>
                        <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    
                    <div class="form-group filter-item">
                        <label>&nbsp;</label>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">筛选</button>
                            <a href="?page=logs" class="btn btn-secondary">重置</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 日志列表 -->
    <div class="card">
<div class="card-header">
            <h3>日志记录</h3>
            <span class="record-count">共 <?= number_format($total) ?> 条记录</span>
        </div>
        
        <!-- 调试信息已移除 -->
        <div class="card-content">
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>暂无日志记录</h3>
                    <p>没有找到符合条件的日志记录</p>
                </div>
            <?php else: ?>
                <div class="logs-list">
                    <?php foreach ($logs as $log): ?>
                        <div class="log-item">
                            <div class="log-header">
                                <div class="log-action">
                                    <span class="action-icon">
                                        <?php
                                        $icons = [
                                            'login' => '🔑',
                                            'logout' => '🚪',
                                            'create' => '➕',
                                            'update' => '✏️',
                                            'delete' => '🗑️',
                                            'upload' => '📤',
                                            'download' => '📥',
                                            'error' => '❌',
                                            'warning' => '⚠️',
                                            'info' => 'ℹ️'
                                        ];
                                        $action = $log['action'] ?? $log['type'] ?? '未知';
                                        echo $icons[$action] ?? '📝';
                                        ?>
                                    </span>
                                    <span class="action-name"><?= htmlspecialchars($action) ?></span>
                                </div>
                                
                                <div class="log-meta">
                                    <span class="log-user"><?= htmlspecialchars($log['username'] ?? $log['user'] ?? '系统') ?></span>
                                    <span class="log-time"><?= date('Y-m-d H:i:s', strtotime($log['created_at'] ?? $log['time'] ?? $log['date'] ?? 'now')) ?></span>
                                </div>
                            </div>
                            
                            <div class="log-description">
                                <?= htmlspecialchars($log['description'] ?? $log['message'] ?? $log['content'] ?? '无描述') ?>
                            </div>
                            
                            <?php 
                            $ipField = null;
                            $uaField = null;
                            
                            // 尝试找到IP地址字段
                            foreach (['ip_address', 'ip', 'client_ip'] as $field) {
                                if (isset($log[$field]) && !empty($log[$field])) {
                                    $ipField = $field;
                                    break;
                                }
                            }
                            
                            // 尝试找到User Agent字段
                            foreach (['user_agent', 'ua', 'browser', 'client'] as $field) {
                                if (isset($log[$field]) && !empty($log[$field])) {
                                    $uaField = $field;
                                    break;
                                }
                            }
                            ?>
                            
                            <?php if ($ipField || $uaField): ?>
                                <div class="log-details">
                                    <?php if ($ipField): ?>
                                        <span class="log-ip">IP: <?= htmlspecialchars($log[$ipField]) ?></span>
                                    <?php endif; ?>
                                    <?php if ($uaField): ?>
                                        <span class="log-agent" title="<?= htmlspecialchars($log[$uaField]) ?>">
                                            <?= htmlspecialchars(substr($log[$uaField], 0, 50)) ?><?= strlen($log[$uaField]) > 50 ? '...' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=logs&p=<?= $page - 1 ?><?= $action ? '&action_filter=' . urlencode($action) : '' ?><?= $dateFrom ? '&date_from=' . $dateFrom : '' ?><?= $dateTo ? '&date_to=' . $dateTo : '' ?>" class="btn btn-sm btn-secondary">上一页</a>
                        <?php endif; ?>
                        
                        <span class="page-info">第 <?= $page ?> 页，共 <?= $totalPages ?> 页</span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=logs&p=<?= $page + 1 ?><?= $action ? '&action_filter=' . urlencode($action) : '' ?><?= $dateFrom ? '&date_from=' . $dateFrom : '' ?><?= $dateTo ? '&date_to=' . $dateTo : '' ?>" class="btn btn-sm btn-secondary">下一页</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 清理日志模态框 -->
<div class="modal" id="clearModal" style="display: none;">
    <div class="modal-overlay" onclick="hideClearModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>清理日志</h3>
            <button class="modal-close" onclick="hideClearModal()">×</button>
        </div>
        <form method="post" action="?page=logs&action=clear">
            <div class="modal-body">
                <p>选择要清理多少天前的日志记录：</p>
                
                <div class="form-group">
                    <label for="days">保留天数</label>
                    <select id="days" name="days">
                        <option value="7">保留最近7天</option>
                        <option value="30" selected>保留最近30天</option>
                        <option value="90">保留最近90天</option>
                        <option value="365">保留最近1年</option>
                    </select>
                </div>
                
                <div class="alert alert-warning">
                    <span class="alert-icon">⚠️</span>
                    <span class="alert-message">此操作不可恢复，请谨慎操作！</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger">确认清理</button>
                <button type="button" class="btn btn-secondary" onclick="hideClearModal()">取消</button>
            </div>
        </form>
    </div>
</div>

<script>
function showClearModal() {
    document.getElementById('clearModal').style.display = 'flex';
}

function hideClearModal() {
    document.getElementById('clearModal').style.display = 'none';
}
</script>

<style>
.filter-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 20px;
    align-items: end;
}

.filter-form .form-group {
    margin-bottom: 0;
}

.filter-form .filter-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.filter-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
    text-align: center;
}

.filter-form select,
.filter-form input[type="date"] {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #f8f9fa;
    height: 42px;
    appearance: none;
}

.filter-form select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 30px;
}

.filter-form input[type="date"]::-webkit-calendar-picker-indicator {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
    cursor: pointer;
}

.filter-form select:focus,
.filter-form input[type="date"]:focus {
    outline: none;
    border-color: #00d2d3;
    background-color: white;
    box-shadow: 0 0 0 3px rgba(0, 210, 211, 0.1);
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.filter-actions .btn {
    height: 42px;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.filter-actions .btn-primary {
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
    color: white;
    border: none;
}

.filter-actions .btn-secondary {
    background: #f1f3f5;
    color: #495057;
    border: none;
}

.filter-actions .btn-primary:hover {
    box-shadow: 0 4px 10px rgba(0, 210, 211, 0.3);
    transform: translateY(-2px);
}

.filter-actions .btn-secondary:hover {
    background: #e9ecef;
}

.record-count {
    color: #666;
    font-size: 14px;
}

.logs-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.log-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
}

.log-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.log-action {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.action-icon {
    font-size: 16px;
}

.log-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 14px;
    color: #666;
}

.log-description {
    margin-bottom: 10px;
    line-height: 1.5;
}

.log-details {
    display: flex;
    gap: 20px;
    font-size: 12px;
    color: #999;
    padding-top: 10px;
    border-top: 1px solid #e9ecef;
}

.log-agent {
    cursor: help;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.page-info {
    color: #666;
    font-size: 14px;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 400px;
    width: 90%;
    position: relative;
    z-index: 1001;
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

@media (max-width: 768px) {
    .filter-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .log-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .log-details {
        flex-direction: column;
        gap: 5px;
    }
}
</style>