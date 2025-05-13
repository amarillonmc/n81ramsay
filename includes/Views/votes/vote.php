<h2>投票详情</h2>

<div class="card">
    <div class="card-header">
        <h3><?php echo Utils::escapeHtml($card['name']); ?> (<?php echo $card['id']; ?>)</h3>
        <p>环境: <?php echo Utils::escapeHtml($environment['text']); ?></p>
        <p>投票周期: <?php echo $vote['vote_cycle']; ?></p>
        <p>发起人: <?php echo Utils::escapeHtml($vote['initiator_id']); ?></p>
        <p>发起时间: <?php echo Utils::formatDatetime($vote['created_at']); ?></p>
        <?php if ($vote['is_closed']): ?>
            <div class="alert alert-info">此投票已关闭</div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <img src="<?php echo $card['image_path']; ?>" alt="<?php echo Utils::escapeHtml($card['name']); ?>" class="img-fluid">
            </div>
            <div class="col-md-8">
                <table class="table">
                    <tr>
                        <th>卡号</th>
                        <td><?php echo $card['id']; ?></td>
                    </tr>
                    <tr>
                        <th>卡名</th>
                        <td><?php echo Utils::escapeHtml($card['name']); ?></td>
                    </tr>
                    <tr>
                        <th>系列</th>
                        <td><?php echo Utils::escapeHtml($card['setcode_text']); ?></td>
                    </tr>
                    <tr>
                        <th>类别</th>
                        <td><?php echo Utils::escapeHtml($card['type_text']); ?></td>
                    </tr>
                    <tr>
                        <th>卡片描述</th>
                        <td><?php echo nl2br(Utils::escapeHtml($card['desc'])); ?></td>
                    </tr>
                </table>

                <?php if (!empty($vote['reason'])): ?>
                    <div class="vote-info">
                        <h4>投票理由</h4>
                        <p><?php echo nl2br(Utils::escapeHtml($vote['reason'])); ?></p>
                    </div>
                <?php endif; ?>

                <div class="vote-stats">
                    <div class="vote-stat-item">
                        <div class="vote-stat-label">禁止</div>
                        <div class="vote-stat-count forbidden"><?php echo $stats[0]; ?></div>
                    </div>
                    <div class="vote-stat-item">
                        <div class="vote-stat-label">限制</div>
                        <div class="vote-stat-count limited"><?php echo $stats[1]; ?></div>
                    </div>
                    <div class="vote-stat-item">
                        <div class="vote-stat-label">准限制</div>
                        <div class="vote-stat-count semi-limited"><?php echo $stats[2]; ?></div>
                    </div>
                    <div class="vote-stat-item">
                        <div class="vote-stat-label">无限制</div>
                        <div class="vote-stat-count unlimited"><?php echo $stats[3]; ?></div>
                    </div>
                </div>

                <?php if (!$vote['is_closed']): ?>
                    <form id="vote-form" action="<?php echo BASE_URL; ?>?controller=vote&id=<?php echo $vote['vote_link']; ?>" method="post">
                        <div class="form-group">
                            <label>您的投票</label>
                            <div>
                                <label>
                                    <input type="radio" name="status" value="0" required> 禁止
                                </label>
                            </div>
                            <div>
                                <label>
                                    <input type="radio" name="status" value="1"> 限制
                                </label>
                            </div>
                            <div>
                                <label>
                                    <input type="radio" name="status" value="2"> 准限制
                                </label>
                            </div>
                            <div>
                                <label>
                                    <input type="radio" name="status" value="3"> 无限制
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="user_id">您的ID</label>
                            <input type="text" id="user_id" name="user_id" required>
                        </div>

                        <div class="form-group">
                            <label for="comment">评论（可选）</label>
                            <textarea id="comment" name="comment" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn">提交投票</button>
                    </form>
                <?php endif; ?>

                <div class="vote-records">
                    <h4>投票记录 (<?php echo count($records); ?>)</h4>

                    <?php if (empty($records)): ?>
                        <div class="alert alert-info">暂无投票记录</div>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <div class="vote-record">
                                <div class="vote-record-user">
                                    <strong><?php echo Utils::escapeHtml($record['user_id']); ?></strong>
                                    <?php if (!empty($record['identifier'])): ?>
                                        <span class="vote-record-identifier">#<?php echo $record['identifier']; ?></span>
                                    <?php endif; ?>
                                    <span class="vote-record-time"><?php echo Utils::getRelativeTime($record['created_at']); ?></span>
                                </div>
                                <div class="vote-record-status <?php echo Utils::getLimitStatusClass($record['status']); ?>">
                                    <?php echo Utils::getLimitStatusText($record['status']); ?>
                                </div>
                                <?php if (!empty($record['comment'])): ?>
                                    <div class="vote-record-comment">
                                        <?php echo nl2br(Utils::escapeHtml($record['comment'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="<?php echo BASE_URL; ?>?controller=vote" class="btn btn-secondary">返回投票列表</a>
    </div>
</div>
