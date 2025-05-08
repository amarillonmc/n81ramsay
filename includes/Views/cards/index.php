<h2>卡片检索</h2>

<div class="card">
    <div class="card-body">
        <form id="search-form" action="<?php echo BASE_URL; ?>card/search" method="get">
            <div class="form-group">
                <label for="keyword">搜索卡片</label>
                <input type="text" id="keyword" name="keyword" placeholder="输入卡片ID或卡名">
            </div>
            <button type="submit" class="btn">搜索</button>
        </form>
    </div>
</div>

<?php if (!empty($dbFiles)): ?>
    <div class="card">
        <div class="card-header">
            <h3>数据库文件</h3>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs">
                <?php foreach ($dbFiles as $index => $dbFile): ?>
                    <?php $fileName = basename($dbFile); ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $selectedDb === $dbFile ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>?db=<?php echo urlencode($dbFile); ?>">
                            <?php echo $fileName; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($cards)): ?>
    <h3>卡片列表 (<?php echo count($cards); ?>)</h3>

    <div class="card-grid">
        <?php foreach ($cards as $card): ?>
            <div class="card-item">
                <a href="<?php echo BASE_URL; ?>card/detail?id=<?php echo $card['id']; ?>">
                    <img src="<?php echo BASE_URL . $card['image_path']; ?>" alt="<?php echo Utils::escapeHtml($card['name']); ?>">
                    <div class="card-item-body">
                        <div class="card-item-title"><?php echo Utils::escapeHtml($card['name']); ?></div>
                        <div>ID: <?php echo $card['id']; ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif (isset($selectedDb)): ?>
    <div class="alert alert-info">
        没有找到卡片
    </div>
<?php endif; ?>
