<h2>随机卡片展示</h2>

<?php if ($card): ?>
<div class="card">
    <div class="card-body">
        <table class="table">
            <tr><th>卡号</th><td><?php echo $card['id']; ?></td></tr>
            <tr><th>卡名</th><td><?php echo Utils::escapeHtml($card['name']); ?></td></tr>
            <tr><th>卡图</th><td><img src="<?php echo $card['image_path']; ?>" alt="<?php echo Utils::escapeHtml($card['name']); ?>" class="img-fluid"></td></tr>
            <tr><th>类别</th><td><?php echo Utils::escapeHtml($card['type_text']); ?></td></tr>
            <?php if (($card['type'] & 1) > 0): ?>
                <tr><th>种族</th><td><?php echo Utils::escapeHtml($card['race_text']); ?></td></tr>
                <tr><th>属性</th><td><?php echo Utils::escapeHtml($card['attribute_text']); ?></td></tr>
                <tr><th>等级/阶级/刻度</th><td><?php echo Utils::escapeHtml($card['level_text']); ?></td></tr>
                <tr><th>攻击力</th><td><?php echo $card['atk'] < 0 ? '?' : $card['atk']; ?></td></tr>
                <tr><th>守备力</th><td><?php echo $card['def'] < 0 ? '?' : $card['def']; ?></td></tr>
            <?php endif; ?>
            <tr><th>卡片描述</th><td><?php echo nl2br(Utils::escapeHtml($card['desc'])); ?></td></tr>
            <tr><th>卡片作者</th><td><?php echo !empty($card['author']) ? Utils::escapeHtml($card['author']) : '未知'; ?></td></tr>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($seriesCards)): ?>
<div class="card">
    <div class="card-header"><h3>同系列卡</h3></div>
    <div class="card-body">
        <div class="card-grid">
            <?php foreach ($seriesCards as $c): ?>
                <div class="card-item">
                    <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $c['id']; ?>">
                        <img src="<?php echo $c['image_path']; ?>" alt="<?php echo Utils::escapeHtml($c['name']); ?>">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($authorCards)): ?>
<div class="card">
    <div class="card-header"><h3>同作者卡</h3></div>
    <div class="card-body">
        <div class="card-grid">
            <?php foreach ($authorCards as $c): ?>
                <div class="card-item">
                    <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $c['id']; ?>">
                        <img src="<?php echo $c['image_path']; ?>" alt="<?php echo Utils::escapeHtml($c['name']); ?>">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<h2>搜索卡片</h2>
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
