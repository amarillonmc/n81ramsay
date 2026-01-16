<?php
/**
 * 卡组列表页面
 */
?>

<div class="deck-sharing-page">
    <div class="page-header">
        <h2>卡组分享</h2>
        <?php if ($canUpload): ?>
            <a href="<?php echo BASE_URL; ?>?controller=deck&action=create" class="btn btn-primary">上传卡组</a>
        <?php else: ?>
            <span class="btn btn-disabled" title="<?php echo Utils::escapeHtml($uploadDeniedReason); ?>">上传卡组</span>
        <?php endif; ?>
    </div>

    <?php if (empty($decks)): ?>
        <div class="alert alert-info">暂无卡组分享</div>
    <?php else: ?>
        <div class="deck-list">
            <?php foreach ($decks as $deck): ?>
                <div class="deck-card">
                    <div class="deck-card-header">
                        <a href="<?php echo BASE_URL; ?>?controller=deck&action=detail&id=<?php echo $deck['id']; ?>" class="deck-name">
                            <?php echo Utils::escapeHtml($deck['name']); ?>
                        </a>
                        <?php if ($deck['is_admin_deck']): ?>
                            <span class="badge badge-admin">管理员</span>
                        <?php endif; ?>
                    </div>
                    <div class="deck-card-body">
                        <div class="deck-info">
                            <span class="deck-card-count">
                                主卡组: <?php echo count($deck['main_deck']); ?>张 | 
                                额外: <?php echo count($deck['extra_deck']); ?>张
                                <?php if (count($deck['side_deck']) > 0): ?>
                                    | 副卡组: <?php echo count($deck['side_deck']); ?>张
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="deck-meta">
                            <span class="deck-uploader">上传者: <?php echo Utils::escapeHtml($deck['uploader_name']); ?></span>
                            <span class="deck-time"><?php echo Utils::getRelativeTime($deck['created_at']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 分页 -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['page'] > 1): ?>
                    <a href="<?php echo BASE_URL; ?>?controller=deck&page=<?php echo $pagination['page'] - 1; ?>" class="page-link">« 上一页</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $pagination['page'] - 2);
                $endPage = min($pagination['total_pages'], $pagination['page'] + 2);
                ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $pagination['page']): ?>
                        <span class="page-link current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>?controller=deck&page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                    <a href="<?php echo BASE_URL; ?>?controller=deck&page=<?php echo $pagination['page'] + 1; ?>" class="page-link">下一页 »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

