<?php
/**
 * 卡组上传页面
 */
?>

<div class="deck-create-page">
    <h2>上传卡组</h2>
    
    <div class="back-link">
        <a href="<?php echo BASE_URL; ?>?controller=deck">← 返回卡组列表</a>
    </div>

    <!-- 普通用户上传表单 -->
    <div class="card">
        <h3>上传单个卡组</h3>
        <form action="<?php echo BASE_URL; ?>?controller=deck&action=store" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="deck_name">卡组名称 <span class="required">*</span></label>
                <input type="text" id="deck_name" name="deck_name" class="form-control" required maxlength="100" placeholder="请输入卡组名称">
            </div>

            <div class="form-group">
                <label for="ydk_file">上传YDK文件</label>
                <input type="file" id="ydk_file" name="ydk_file" class="form-control" accept=".ydk">
                <small class="form-text">支持YDK格式的卡组文件</small>
            </div>

            <div class="form-group">
                <label for="ydk_content">或粘贴YDK内容</label>
                <textarea id="ydk_content" name="ydk_content" class="form-control" rows="15" placeholder="#created by ...
#main
卡片ID
...
#extra
卡片ID
...
!side
卡片ID
..."></textarea>
                <small class="form-text">如果同时上传文件和粘贴内容，将优先使用文件</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">上传卡组</button>
                <a href="<?php echo BASE_URL; ?>?controller=deck" class="btn btn-secondary">取消</a>
            </div>
        </form>
    </div>

    <?php if ($isAdmin): ?>
    <!-- 管理员批量上传表单 -->
    <div class="card" style="margin-top: 30px;">
        <h3>批量上传卡组（管理员）</h3>
        <p class="form-text">用于更新卡展示，可一次粘贴多个卡组内容，系统将自动识别并创建多个卡组。</p>
        
        <form action="<?php echo BASE_URL; ?>?controller=deck&action=storeBatch" method="post">
            <div class="form-group">
                <label for="batch_content">批量YDK内容</label>
                <textarea id="batch_content" name="batch_content" class="form-control" rows="20" placeholder="粘贴多个卡组的YDK内容，每个卡组以#created或#main开头"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">批量上传</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<style>
.deck-create-page .card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.deck-create-page .card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.deck-create-page .form-group {
    margin-bottom: 20px;
}

.deck-create-page label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.deck-create-page .required {
    color: #dc3545;
}

.deck-create-page .form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.deck-create-page textarea.form-control {
    font-family: monospace;
    resize: vertical;
}

.deck-create-page .form-text {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.deck-create-page .form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.back-link {
    margin-bottom: 20px;
}

.back-link a {
    color: #666;
    text-decoration: none;
}

.back-link a:hover {
    color: #333;
}
</style>

