<h2>服务器提示管理</h2>

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

<?php if (!file_exists(TIPS_FILE_PATH)): ?>
    <div class="alert alert-warning">
        <strong>提示文件不存在</strong><br>
        文件路径：<?php echo Utils::escapeHtml(TIPS_FILE_PATH); ?><br>
        您可以添加第一条提示来创建该文件。
    </div>
<?php endif; ?>

<?php
$originalPath = dirname(__DIR__, 3) . '/data/const/tips.json';
$isUsingTempPath = (TIPS_FILE_PATH !== $originalPath);
?>

<?php if ($isUsingTempPath): ?>
    <div class="alert alert-warning">
        <strong>注意：正在使用临时文件路径</strong><br>
        由于原始目录权限问题，系统正在使用临时目录存储提示文件。<br>
        当前文件路径：<?php echo Utils::escapeHtml(TIPS_FILE_PATH); ?><br>
        原始路径：<?php echo Utils::escapeHtml($originalPath); ?><br>
        <strong>建议：</strong>请联系系统管理员修复目录权限问题。
    </div>
<?php endif; ?>

<?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
    <div class="alert alert-info">
        <strong>调试信息</strong><br>
        当前文件路径：<?php echo Utils::escapeHtml(TIPS_FILE_PATH); ?><br>
        原始文件路径：<?php echo Utils::escapeHtml($originalPath); ?><br>
        使用临时路径：<?php echo $isUsingTempPath ? '是' : '否'; ?><br>
        目录：<?php echo Utils::escapeHtml(dirname(TIPS_FILE_PATH)); ?><br>
        目录是否存在：<?php echo is_dir(dirname(TIPS_FILE_PATH)) ? '是' : '否'; ?><br>
        目录是否可写：<?php echo is_writable(dirname(TIPS_FILE_PATH)) ? '是' : '否'; ?><br>
        文件是否存在：<?php echo file_exists(TIPS_FILE_PATH) ? '是' : '否'; ?><br>
        <?php if (file_exists(TIPS_FILE_PATH)): ?>
            文件是否可读：<?php echo is_readable(TIPS_FILE_PATH) ? '是' : '否'; ?><br>
            文件是否可写：<?php echo is_writable(TIPS_FILE_PATH) ? '是' : '否'; ?><br>
            文件大小：<?php echo filesize(TIPS_FILE_PATH); ?> 字节<br>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3>添加新提示</h3>
    </div>
    <div class="card-body">
        <form action="<?php echo BASE_URL; ?>?controller=admin&action=addTip" method="post">
            <div class="form-group">
                <label for="tip_content">提示内容</label>
                <textarea id="tip_content" name="tip_content" rows="3" required placeholder="请输入服务器提示内容..."></textarea>
            </div>
            <button type="submit" class="btn">添加提示</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>现有提示列表</h3>
    </div>
    <div class="card-body">
        <?php if (empty($tips)): ?>
            <div class="alert alert-info">
                暂无服务器提示数据
            </div>
        <?php else: ?>
            <div class="tips-list">
                <?php foreach ($tips as $index => $tip): ?>
                    <div class="tip-item card mb-3">
                        <div class="card-body">
                            <div class="tip-content">
                                <strong>提示 #<?php echo $index + 1; ?>:</strong>
                                <p><?php echo Utils::escapeHtml($tip); ?></p>
                            </div>
                            <div class="tip-actions">
                                <button type="button" class="btn btn-sm edit-tip-btn" onclick="toggleEdit(<?php echo $index; ?>)">编辑</button>
                                <form action="<?php echo BASE_URL; ?>?controller=admin&action=deleteTip" method="post" style="display: inline;">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除这条提示吗？')">删除</button>
                                </form>
                            </div>

                            <!-- 编辑表单（默认隐藏） -->
                            <div class="tip-edit-form" id="edit-form-<?php echo $index; ?>" style="display: none;">
                                <form action="<?php echo BASE_URL; ?>?controller=admin&action=editTip" method="post">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <div class="form-group">
                                        <textarea name="tip_content" rows="3" required><?php echo Utils::escapeHtml($tip); ?></textarea>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-sm">保存</button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleEdit(<?php echo $index; ?>)">取消</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>



<style>
.tip-item {
    border-left: 4px solid #007bff;
}

.tip-content p {
    margin: 10px 0;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
    word-wrap: break-word;
}

.tip-actions {
    text-align: right;
    margin-top: 10px;
}

.tip-edit-form {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
}

.form-actions {
    text-align: right;
    margin-top: 10px;
}

.form-actions .btn {
    margin-left: 10px;
}
</style>

<script>
function toggleEdit(index) {
    const editForm = document.getElementById('edit-form-' + index);
    const tipContent = editForm.parentElement.querySelector('.tip-content');

    if (editForm.style.display === 'none' || editForm.style.display === '') {
        // 显示编辑表单，隐藏内容
        editForm.style.display = 'block';
        tipContent.style.display = 'none';
    } else {
        // 隐藏编辑表单，显示内容
        editForm.style.display = 'none';
        tipContent.style.display = 'block';
    }
}
</script>
