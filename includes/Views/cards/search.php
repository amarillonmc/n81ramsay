<h2>搜索结果</h2>

<div class="card">
    <div class="card-body">
        <form id="search-form" action="<?php echo BASE_URL; ?>" method="get">
            <input type="hidden" name="controller" value="card">
            <input type="hidden" name="action" value="search">
            <div class="form-group">
                <label for="keyword">搜索卡片</label>
                <input type="text" id="keyword" name="keyword" value="<?php echo isset($_GET['keyword']) ? Utils::escapeHtml($_GET['keyword']) : ''; ?>" placeholder="输入卡片ID或卡名">
            </div>
            <button type="submit" class="btn">搜索</button>
        </form>
    </div>
</div>

<?php if (!empty($cards)): ?>
    <h3>搜索结果 (<?php echo count($cards); ?>)</h3>

    <div class="card-grid">
        <?php foreach ($cards as $card): ?>
            <div class="card-item">
                <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $card['id']; ?>">
                    <img src="<?php echo $card['image_path']; ?>" alt="<?php echo Utils::escapeHtml($card['name']); ?>">
                    <div class="card-item-body">
                        <div class="card-item-title"><?php echo Utils::escapeHtml($card['name']); ?></div>
                        <div>ID: <?php echo $card['id']; ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        没有找到匹配的卡片
    </div>
<?php endif; ?>
