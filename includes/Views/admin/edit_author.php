<?php $escapedBaseUrl = Utils::escapeHtml(BASE_URL); ?>

<h2>编辑作者映射</h2>

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
        <h3>编辑卡号与作者信息</h3>
    </div>
    <div class="card-body">
        <form action="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=editAuthor&amp;id=<?php echo (int)$authorMapping['id']; ?>" method="post">
            <?php Utils::renderCsrfFields('admin_edit_author'); ?>
            <input type="hidden" name="mapping_id" value="<?php echo (int)$authorMapping['id']; ?>">
            <div class="form-group">
                <label for="card_prefix">卡片前缀</label>
                <input type="text" id="card_prefix" name="card_prefix" inputmode="numeric" maxlength="16" value="<?php echo Utils::escapeHtml($authorMapping['card_prefix']); ?>" required>
                <small>按原样保存数字前缀；<code>011</code>、<code>0721</code> 等前导零不能省略。</small>
            </div>

            <div class="form-group">
                <label for="card_id_length">卡号总位数</label>
                <input type="number" id="card_id_length" name="card_id_length" min="1" max="16" value="<?php echo Utils::escapeHtml(isset($authorMapping['card_id_length']) ? $authorMapping['card_id_length'] : ''); ?>">
                <small>可选。卡号从左侧补零到该长度后再匹配前缀；数据库中的 <code>1000360</code> 按8位读取为 <code>01000360</code>。</small>
            </div>

            <div class="form-group">
                <label for="card_id_start">显式卡号起始值</label>
                <input type="number" id="card_id_start" name="card_id_start" min="0" value="<?php echo Utils::escapeHtml(isset($authorMapping['card_id_start']) ? $authorMapping['card_id_start'] : ''); ?>">
            </div>

            <div class="form-group">
                <label for="card_id_end">显式卡号结束值</label>
                <input type="number" id="card_id_end" name="card_id_end" min="0" value="<?php echo Utils::escapeHtml(isset($authorMapping['card_id_end']) ? $authorMapping['card_id_end'] : ''); ?>">
                <small>可选，但起止值必须成对填写。显式区间存在时优先于“前缀＋总位数”；本页只修改当前ID对应的区间。</small>
            </div>

            <div class="form-group">
                <label for="priority">优先级</label>
                <input type="number" id="priority" name="priority" value="<?php echo Utils::escapeHtml(isset($authorMapping['priority']) ? $authorMapping['priority'] : 100); ?>" required>
                <small>数值越大越优先；同优先级下会继续比较区间精度。</small>
            </div>

            <div class="form-group">
                <label for="author_name">作者名称</label>
                <input type="text" id="author_name" name="author_name" value="<?php echo Utils::escapeHtml($authorMapping['author_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="alias">作者别名</label>
                <input type="text" id="alias" name="alias" value="<?php echo Utils::escapeHtml(isset($authorMapping['alias']) ? $authorMapping['alias'] : ''); ?>">
            </div>

            <div class="form-group">
                <label for="contact">联系方式</label>
                <input type="text" id="contact" name="contact" value="<?php echo Utils::escapeHtml(isset($authorMapping['contact']) ? $authorMapping['contact'] : ''); ?>">
            </div>

            <div class="form-group">
                <label for="notes">备注</label>
                <textarea id="notes" name="notes" rows="3"><?php echo Utils::escapeHtml(isset($authorMapping['notes']) ? $authorMapping['notes'] : ''); ?></textarea>
            </div>

            <button type="submit" class="btn">保存修改</button>
            <a href="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=authors" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>
