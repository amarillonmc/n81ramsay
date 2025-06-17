<h2>确认高级投票</h2>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>投票信息确认</h3>
                    <p class="text-muted">请仔细检查以下信息，确认无误后提交投票。</p>
                </div>
                <div class="card-body">
                    <div class="vote-summary">
                        <div class="summary-item">
                            <label>环境：</label>
                            <span><?php echo Utils::escapeHtml($environment['text']); ?></span>
                        </div>
                        <div class="summary-item">
                            <label>投票状态：</label>
                            <span class="status-<?php echo $status; ?>"><?php echo Utils::getLimitStatusText($status); ?></span>
                        </div>
                        <div class="summary-item">
                            <label>发起人：</label>
                            <span><?php echo Utils::escapeHtml($initiatorId); ?></span>
                        </div>
                        <div class="summary-item">
                            <label>理由：</label>
                            <div class="reason-text"><?php echo nl2br(Utils::escapeHtml($reason)); ?></div>
                        </div>
                    </div>

                    <div class="cards-section">
                        <h4>涉及卡片列表 (共 <?php echo count($cards); ?> 张)</h4>
                        
                        <?php if (!empty($invalidCardIds)): ?>
                            <div class="alert alert-warning">
                                <strong>注意：</strong>以下卡片ID无效或不存在，将被忽略：
                                <?php echo implode(', ', $invalidCardIds); ?>
                            </div>
                        <?php endif; ?>

                        <div class="cards-table-container">
                            <table class="cards-table">
                                <thead>
                                    <tr>
                                        <th>卡片ID</th>
                                        <th>卡片名称</th>
                                        <th>类型</th>
                                        <th>属性/种族</th>
                                        <th>当前状态</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cards as $card): ?>
                                        <tr>
                                            <td><?php echo $card['id']; ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $card['id']; ?>" target="_blank">
                                                    <?php echo Utils::escapeHtml($card['name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo Utils::escapeHtml($card['type_text']); ?></td>
                                            <td>
                                                <?php if ($card['type'] & 0x1): ?>
                                                    <?php echo Utils::escapeHtml($card['attribute_text']); ?>
                                                <?php else: ?>
                                                    <?php echo Utils::escapeHtml($card['race_text']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="current-status status-<?php echo $card['current_limit_status']; ?>">
                                                    <?php echo Utils::getLimitStatusText($card['current_limit_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form method="POST" action="<?php echo BASE_URL; ?>?controller=vote&action=createAdvanced">
                        <!-- 隐藏字段保存表单数据 -->
                        <input type="hidden" name="card_ids" value="<?php echo Utils::escapeHtml($cardIdsString); ?>">
                        <input type="hidden" name="environment_id" value="<?php echo $environmentId; ?>">
                        <input type="hidden" name="status" value="<?php echo $status; ?>">
                        <input type="hidden" name="reason" value="<?php echo Utils::escapeHtml($reason); ?>">
                        <input type="hidden" name="initiator_id" value="<?php echo Utils::escapeHtml($initiatorId); ?>">
                        
                        <div class="form-actions">
                            <button type="submit" name="action" value="confirm" class="btn btn-success">确认提交投票</button>
                            <button type="submit" name="action" value="edit" class="btn btn-secondary">返回编辑</button>
                            <a href="<?php echo BASE_URL; ?>?controller=vote" class="btn btn-outline">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.card {
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
}

.card-header {
    background-color: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #ddd;
    border-radius: 8px 8px 0 0;
}

.card-header h3 {
    margin: 0 0 5px 0;
}

.card-body {
    padding: 20px;
}

.vote-summary {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.summary-item {
    display: flex;
    margin-bottom: 10px;
    align-items: flex-start;
}

.summary-item label {
    font-weight: bold;
    min-width: 100px;
    margin-right: 10px;
}

.reason-text {
    background-color: white;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-width: 600px;
    line-height: 1.5;
}

.cards-section {
    margin-bottom: 30px;
}

.cards-section h4 {
    margin-bottom: 15px;
    color: #333;
}

.cards-table-container {
    overflow-x: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.cards-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.cards-table th,
.cards-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.cards-table th {
    background-color: #f8f9fa;
    font-weight: bold;
    position: sticky;
    top: 0;
}

.cards-table tr:hover {
    background-color: #f5f5f5;
}

.cards-table a {
    color: #007bff;
    text-decoration: none;
}

.cards-table a:hover {
    text-decoration: underline;
}

.current-status {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.status-0 {
    background-color: #dc3545;
    color: white;
}

.status-1 {
    background-color: #fd7e14;
    color: white;
}

.status-2 {
    background-color: #ffc107;
    color: #212529;
}

.status-3 {
    background-color: #28a745;
    color: white;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
    font-weight: bold;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

.btn-outline {
    background-color: transparent;
    color: #6c757d;
    border: 1px solid #6c757d;
}

.btn-outline:hover {
    background-color: #6c757d;
    color: white;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeaa7;
}

.text-muted {
    color: #6c757d;
}
</style>
