<h2>投票管理</h2>

<?php if (empty($votes)): ?>
    <div class="alert alert-info">
        暂无有效投票
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>卡片ID</th>
                    <th>卡名</th>
                    <th>环境</th>
                    <th>投票状态</th>
                    <th>发起人</th>
                    <th>发起时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($votes as $vote): ?>
                    <tr>
                        <td><?php echo $vote['card_id']; ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>card/detail?id=<?php echo $vote['card_id']; ?>">
                                <?php echo Utils::escapeHtml($vote['card']['name']); ?>
                            </a>
                        </td>
                        <td><?php echo Utils::escapeHtml($vote['environment']['text']); ?></td>
                        <td>
                            <div>禁止: <?php echo $vote['stats'][0]; ?></div>
                            <div>限制: <?php echo $vote['stats'][1]; ?></div>
                            <div>准限制: <?php echo $vote['stats'][2]; ?></div>
                            <div>无限制: <?php echo $vote['stats'][3]; ?></div>
                        </td>
                        <td><?php echo Utils::escapeHtml($vote['initiator_id']); ?></td>
                        <td><?php echo Utils::formatDatetime($vote['created_at']); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>vote/<?php echo $vote['vote_link']; ?>" class="btn btn-sm">查看</a>
                            <form action="<?php echo BASE_URL; ?>admin/closeVote" method="post" style="display: inline;">
                                <input type="hidden" name="vote_id" value="<?php echo $vote['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger close-vote-btn">关闭</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>admin/votes?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>
