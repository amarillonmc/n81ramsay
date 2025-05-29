<h2>召唤词投稿</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-success">
        <?php echo Utils::escapeHtml($message); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error">
        <?php echo Utils::escapeHtml($error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>投稿召唤词</h3>
    </div>
    <div class="card-body">
        <div class="submission-info">
            <h4>投稿说明</h4>
            <ul>
                <li>每个用户最多可以同时投稿 <strong><?php echo MAX_PENDING_DIALOGUES_PER_USER; ?></strong> 个召唤词等待审核</li>
                <li>请确保输入正确的卡片ID和作者ID</li>
                <li>召唤词内容应该符合卡片主题，避免不当内容</li>
                <?php if (DIALOGUE_SUBMISSION_STRICTNESS == 2): ?>
                    <li><strong>严格模式</strong>：系统会验证作者ID和卡片前缀是否匹配</li>
                <?php elseif (DIALOGUE_SUBMISSION_STRICTNESS == 1): ?>
                    <li><strong>宽松模式</strong>：系统会验证作者ID是否存在，但不强制要求前缀匹配</li>
                <?php else: ?>
                    <li><strong>自由模式</strong>：系统不验证作者信息</li>
                <?php endif; ?>
            </ul>
        </div>

        <form action="<?php echo BASE_URL; ?>?controller=dialogue&action=submitDialogue" method="post" class="submission-form">
            <div class="form-group">
                <label for="card_id">卡片ID <span class="required">*</span></label>
                <input type="text" id="card_id" name="card_id" required placeholder="例如：33700001">
                <small class="form-help">请输入完整的卡片ID数字</small>
            </div>

            <div class="form-group">
                <label for="dialogue">召唤词内容 <span class="required">*</span></label>
                <textarea id="dialogue" name="dialogue" rows="4" required placeholder="请输入召唤词内容..."></textarea>
                <small class="form-help">支持换行，请避免过长的内容</small>
            </div>

            <div class="form-group">
                <label for="author_id">作者ID <span class="required">*</span></label>
                <input type="text" id="author_id" name="author_id" required placeholder="例如：作者名称">
                <small class="form-help">请输入卡片作者的名称或别名</small>
            </div>

            <div class="form-group">
                <label for="user_id">您的用户ID <span class="required">*</span></label>
                <input type="text" id="user_id" name="user_id" required placeholder="请输入您的用户ID">
                <small class="form-help">用于识别投稿者身份</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">提交投稿</button>
                <a href="<?php echo BASE_URL; ?>?controller=dialogue" class="btn btn-secondary">返回列表</a>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h3>如何查找卡片信息</h3>
    </div>
    <div class="card-body">
        <p>如果您不确定卡片ID或作者信息，可以：</p>
        <ul>
            <li><a href="<?php echo BASE_URL; ?>?controller=card">使用卡片检索功能</a>查找卡片ID</li>
            <li>在卡片详情页面查看作者信息</li>
            <li>参考现有的召唤词列表了解格式</li>
        </ul>
    </div>
</div>

<style>
.submission-info {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.submission-info h4 {
    margin-top: 0;
    color: #495057;
}

.submission-info ul {
    margin-bottom: 0;
}

.submission-info li {
    margin-bottom: 8px;
}

.submission-form .form-group {
    margin-bottom: 25px;
}

.submission-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #495057;
}

.required {
    color: #dc3545;
}

.submission-form input[type="text"],
.submission-form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.submission-form input[type="text"]:focus,
.submission-form textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.form-help {
    display: block;
    margin-top: 5px;
    color: #6c757d;
    font-size: 12px;
}

.form-actions {
    text-align: right;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.form-actions .btn {
    margin-left: 10px;
}

.mt-4 {
    margin-top: 2rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 卡片ID输入验证
    const cardIdInput = document.getElementById('card_id');
    cardIdInput.addEventListener('input', function() {
        const value = this.value;
        if (value && !/^\d+$/.test(value)) {
            this.setCustomValidity('卡片ID必须是数字');
        } else {
            this.setCustomValidity('');
        }
    });

    // 表单提交前验证
    const form = document.querySelector('.submission-form');
    form.addEventListener('submit', function(e) {
        const cardId = document.getElementById('card_id').value.trim();
        const dialogue = document.getElementById('dialogue').value.trim();
        const authorId = document.getElementById('author_id').value.trim();
        const userId = document.getElementById('user_id').value.trim();

        if (!cardId || !dialogue || !authorId || !userId) {
            e.preventDefault();
            alert('请填写所有必填字段');
            return;
        }

        if (!/^\d+$/.test(cardId)) {
            e.preventDefault();
            alert('卡片ID必须是数字');
            return;
        }

        if (dialogue.length > 1000) {
            e.preventDefault();
            alert('召唤词内容过长，请控制在1000字符以内');
            return;
        }
    });
});
</script>
