<h2>作者管理</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-success">
        <?php echo Utils::escapeHtml($message); ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3>识别作者</h3>
    </div>
    <div class="card-body">
        <p>点击下方按钮，系统将自动从strings.conf文件中识别作者信息并导入。</p>
        <form action="<?php echo BASE_URL; ?>?controller=admin&action=identifyAuthors" method="post">
            <button type="submit" class="btn">识别作者</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3>添加作者</h3>
    </div>
    <div class="card-body">
        <form action="<?php echo BASE_URL; ?>?controller=admin&action=addAuthor" method="post">
            <div class="form-group">
                <label for="card_prefix">卡片前缀</label>
                <input type="text" id="card_prefix" name="card_prefix" required>
                <small>输入卡片ID的前缀，通常是前3位数字</small>
            </div>

            <div class="form-group">
                <label for="author_name">作者名称</label>
                <input type="text" id="author_name" name="author_name" required>
            </div>

            <div class="form-group">
                <label for="alias">作者别名</label>
                <input type="text" id="alias" name="alias">
                <small>可选，作者的其他名称</small>
            </div>

            <div class="form-group">
                <label for="contact">联系方式</label>
                <input type="text" id="contact" name="contact">
                <small>可选，作者的联系方式</small>
            </div>

            <div class="form-group">
                <label for="notes">备注</label>
                <textarea id="notes" name="notes" rows="3"></textarea>
                <small>可选，其他相关信息</small>
            </div>

            <button type="submit" class="btn">添加作者</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>作者列表</h3>
    </div>
    <div class="card-body">
        <?php if (empty($authorMappings)): ?>
            <div class="alert alert-info">
                暂无作者映射数据
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>卡片前缀</th>
                            <th>作者名称</th>
                            <th>别名</th>
                            <th>联系方式</th>
                            <th>备注</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($authorMappings as $mapping): ?>
                            <tr>
                                <td><?php echo Utils::escapeHtml($mapping['card_prefix']); ?></td>
                                <td><?php echo Utils::escapeHtml($mapping['author_name']); ?></td>
                                <td><?php echo Utils::escapeHtml($mapping['alias'] ?? ''); ?></td>
                                <td><?php echo Utils::escapeHtml($mapping['contact'] ?? ''); ?></td>
                                <td><?php echo Utils::escapeHtml($mapping['notes'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>?controller=admin&action=editAuthor&card_prefix=<?php echo urlencode($mapping['card_prefix']); ?>" class="btn btn-sm">编辑</a>
                                    <form action="<?php echo BASE_URL; ?>?controller=admin&action=deleteAuthor" method="post" style="display: inline;">
                                        <input type="hidden" name="card_prefix" value="<?php echo Utils::escapeHtml($mapping['card_prefix']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger delete-author-btn" onclick="return confirm('确定要删除这个作者映射吗？')">删除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
