<h2>投票概览</h2>

<div class="vote-actions" style="margin-bottom: 20px;">
    <?php if (defined('ADVANCED_VOTING_ENABLED') && ADVANCED_VOTING_ENABLED): ?>
        <a href="<?php echo BASE_URL; ?>?controller=vote&action=createAdvanced" class="btn btn-primary">发起高级投票</a>
    <?php endif; ?>
</div>

<?php if (empty($votes)): ?>
    <div class="alert alert-info">
        暂无投票
    </div>
<?php else: ?>
    <?php
    // 按周期分组投票
    $votesByCycle = [];
    foreach ($votes as $vote) {
        $cycle = $vote['vote_cycle'];
        if (!isset($votesByCycle[$cycle])) {
            $votesByCycle[$cycle] = [];
        }
        $votesByCycle[$cycle][] = $vote;
    }

    // 获取当前周期
    $currentCycle = Database::getInstance()->getCurrentVoteCycle();
    ?>

    <?php foreach ($votesByCycle as $cycle => $cycleVotes): ?>
        <div class="vote-cycle-section">
            <h3 class="vote-cycle-header" data-cycle="<?php echo $cycle; ?>">
                <span class="toggle-icon"><?php echo ($cycle == $currentCycle) ? '▼' : '►'; ?></span>
                投票周期 <?php echo $cycle; ?>
                <?php if ($cycle == $currentCycle): ?>
                    <span class="current-cycle-badge">当前周期</span>
                <?php endif; ?>
            </h3>

            <div class="vote-cycle-content" style="display: <?php echo ($cycle == $currentCycle) ? 'block' : 'none'; ?>;">
                <div class="card-grid">
                    <?php foreach ($cycleVotes as $vote): ?>
                        <div class="card-item <?php echo $vote['is_closed'] ? 'closed' : ''; ?> <?php echo $vote['is_series_vote'] ? 'series-vote' : ''; ?> <?php echo $vote['is_advanced_vote'] ? 'advanced-vote' : ''; ?>">
                            <a href="<?php echo BASE_URL; ?>?controller=vote&id=<?php echo $vote['vote_link']; ?>">
                                <div class="card-image-container">
                                    <img src="<?php echo $vote['card']['image_path']; ?>" alt="<?php echo Utils::escapeHtml($vote['card']['name']); ?>" class="<?php echo $vote['is_closed'] ? 'grayscale' : ''; ?>">
                                    <?php if ($vote['is_series_vote']): ?>
                                        <div class="series-overlay">
                                            <div class="series-indicator">系列</div>
                                            <div class="series-cards-count"><?php
                                                // 获取系列卡片数量
                                                if (isset($vote['series_card_count'])) {
                                                    echo $vote['series_card_count'] . '张卡片';
                                                } else {
                                                    echo "多张卡片";
                                                }
                                            ?></div>
                                        </div>
                                    <?php elseif ($vote['is_advanced_vote']): ?>
                                        <div class="advanced-overlay">
                                            <div class="advanced-indicator">高级</div>
                                            <div class="advanced-cards-count"><?php
                                                // 获取高级投票卡片数量
                                                if (isset($vote['advanced_card_count'])) {
                                                    echo $vote['advanced_card_count'] . '张卡片';
                                                } else {
                                                    echo "多张卡片";
                                                }
                                            ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-item-body">
                                    <div class="card-item-title">
                                        <?php if ($vote['is_series_vote']): ?>
                                            <div class="series-vote-title">
                                                <span class="series-name"><?php echo Utils::escapeHtml($vote['card']['setcode_text']); ?></span>
                                                <span class="series-vote-label">系列投票</span>
                                            </div>
                                            <div class="representative-card">
                                                代表卡片: <?php echo Utils::escapeHtml($vote['card']['name']); ?>
                                            </div>
                                        <?php elseif ($vote['is_advanced_vote']): ?>
                                            <div class="advanced-vote-title">
                                                <span class="advanced-vote-label">高级投票</span>
                                            </div>
                                            <div class="representative-card">
                                                代表卡片: <?php echo Utils::escapeHtml($vote['card']['name']); ?>
                                            </div>
                                        <?php else: ?>
                                            <?php echo Utils::escapeHtml($vote['card']['name']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>ID: <?php echo $vote['card']['id']; ?></div>
                                    <div>环境: <?php echo Utils::escapeHtml($vote['environment']['text']); ?></div>
                                    <?php if ($vote['is_series_vote']): ?>
                                        <div class="series-info">系列代码: 0x<?php echo dechex($vote['card']['setcode']); ?></div>
                                    <?php endif; ?>
                                    <div>状态:
                                        <?php if ($vote['is_closed']): ?>
                                            <span class="text-muted">已关闭</span>
                                        <?php else: ?>
                                            <span class="text-success">进行中</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="vote-stats-mini">
                                        <span class="forbidden"><?php echo $vote['stats'][0]; ?></span> /
                                        <span class="limited"><?php echo $vote['stats'][1]; ?></span> /
                                        <span class="semi-limited"><?php echo $vote['stats'][2]; ?></span> /
                                        <span class="unlimited"><?php echo $vote['stats'][3]; ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>?controller=vote&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 为周期标题添加点击事件
        var headers = document.querySelectorAll('.vote-cycle-header');
        headers.forEach(function(header) {
            header.addEventListener('click', function() {
                var content = this.nextElementSibling;
                var icon = this.querySelector('.toggle-icon');

                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    icon.textContent = '▼';
                } else {
                    content.style.display = 'none';
                    icon.textContent = '►';
                }
            });
        });
    });
    </script>

    <style>
    .vote-cycle-section {
        margin-bottom: 20px;
    }

    .vote-cycle-header {
        background-color: #f5f5f5;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        user-select: none;
        margin-bottom: 10px;
    }

    .vote-cycle-header:hover {
        background-color: #e9e9e9;
    }

    .toggle-icon {
        margin-right: 10px;
        display: inline-block;
        width: 15px;
    }

    .current-cycle-badge {
        background-color: #28a745;
        color: white;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 0.8em;
        margin-left: 10px;
    }

    .vote-cycle-content {
        padding: 0 15px;
    }

    /* 已关闭投票的灰度效果 */
    .grayscale {
        filter: grayscale(100%);
        opacity: 0.8;
        transition: filter 0.3s, opacity 0.3s;
    }

    .card-item.closed {
        opacity: 0.9;
        background-color: #f8f8f8;
        transition: opacity 0.3s, background-color 0.3s;
    }

    .card-item.closed:hover {
        opacity: 1;
        background-color: #fff;
    }

    .card-item.closed:hover .grayscale {
        filter: grayscale(50%);
        opacity: 0.9;
    }

    /* 系列投票样式 */
    .card-image-container {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .series-overlay {
        position: absolute;
        top: 5px;
        right: 5px;
        background: linear-gradient(135deg, #ff6b35, #ff8c42);
        color: white;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 0.7em;
        font-weight: bold;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        z-index: 10;
        min-width: 50px;
    }

    .series-indicator {
        font-size: 0.9em;
        margin-bottom: 1px;
    }

    .series-cards-count {
        font-size: 0.7em;
        opacity: 0.9;
    }

    .series-vote-title {
        margin-bottom: 5px;
    }

    .series-name {
        font-weight: bold;
        color: #ff6b35;
        font-size: 1.1em;
        display: block;
        margin-bottom: 2px;
    }

    .series-vote-label {
        background-color: #ff6b35;
        color: white;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 0.7em;
        font-weight: bold;
        display: inline-block;
    }

    .representative-card {
        font-size: 0.85em;
        color: #666;
        margin-top: 3px;
    }

    .series-info {
        font-size: 0.9em;
        color: #555;
        font-style: italic;
    }

    /* 系列投票卡片的特殊边框效果 */
    .card-item.series-vote {
        border: 2px solid #ff6b35;
        border-radius: 8px;
        background: linear-gradient(145deg, #fff, #fef8f5);
    }

    .card-item.series-vote:hover {
        box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    /* 系列投票的图片叠加效果 */
    .series-vote .card-image-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg,
            transparent 0%,
            transparent 70%,
            rgba(255, 107, 53, 0.1) 70%,
            rgba(255, 107, 53, 0.2) 100%);
        pointer-events: none;
        z-index: 5;
    }

    /* 高级投票样式 */
    .advanced-overlay {
        position: absolute;
        top: 5px;
        right: 5px;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 0.7em;
        font-weight: bold;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        z-index: 10;
        min-width: 50px;
    }

    .advanced-indicator {
        font-size: 0.9em;
        margin-bottom: 1px;
    }

    .advanced-cards-count {
        font-size: 0.7em;
        opacity: 0.9;
    }

    .advanced-vote-title {
        margin-bottom: 5px;
    }

    .advanced-vote-label {
        background-color: #007bff;
        color: white;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 0.7em;
        font-weight: bold;
        display: inline-block;
    }

    /* 高级投票卡片的特殊边框效果 */
    .card-item.advanced-vote {
        border: 2px solid #007bff;
        border-radius: 8px;
        background: linear-gradient(145deg, #fff, #f0f8ff);
    }

    .card-item.advanced-vote:hover {
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }

    /* 高级投票的图片叠加效果 */
    .advanced-vote .card-image-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg,
            transparent 0%,
            transparent 70%,
            rgba(0, 123, 255, 0.1) 70%,
            rgba(0, 123, 255, 0.2) 100%);
        pointer-events: none;
        z-index: 5;
    }
    </style>
<?php endif; ?>
