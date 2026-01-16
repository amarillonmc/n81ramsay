<?php
/**
 * 卡组详情页面
 */

// 获取TCG卡图辅助函数
function getDeckCardImage($cardId, $cardInfo) {
    // 如果有卡片信息且有图片路径，使用该路径
    if ($cardInfo && isset($cardInfo['image_path']) && !empty($cardInfo['image_path'])) {
        return $cardInfo['image_path'];
    }
    
    // 检查TCG卡图路径配置
    $tcgImagePath = defined('TCG_CARD_IMAGE_PATH') ? TCG_CARD_IMAGE_PATH : '';
    
    if (!empty($tcgImagePath) && is_dir($tcgImagePath)) {
        $imagePaths = [
            $tcgImagePath . '/' . $cardId . '.jpg',
            $tcgImagePath . '/' . $cardId . '.png'
        ];

        foreach ($imagePaths as $path) {
            if (file_exists($path)) {
                return BASE_URL . 'tcg_pics/' . basename($path);
            }
        }
    }

    return BASE_URL . 'assets/images/card_back.jpg';
}

// 获取卡片名称
function getDeckCardName($cardId, $cardInfo) {
    if ($cardInfo && isset($cardInfo['name'])) {
        return $cardInfo['name'];
    }
    return '未知卡片 #' . $cardId;
}
?>

<div class="deck-detail-page">
    <!-- 标签页（如果有多个相关卡组） -->
    <?php if (!empty($relatedDecks) && count($relatedDecks) > 1): ?>
    <div class="deck-tabs">
        <?php foreach ($relatedDecks as $rDeck): ?>
            <a href="<?php echo BASE_URL; ?>?controller=deck&action=detail&id=<?php echo $rDeck['id']; ?>" 
               class="deck-tab <?php echo $rDeck['id'] == $deck['id'] ? 'active' : ''; ?>">
                <?php echo Utils::escapeHtml($rDeck['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 卡组标题和操作 -->
    <div class="deck-header">
        <h2><?php echo Utils::escapeHtml($deck['name']); ?></h2>
        <div class="deck-actions">
            <a href="<?php echo BASE_URL; ?>?controller=deck&action=download&id=<?php echo $deck['id']; ?>" class="btn btn-primary">
                下载YDK
            </a>
            <button type="button" class="btn btn-secondary" onclick="copyDeckLink()">
                复制链接
            </button>
            <?php if ($canDelete): ?>
            <a href="<?php echo BASE_URL; ?>?controller=deck&action=delete&id=<?php echo $deck['id']; ?>" 
               class="btn btn-danger" 
               onclick="return confirm('确定要删除此卡组吗？')">
                删除卡组
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="deck-meta-info">
        <span>上传者: <?php echo Utils::escapeHtml($deck['uploader_name']); ?></span>
        <span>上传时间: <?php echo Utils::formatDatetime($deck['created_at']); ?></span>
        <span>主卡组: <?php echo count($deck['main_deck']); ?>张</span>
        <span>额外卡组: <?php echo count($deck['extra_deck']); ?>张</span>
        <?php if (count($deck['side_deck']) > 0): ?>
        <span>副卡组: <?php echo count($deck['side_deck']); ?>张</span>
        <?php endif; ?>
    </div>

    <!-- 卡组展示区（左右分栏） -->
    <div class="deck-display">
        <!-- 左侧：卡图展示 -->
        <div class="deck-images">
            <h3>卡图预览</h3>
            
            <!-- 主卡组 -->
            <div class="deck-section">
                <h4>主卡组 (<?php echo count($deck['main_deck']); ?>)</h4>
                <div class="card-grid">
                    <?php foreach ($deck['main_deck'] as $cardId): ?>
                        <?php $cardInfo = isset($cardInfoMap[$cardId]) ? $cardInfoMap[$cardId] : null; ?>
                        <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $cardId; ?>" 
                           class="card-thumb" title="<?php echo getDeckCardName($cardId, $cardInfo); ?>">
                            <img src="<?php echo getDeckCardImage($cardId, $cardInfo); ?>" 
                                 alt="<?php echo getDeckCardName($cardId, $cardInfo); ?>"
                                 loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 额外卡组 -->
            <?php if (!empty($deck['extra_deck'])): ?>
            <div class="deck-section">
                <h4>额外卡组 (<?php echo count($deck['extra_deck']); ?>)</h4>
                <div class="card-grid">
                    <?php foreach ($deck['extra_deck'] as $cardId): ?>
                        <?php $cardInfo = isset($cardInfoMap[$cardId]) ? $cardInfoMap[$cardId] : null; ?>
                        <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $cardId; ?>" 
                           class="card-thumb" title="<?php echo getDeckCardName($cardId, $cardInfo); ?>">
                            <img src="<?php echo getDeckCardImage($cardId, $cardInfo); ?>" 
                                 alt="<?php echo getDeckCardName($cardId, $cardInfo); ?>"
                                 loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 副卡组 -->
            <?php if (!empty($deck['side_deck'])): ?>
            <div class="deck-section">
                <h4>副卡组 (<?php echo count($deck['side_deck']); ?>)</h4>
                <div class="card-grid">
                    <?php foreach ($deck['side_deck'] as $cardId): ?>
                        <?php $cardInfo = isset($cardInfoMap[$cardId]) ? $cardInfoMap[$cardId] : null; ?>
                        <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $cardId; ?>" 
                           class="card-thumb" title="<?php echo getDeckCardName($cardId, $cardInfo); ?>">
                            <img src="<?php echo getDeckCardImage($cardId, $cardInfo); ?>" 
                                 alt="<?php echo getDeckCardName($cardId, $cardInfo); ?>"
                                 loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 右侧：文字卡名列表 -->
        <div class="deck-text-list">
            <h3>卡片列表</h3>

            <!-- 主卡组 -->
            <div class="text-section">
                <h4>主卡组 (<?php echo count($deck['main_deck']); ?>)</h4>
                <ul class="card-name-list">
                    <?php
                    $mainDeckCounts = array_count_values($deck['main_deck']);
                    foreach ($mainDeckCounts as $cardId => $count):
                        $cardInfo = isset($cardInfoMap[$cardId]) ? $cardInfoMap[$cardId] : null;
                    ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $cardId; ?>">
                                <?php echo getDeckCardName($cardId, $cardInfo); ?>
                            </a>
                            <?php if ($count > 1): ?>
                                <span class="card-count">x<?php echo $count; ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- 额外卡组 -->
            <?php if (!empty($deck['extra_deck'])): ?>
            <div class="text-section">
                <h4>额外卡组 (<?php echo count($deck['extra_deck']); ?>)</h4>
                <ul class="card-name-list">
                    <?php
                    $extraDeckCounts = array_count_values($deck['extra_deck']);
                    foreach ($extraDeckCounts as $cardId => $count):
                        $cardInfo = isset($cardInfoMap[$cardId]) ? $cardInfoMap[$cardId] : null;
                    ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $cardId; ?>">
                                <?php echo getDeckCardName($cardId, $cardInfo); ?>
                            </a>
                            <?php if ($count > 1): ?>
                                <span class="card-count">x<?php echo $count; ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- 副卡组 -->
            <?php if (!empty($deck['side_deck'])): ?>
            <div class="text-section">
                <h4>副卡组 (<?php echo count($deck['side_deck']); ?>)</h4>
                <ul class="card-name-list">
                    <?php
                    $sideDeckCounts = array_count_values($deck['side_deck']);
                    foreach ($sideDeckCounts as $cardId => $count):
                        $cardInfo = isset($cardInfoMap[$cardId]) ? $cardInfoMap[$cardId] : null;
                    ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $cardId; ?>">
                                <?php echo getDeckCardName($cardId, $cardInfo); ?>
                            </a>
                            <?php if ($count > 1): ?>
                                <span class="card-count">x<?php echo $count; ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 评论区 -->
    <div class="deck-comments">
        <h3>评论区 (<?php echo count($comments); ?>)</h3>

        <!-- 评论表单 -->
        <form action="<?php echo BASE_URL; ?>?controller=deck&action=comment" method="post" class="comment-form">
            <input type="hidden" name="deck_id" value="<?php echo $deck['id']; ?>">
            <div class="form-group">
                <textarea name="comment" class="form-control" rows="3" placeholder="发表评论..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">发表评论</button>
        </form>

        <!-- 评论列表 -->
        <div class="comment-list">
            <?php if (empty($comments)): ?>
                <div class="no-comments">暂无评论</div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <span class="comment-author"><?php echo Utils::escapeHtml($comment['user_name']); ?></span>
                            <span class="comment-time"><?php echo Utils::getRelativeTime($comment['created_at']); ?></span>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(Utils::escapeHtml($comment['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="back-link">
        <a href="<?php echo BASE_URL; ?>?controller=deck">← 返回卡组列表</a>
    </div>
</div>

<script>
function copyDeckLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(function() {
        alert('链接已复制到剪贴板');
    }, function() {
        prompt('复制此链接:', url);
    });
}
</script>

