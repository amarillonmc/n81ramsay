<h2>卡片检索</h2>

<div class="card">
    <div class="card-body">
        <form id="search-form" action="<?php echo BASE_URL; ?>" method="get">
            <input type="hidden" name="controller" value="card">
            <input type="hidden" name="action" value="search">
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
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3>卡片列表 (<?php echo $pagination['total']; ?>)</h3>
                <div class="per-page-selector">
                    <form id="per-page-form" action="<?php echo BASE_URL; ?>" method="get">
                        <input type="hidden" name="db" value="<?php echo urlencode($selectedDb); ?>">
                        <label for="per_page">每页显示：</label>
                        <select id="per_page" name="per_page" onchange="document.getElementById('per-page-form').submit();">
                            <?php foreach ($perPageOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo $pagination['per_page'] == $option ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
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

            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination">
                    <ul>
                        <?php if ($pagination['page'] > 1): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>?db=<?php echo urlencode($selectedDb); ?>&page=1&per_page=<?php echo $pagination['per_page']; ?>">首页</a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>?db=<?php echo urlencode($selectedDb); ?>&page=<?php echo $pagination['page'] - 1; ?>&per_page=<?php echo $pagination['per_page']; ?>">上一页</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        // 显示页码
                        $startPage = max(1, $pagination['page'] - 2);
                        $endPage = min($pagination['total_pages'], $pagination['page'] + 2);

                        // 确保显示至少5个页码（如果有足够的页数）
                        if ($endPage - $startPage < 4 && $pagination['total_pages'] > 4) {
                            if ($startPage == 1) {
                                $endPage = min($pagination['total_pages'], 5);
                            } elseif ($endPage == $pagination['total_pages']) {
                                $startPage = max(1, $pagination['total_pages'] - 4);
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li <?php echo $i == $pagination['page'] ? 'class="active"' : ''; ?>>
                                <a href="<?php echo BASE_URL; ?>?db=<?php echo urlencode($selectedDb); ?>&page=<?php echo $i; ?>&per_page=<?php echo $pagination['per_page']; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>?db=<?php echo urlencode($selectedDb); ?>&page=<?php echo $pagination['page'] + 1; ?>&per_page=<?php echo $pagination['per_page']; ?>">下一页</a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>?db=<?php echo urlencode($selectedDb); ?>&page=<?php echo $pagination['total_pages']; ?>&per_page=<?php echo $pagination['per_page']; ?>">末页</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php elseif (isset($selectedDb)): ?>
    <div class="alert alert-info">
        没有找到卡片
    </div>
<?php endif; ?>
