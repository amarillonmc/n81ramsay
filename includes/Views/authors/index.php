<div class="container">
    <h2>作者光荣榜</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success">
            <?php echo Utils::escapeHtml($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['author_debug_message'])): ?>
        <div class="alert alert-success">
            <?php
                echo Utils::escapeHtml($_SESSION['author_debug_message']);
                // 显示后清除消息
                unset($_SESSION['author_debug_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span>作者光荣榜 - 生成时间：<?php echo $generatedTime; ?></span>

                <?php if ($this->userModel->hasPermission(1)): ?>
                    <div class="btn-group">
                        <a href="<?php echo BASE_URL; ?>?controller=author&action=update" class="btn btn-primary">更新榜单</a>
                        <a href="<?php echo BASE_URL; ?>?controller=author&action=clearCache" class="btn btn-warning">清除缓存</a>
                        <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                            <a href="<?php echo BASE_URL; ?>?controller=author&action=debug" class="btn btn-warning">生成调试内容</a>
                        <?php endif; ?>
                    </div>
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
                                <?php
                                // 根据作者类型设置不同的样式
                                $rowClass = '';
                                if ($stats['is_unknown']) {
                                    $rowClass = 'table-secondary';
                                } else if ($stats['banned_percentage'] > $highlightThreshold) {
                                    $rowClass = 'table-danger';
                                }
                                ?>
                                <tr<?php echo !empty($rowClass) ? ' class="' . $rowClass . '"' : ''; ?>>
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
