<h2>发起高级投票</h2>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo Utils::escapeHtml($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>高级投票信息</h3>
                    <p class="text-muted">高级投票允许您对多张卡片同时发起投票，适用于需要统一处理的卡片组合。</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo BASE_URL; ?>?controller=vote&action=createAdvanced">
                        <div class="form-group">
                            <label for="card_ids">卡片ID列表</label>
                            <textarea id="card_ids" name="card_ids" rows="4" placeholder="请输入一个或多个卡片ID，每行一个或用逗号分隔" required><?php echo isset($_POST['card_ids']) ? Utils::escapeHtml($_POST['card_ids']) : ''; ?></textarea>
                            <small class="form-text text-muted">
                                支持多种格式：每行一个ID、逗号分隔、空格分隔等。系统会自动解析并去重。
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="environment_id">环境</label>
                            <select id="environment_id" name="environment_id" required>
                                <option value="">请选择环境</option>
                                <?php foreach ($environments as $env): ?>
                                    <option value="<?php echo $env['id']; ?>" <?php echo (isset($_POST['environment_id']) && $_POST['environment_id'] == $env['id']) ? 'selected' : ''; ?>>
                                        <?php echo Utils::escapeHtml($env['text']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>投票状态</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <label>
                                        <input type="radio" name="status" value="0" <?php echo (isset($_POST['status']) && $_POST['status'] == '0') ? 'checked' : ''; ?>> 禁止
                                    </label>
                                </div>
                                <div class="radio-item">
                                    <label>
                                        <input type="radio" name="status" value="1" <?php echo (isset($_POST['status']) && $_POST['status'] == '1') ? 'checked' : ''; ?>> 限制
                                    </label>
                                </div>
                                <div class="radio-item">
                                    <label>
                                        <input type="radio" name="status" value="2" <?php echo (isset($_POST['status']) && $_POST['status'] == '2') ? 'checked' : ''; ?>> 准限制
                                    </label>
                                </div>
                                <div class="radio-item">
                                    <label>
                                        <input type="radio" name="status" value="3" <?php echo (isset($_POST['status']) && $_POST['status'] == '3') ? 'checked' : 'checked'; ?>> 无限制
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reason">理由</label>
                            <textarea id="reason" name="reason" rows="4" placeholder="请详细说明发起此次高级投票的理由..." required><?php echo isset($_POST['reason']) ? Utils::escapeHtml($_POST['reason']) : ''; ?></textarea>
                            <div class="character-count">
                                <span id="reason-count">0</span> / <span id="reason-min"><?php echo defined('SERIES_VOTING_REASON_MIN_LENGTH') ? SERIES_VOTING_REASON_MIN_LENGTH : 400; ?></span> 字符
                            </div>
                            <small class="form-text text-muted">
                                由于高级投票涉及多张卡片，请提供充分的理由说明。
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="initiator_id">您的ID</label>
                            <input type="text" id="initiator_id" name="initiator_id" value="<?php echo isset($_POST['initiator_id']) ? Utils::escapeHtml($_POST['initiator_id']) : ''; ?>" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="action" value="preview" class="btn btn-primary">预览确认</button>
                            <a href="<?php echo BASE_URL; ?>?controller=vote" class="btn btn-secondary">返回投票概览</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>使用说明</h4>
                </div>
                <div class="card-body">
                    <h5>什么是高级投票？</h5>
                    <p>高级投票允许您对多张卡片同时发起投票，所有卡片将使用相同的投票状态和理由。</p>
                    
                    <h5>适用场景</h5>
                    <ul>
                        <li>同一系列的多张卡片需要统一处理</li>
                        <li>功能相似的卡片组合</li>
                        <li>需要批量调整的卡片</li>
                    </ul>
                    
                    <h5>注意事项</h5>
                    <ul>
                        <li>涉及多张卡片时，理由要求与系列投票相同</li>
                        <li>所有卡片将使用相同的投票状态</li>
                        <li>确认后无法修改卡片列表</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 理由字符数统计
    const reasonTextarea = document.getElementById('reason');
    const reasonCount = document.getElementById('reason-count');
    const minLength = parseInt(document.getElementById('reason-min').textContent);
    
    function updateReasonCount() {
        const length = reasonTextarea.value.length;
        reasonCount.textContent = length;
        
        if (length < minLength) {
            reasonCount.style.color = '#dc3545';
        } else {
            reasonCount.style.color = '#28a745';
        }
    }
    
    reasonTextarea.addEventListener('input', updateReasonCount);
    updateReasonCount();
});
</script>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.row {
    display: flex;
    gap: 20px;
}

.col-md-8 {
    flex: 0 0 66.666667%;
}

.col-md-4 {
    flex: 0 0 33.333333%;
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

.card-header h3, .card-header h4 {
    margin: 0 0 5px 0;
}

.card-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input[type="text"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.radio-group {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.radio-item label {
    font-weight: normal;
    display: flex;
    align-items: center;
    gap: 5px;
}

.character-count {
    text-align: right;
    font-size: 12px;
    margin-top: 5px;
}

.form-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert ul {
    margin: 0;
    padding-left: 20px;
}

.text-muted {
    color: #6c757d;
}
</style>
