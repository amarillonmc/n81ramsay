<h2>系统配置管理</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo Utils::escapeHtml($message); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo BASE_URL; ?>?controller=admin&amp;action=config">
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>配置项</th>
                    <th>值</th>
                    <th>描述</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($configs as $key => $conf): ?>
                    <tr>
                        <td><code><?php echo Utils::escapeHtml($key); ?></code></td>
                        <td>
                            <input type="text" class="form-control" name="config[<?php echo Utils::escapeHtml($key); ?>]" value="<?php echo Utils::escapeHtml($conf['value']); ?>" <?php echo $canEdit ? '' : 'readonly'; ?>>
                        </td>
                        <td><?php echo Utils::escapeHtml($conf['description']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($canEdit): ?>
        <button type="submit" class="btn btn-primary">保存配置</button>
    <?php endif; ?>
</form>
