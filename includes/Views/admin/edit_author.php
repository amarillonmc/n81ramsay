<h2>编辑作者</h2>

<div class="card">
    <div class="card-header">
        <h3>编辑作者信息</h3>
    </div>
    <div class="card-body">
        <form action="<?php echo BASE_URL; ?>?controller=admin&action=editAuthor&card_prefix=<?php echo urlencode($cardPrefix); ?>" method="post">
            <div class="form-group">
                <label for="card_prefix">卡片前缀</label>
                <input type="text" id="card_prefix" name="card_prefix" value="<?php echo Utils::escapeHtml($authorMapping['card_prefix']); ?>" required>
                <small>卡片ID的前缀，通常是前3位数字</small>
            </div>

            <div class="form-group">
                <label for="author_name">作者名称</label>
                <input type="text" id="author_name" name="author_name" value="<?php echo Utils::escapeHtml($authorMapping['author_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="alias">作者别名</label>
                <input type="text" id="alias" name="alias" value="<?php echo Utils::escapeHtml($authorMapping['alias'] ?? ''); ?>">
                <small>可选，作者的其他名称</small>
            </div>

            <div class="form-group">
                <label for="contact">联系方式</label>
                <input type="text" id="contact" name="contact" value="<?php echo Utils::escapeHtml($authorMapping['contact'] ?? ''); ?>">
                <small>可选，作者的联系方式</small>
            </div>

            <div class="form-group">
                <label for="notes">备注</label>
                <textarea id="notes" name="notes" rows="3"><?php echo Utils::escapeHtml($authorMapping['notes'] ?? ''); ?></textarea>
                <small>可选，其他相关信息</small>
            </div>

            <button type="submit" class="btn">保存修改</button>
            <a href="<?php echo BASE_URL; ?>?controller=admin&action=authors" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>
