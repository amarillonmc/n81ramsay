<h2>召唤词管理</h2>

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

<?php
$originalPath = dirname(__DIR__, 3) . '/data/const/dialogues-custom.json';
$isUsingTempPath = (DIALOGUES_FILE_PATH !== $originalPath);
?>

<?php if ($isUsingTempPath): ?>
    <div class="alert alert-warning">
        <strong>注意：正在使用临时文件路径</strong><br>
        当前文件路径：<?php echo Utils::escapeHtml(DIALOGUES_FILE_PATH); ?><br>
        原始路径：<?php echo Utils::escapeHtml($originalPath); ?><br>
    </div>
<?php endif; ?>

<!-- 待审核投稿 -->
<div class="card mb-4">
    <div class="card-header">
        <h3>待审核投稿 (<?php echo count($pendingSubmissions); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($pendingSubmissions)): ?>
            <div class="alert alert-info">
                暂无待审核的投稿
            </div>
        <?php else: ?>
            <div class="submissions-list">
                <?php foreach ($pendingSubmissions as $submission): ?>
                    <div class="submission-item card mb-3 <?php echo $submission['has_warning'] ? 'warning-item' : ''; ?>">
                        <div class="card-body">
                            <div class="submission-header">
                                <h4>
                                    <?php if ($submission['card']): ?>
                                        [<?php echo Utils::escapeHtml($submission['card_id']); ?>] <?php echo Utils::escapeHtml($submission['card']['name']); ?>
                                    <?php else: ?>
                                        [<?php echo Utils::escapeHtml($submission['card_id']); ?>] 卡片未找到
                                    <?php endif; ?>
                                    <?php if ($submission['has_warning']): ?>
                                        <span class="warning-badge">⚠️ 前缀不匹配</span>
                                    <?php endif; ?>
                                </h4>
                                <div class="submission-meta">
                                    <span>投稿者：<?php echo Utils::escapeHtml($submission['username'] ?? $submission['user_id']); ?></span>
                                    <span>作者ID：<?php echo Utils::escapeHtml($submission['author_id']); ?></span>
                                    <span>投稿时间：<?php echo Utils::escapeHtml($submission['created_at']); ?></span>
                                </div>
                            </div>
                            <div class="submission-content">
                                <strong>召唤词内容：</strong>
                                <div class="dialogue-preview">
                                    <?php echo nl2br(Utils::escapeHtml($submission['dialogue'])); ?>
                                </div>
                            </div>
                            <div class="submission-actions">
                                <form action="<?php echo BASE_URL; ?>?controller=dialogue&action=reviewSubmission" method="post" style="display: inline;">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('确定要接受这个投稿吗？')">接受</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger reject-btn" data-id="<?php echo $submission['id']; ?>">拒绝</button>
                                <form action="<?php echo BASE_URL; ?>?controller=dialogue&action=deleteSubmission" method="post" style="display: inline;">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('确定要删除这个投稿吗？')">删除</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 添加新召唤词 -->
<div class="card mb-4">
    <div class="card-header">
        <h3>添加新召唤词</h3>
    </div>
    <div class="card-body">
        <form action="<?php echo BASE_URL; ?>?controller=dialogue&action=addDialogue" method="post" class="add-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="add_card_id">卡片ID</label>
                    <input type="text" id="add_card_id" name="card_id" required placeholder="例如：33700001">
                </div>
                <div class="form-group">
                    <label for="add_dialogue">召唤词内容</label>
                    <textarea id="add_dialogue" name="dialogue" rows="3" required placeholder="请输入召唤词内容..."></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">添加召唤词</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 现有召唤词管理 -->
<div class="card">
    <div class="card-header">
        <h3>现有召唤词管理 (<?php echo count($dialogueCards); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($dialogueCards)): ?>
            <div class="alert alert-info">
                暂无召唤词数据
            </div>
        <?php else: ?>
            <div class="dialogue-management">
                <?php foreach ($dialogueCards as $item): ?>
                    <div class="dialogue-item card mb-3">
                        <div class="card-body">
                            <div class="dialogue-header">
                                <h4>
                                    [<?php echo Utils::escapeHtml($item['card_id']); ?>] <?php echo Utils::escapeHtml($item['card']['name']); ?>
                                </h4>
                            </div>
                            <div class="dialogue-content">
                                <div class="current-dialogue">
                                    <strong>当前召唤词：</strong>
                                    <div class="dialogue-text">
                                        <?php echo nl2br(Utils::escapeHtml($item['dialogues'][0])); ?>
                                    </div>
                                </div>
                                <div class="dialogue-edit" id="edit-<?php echo $item['card_id']; ?>" style="display: none;">
                                    <form action="<?php echo BASE_URL; ?>?controller=dialogue&action=editDialogue" method="post">
                                        <input type="hidden" name="card_id" value="<?php echo $item['card_id']; ?>">
                                        <textarea name="dialogue" rows="3" required><?php echo Utils::escapeHtml($item['dialogues'][0]); ?></textarea>
                                        <div class="edit-actions">
                                            <button type="submit" class="btn btn-sm">保存</button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="toggleEdit('<?php echo $item['card_id']; ?>')">取消</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="dialogue-actions">
                                <button type="button" class="btn btn-sm" onclick="toggleEdit('<?php echo $item['card_id']; ?>')">编辑</button>
                                <form action="<?php echo BASE_URL; ?>?controller=dialogue&action=deleteDialogue" method="post" style="display: inline;">
                                    <input type="hidden" name="card_id" value="<?php echo $item['card_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除这个召唤词吗？')">删除</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 拒绝投稿模态框 -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>拒绝投稿</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="rejectForm" action="<?php echo BASE_URL; ?>?controller=dialogue&action=reviewSubmission" method="post">
                <input type="hidden" id="rejectSubmissionId" name="submission_id" value="">
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label for="rejectReason">拒绝原因</label>
                    <textarea id="rejectReason" name="reason" rows="3" required placeholder="请说明拒绝的原因..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">确认拒绝</button>
                    <button type="button" class="btn btn-secondary cancel-reject">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.warning-item {
    border-left: 4px solid #ffc107 !important;
}

.warning-badge {
    background-color: #ffc107;
    color: #212529;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 0.8em;
    margin-left: 10px;
}

.submission-item {
    border-left: 4px solid #17a2b8;
}

.submission-header h4 {
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
}

.submission-meta {
    margin-bottom: 15px;
}

.submission-meta span {
    display: inline-block;
    margin-right: 20px;
    color: #6c757d;
    font-size: 0.9em;
}

.submission-content {
    margin-bottom: 15px;
}

.dialogue-preview {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin-top: 5px;
    border-left: 3px solid #007bff;
}

.submission-actions {
    text-align: right;
}

.submission-actions .btn {
    margin-left: 5px;
}

.form-row {
    display: flex;
    gap: 20px;
    align-items: end;
}

.form-row .form-group {
    flex: 1;
}

.form-row .form-group:last-child {
    flex: 0 0 auto;
}

.dialogue-item {
    border-left: 4px solid #28a745;
}

.dialogue-header h4 {
    margin: 0 0 15px 0;
}

.current-dialogue {
    margin-bottom: 15px;
}

.dialogue-text {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin-top: 5px;
    border-left: 3px solid #28a745;
}

.dialogue-edit textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    margin-top: 5px;
}

.edit-actions {
    margin-top: 10px;
    text-align: right;
}

.edit-actions .btn {
    margin-left: 5px;
}

.dialogue-actions {
    text-align: right;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
}

.dialogue-actions .btn {
    margin-left: 5px;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 8px;
}

.modal-header {
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
}

.modal-body {
    padding: 20px;
}

.modal-body .form-group {
    margin-bottom: 20px;
}

.modal-body label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.modal-body textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-sizing: border-box;
}

.modal-body .form-actions {
    text-align: right;
    margin-top: 20px;
}

.modal-body .form-actions .btn {
    margin-left: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('rejectModal');
    const rejectButtons = document.querySelectorAll('.reject-btn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.querySelector('.cancel-reject');

    // 打开拒绝模态框
    rejectButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const submissionId = this.getAttribute('data-id');
            document.getElementById('rejectSubmissionId').value = submissionId;
            document.getElementById('rejectReason').value = '';
            modal.style.display = 'block';
        });
    });

    // 关闭模态框
    function closeModal() {
        modal.style.display = 'none';
    }

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // 点击模态框外部关闭
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
});

function toggleEdit(cardId) {
    const currentDiv = document.querySelector('#edit-' + cardId).parentElement.querySelector('.current-dialogue');
    const editDiv = document.getElementById('edit-' + cardId);

    if (editDiv.style.display === 'none' || editDiv.style.display === '') {
        // 显示编辑表单，隐藏当前内容
        editDiv.style.display = 'block';
        currentDiv.style.display = 'none';
    } else {
        // 隐藏编辑表单，显示当前内容
        editDiv.style.display = 'none';
        currentDiv.style.display = 'block';
    }
}
</script>
