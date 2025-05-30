<h2>生成禁卡表</h2>

<div class="card mb-4">
    <div class="card-header">
        <h3><?php echo Utils::escapeHtml(Utils::getEnvironmentById($environmentId)['text']); ?> - 可读格式</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="readable_text">可读禁卡表文本</label>
            <textarea id="readable_text" class="form-control" rows="20" readonly><?php echo $readableText; ?></textarea>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><?php echo Utils::escapeHtml(Utils::getEnvironmentById($environmentId)['text']); ?> - lflist.conf 格式</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="lflist_text">禁卡表文本</label>
            <textarea id="lflist_text" class="form-control" rows="20" readonly><?php echo $lflistText; ?></textarea>
        </div>

        <?php if ($this->userModel->hasPermission(2)): ?>
            <form action="<?php echo BASE_URL; ?>?controller=admin&action=update" method="post">
                <input type="hidden" name="environment_id" value="<?php echo $environmentId; ?>">
                <input type="hidden" name="lflist_text" value="<?php echo htmlspecialchars($lflistText); ?>">

                <button type="submit" id="update-banlist-btn" class="btn btn-primary">更新禁卡表</button>
                <a href="<?php echo BASE_URL; ?>?controller=admin&action=banlist" class="btn btn-secondary">返回</a>
            </form>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>?controller=admin&action=banlist" class="btn btn-secondary">返回</a>
        <?php endif; ?>
    </div>
</div>
