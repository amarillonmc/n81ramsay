<h2>投票概览</h2>

<?php if (empty($votes)): ?>
    <div class="alert alert-info">
        暂无投票
    </div>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($votes as $vote): ?>
            <div class="card-item <?php echo $vote['is_closed'] ? 'closed' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>vote/<?php echo $vote['vote_link']; ?>">
                    <img src="<?php echo BASE_URL . $vote['card']['image_path']; ?>" alt="<?php echo Utils::escapeHtml($vote['card']['name']); ?>">
                    <div class="card-item-body">
                        <div class="card-item-title"><?php echo Utils::escapeHtml($vote['card']['name']); ?></div>
                        <div>ID: <?php echo $vote['card']['id']; ?></div>
                        <div>环境: <?php echo Utils::escapeHtml($vote['environment']['text']); ?></div>
                        <div>周期: <?php echo $vote['vote_cycle']; ?></div>
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
    
    <?php if ($totalPages > 1): ?>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>vote?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>
