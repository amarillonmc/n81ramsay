<h2>召唤词一览</h2>

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
        由于原始目录权限问题，系统正在使用临时目录存储召唤词文件。<br>
        当前文件路径：<?php echo Utils::escapeHtml(DIALOGUES_FILE_PATH); ?><br>
        原始路径：<?php echo Utils::escapeHtml($originalPath); ?><br>
        <strong>建议：</strong>请联系系统管理员修复目录权限问题。
    </div>
<?php endif; ?>

<?php if (!file_exists(DIALOGUES_FILE_PATH)): ?>
    <div class="alert alert-warning">
        <strong>召唤词文件不存在</strong><br>
        文件路径：<?php echo Utils::escapeHtml(DIALOGUES_FILE_PATH); ?><br>
        目前没有任何召唤词数据。
    </div>
<?php endif; ?>

<div class="action-buttons">
    <a href="<?php echo BASE_URL; ?>?controller=dialogue&action=submit" class="btn">投稿召唤词</a>
</div>

<div class="card">
    <div class="card-header">
        <h3>现有召唤词列表</h3>
    </div>
    <div class="card-body">
        <?php if (empty($dialogueCards)): ?>
            <div class="alert alert-info">
                暂无召唤词数据
            </div>
        <?php else: ?>
            <div class="dialogue-list">
                <?php foreach ($dialogueCards as $item): ?>
                    <div class="dialogue-item card mb-3">
                        <div class="card-body">
                            <div class="dialogue-header">
                                <h4>
                                    <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $item['card']['id']; ?>" class="card-link">
                                        [<?php echo Utils::escapeHtml($item['card']['id']); ?>] <?php echo Utils::escapeHtml($item['card']['name']); ?>
                                    </a>
                                </h4>
                                <div class="card-info">
                                    <span class="card-type"><?php echo Utils::escapeHtml($item['card']['type_text']); ?></span>
                                    <?php if (!empty($item['card']['race_text'])): ?>
                                        <span class="card-race"><?php echo Utils::escapeHtml($item['card']['race_text']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['card']['attribute_text'])): ?>
                                        <span class="card-attribute"><?php echo Utils::escapeHtml($item['card']['attribute_text']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="dialogue-content">
                                <?php foreach ($item['dialogues'] as $dialogue): ?>
                                    <div class="dialogue-text">
                                        <i class="dialogue-icon">💬</i>
                                        <span><?php echo nl2br(Utils::escapeHtml($dialogue)); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.action-buttons {
    margin-bottom: 20px;
    text-align: right;
}

.dialogue-item {
    border-left: 4px solid #28a745;
}

.dialogue-header h4 {
    margin: 0 0 10px 0;
}

.card-link {
    color: #007bff;
    text-decoration: none;
}

.card-link:hover {
    text-decoration: underline;
}

.card-info {
    margin-bottom: 15px;
}

.card-info span {
    display: inline-block;
    margin-right: 15px;
    padding: 2px 8px;
    background-color: #f8f9fa;
    border-radius: 3px;
    font-size: 0.9em;
}

.card-type {
    background-color: #e3f2fd !important;
    color: #1976d2;
}

.card-race {
    background-color: #f3e5f5 !important;
    color: #7b1fa2;
}

.card-attribute {
    background-color: #fff3e0 !important;
    color: #f57c00;
}

.dialogue-content {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.dialogue-text {
    display: flex;
    align-items: flex-start;
    margin-bottom: 10px;
}

.dialogue-text:last-child {
    margin-bottom: 0;
}

.dialogue-icon {
    margin-right: 10px;
    font-size: 1.2em;
    flex-shrink: 0;
}

.dialogue-text span {
    line-height: 1.5;
    word-wrap: break-word;
}
</style>
