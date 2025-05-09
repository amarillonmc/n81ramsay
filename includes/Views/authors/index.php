<div class="container">
    <h2>作者光荣榜</h2>
    
    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success">
            <?php echo Utils::escapeHtml($_GET['message']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span>作者光荣榜 - 生成时间：<?php echo $generatedTime; ?></span>
                
                <?php if ($this->userModel->hasPermission(1)): ?>
                    <a href="<?php echo BASE_URL; ?>?controller=author&action=update" class="btn btn-primary">更新榜单</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>排名</th>
                            <th>作者名称</th>
                            <th>投稿卡片数量</th>
                            <th>标准环境被禁卡数量</th>
                            <th>标准环境被禁卡百分比</th>
                            <th>标准环境系列禁止数量</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($authorStats)): ?>
                            <tr>
                                <td colspan="6" class="text-center">暂无数据</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($authorStats as $author => $stats): ?>
                                <tr<?php echo ($stats['banned_percentage'] > $highlightThreshold) ? ' class="table-danger"' : ''; ?>>
                                    <td><?php echo $stats['rank']; ?></td>
                                    <td><?php echo Utils::escapeHtml($author); ?></td>
                                    <td><?php echo $stats['total_cards']; ?></td>
                                    <td><?php echo $stats['banned_cards']; ?></td>
                                    <td><?php echo $stats['banned_percentage']; ?>%</td>
                                    <td><?php echo $stats['banned_series']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
