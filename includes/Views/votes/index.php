<h2>投票概览</h2>

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
                        <div class="card-item <?php echo $vote['is_closed'] ? 'closed' : ''; ?>">
                            <a href="<?php echo BASE_URL; ?>?controller=vote&id=<?php echo $vote['vote_link']; ?>">
                                <img src="<?php echo $vote['card']['image_path']; ?>" alt="<?php echo Utils::escapeHtml($vote['card']['name']); ?>" class="<?php echo $vote['is_closed'] ? 'grayscale' : ''; ?>">
                                <div class="card-item-body">
                                    <div class="card-item-title">
                                        <?php echo Utils::escapeHtml($vote['card']['name']); ?>
                                        <?php if ($vote['is_series_vote']): ?>
                                            <span class="series-vote-badge">系列投票</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>ID: <?php echo $vote['card']['id']; ?></div>
                                    <div>环境: <?php echo Utils::escapeHtml($vote['environment']['text']); ?></div>
                                    <?php if ($vote['is_series_vote']): ?>
                                        <div>系列: <?php echo Utils::escapeHtml($vote['card']['setcode_text']); ?></div>
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

    /* 系列投票标识 */
    .series-vote-badge {
        background-color: #ff6b35;
        color: white;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 0.7em;
        margin-left: 5px;
        font-weight: bold;
    }
    </style>
<?php endif; ?>
