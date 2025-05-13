<div class="container">
    <h2>作者详情：<?php echo Utils::escapeHtml($authorName); ?></h2>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span>作者信息</span>
                <a href="<?php echo BASE_URL; ?>?controller=author" class="btn btn-secondary">返回作者光荣榜</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table">
                        <tr>
                            <th>作者名称</th>
                            <td><?php echo Utils::escapeHtml($authorName); ?></td>
                        </tr>
                        <tr>
                            <th>投稿卡片数量</th>
                            <td><?php echo $author['total_cards']; ?></td>
                        </tr>
                        <tr>
                            <th>标准环境被禁卡数量</th>
                            <td><?php echo $author['banned_cards']; ?></td>
                        </tr>
                        <tr>
                            <th>标准环境被禁卡百分比</th>
                            <td><?php echo $author['banned_percentage']; ?>%</td>
                        </tr>
                        <tr>
                            <th>标准环境系列禁止数量</th>
                            <td><?php echo $author['banned_series']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab === 'all' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>?controller=author&action=detail&name=<?php echo urlencode($authorName); ?>&tab=all">全部卡片</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab === 'banned' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>?controller=author&action=detail&name=<?php echo urlencode($authorName); ?>&tab=banned">被禁卡片</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <form action="<?php echo BASE_URL; ?>" method="get" class="form-inline">
                    <input type="hidden" name="controller" value="author">
                    <input type="hidden" name="action" value="detail">
                    <input type="hidden" name="name" value="<?php echo Utils::escapeHtml($authorName); ?>">
                    <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                    <div class="form-group mr-2">
                        <label for="per_page" class="mr-2">每页显示：</label>
                        <select name="per_page" id="per_page" class="form-control" onchange="this.form.submit()">
                            <option value="30" <?php echo $pagination['per_page'] == 30 ? 'selected' : ''; ?>>30张卡</option>
                            <option value="50" <?php echo $pagination['per_page'] == 50 ? 'selected' : ''; ?>>50张卡</option>
                            <option value="0" <?php echo $pagination['per_page'] == 0 ? 'selected' : ''; ?>>全部显示</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>卡片ID</th>
                            <th>卡名</th>
                            <th>类别</th>
                            <th>属性</th>
                            <th>种族</th>
                            <th>ATK</th>
                            <th>DEF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cards)): ?>
                            <tr>
                                <td colspan="7" class="text-center">暂无数据</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cards as $card): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $card['id']; ?>">
                                            <?php echo $card['id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo Utils::escapeHtml($card['name']); ?></td>
                                    <td><?php echo Utils::escapeHtml($card['type_text']); ?></td>
                                    <td><?php echo Utils::escapeHtml($card['attribute_text']); ?></td>
                                    <td><?php echo Utils::escapeHtml($card['race_text']); ?></td>
                                    <td><?php echo isset($card['atk']) ? $card['atk'] : '-'; ?></td>
                                    <td><?php echo isset($card['def']) ? $card['def'] : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="分页导航">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo BASE_URL; ?>?controller=author&action=detail&name=<?php echo urlencode($authorName); ?>&tab=<?php echo $tab; ?>&page=1&per_page=<?php echo $pagination['per_page']; ?>">首页</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo BASE_URL; ?>?controller=author&action=detail&name=<?php echo urlencode($authorName); ?>&tab=<?php echo $tab; ?>&page=<?php echo $pagination['page'] - 1; ?>&per_page=<?php echo $pagination['per_page']; ?>">上一页</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        // 显示页码
                        $startPage = max(1, $pagination['page'] - 2);
                        $endPage = min($pagination['total_pages'], $pagination['page'] + 2);

                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $pagination['page'] ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo BASE_URL; ?>?controller=author&action=detail&name=<?php echo urlencode($authorName); ?>&tab=<?php echo $tab; ?>&page=<?php echo $i; ?>&per_page=<?php echo $pagination['per_page']; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo BASE_URL; ?>?controller=author&action=detail&name=<?php echo urlencode($authorName); ?>&tab=<?php echo $tab; ?>&page=<?php echo $pagination['page'] + 1; ?>&per_page=<?php echo $pagination['per_page']; ?>">下一页</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo BASE_URL; ?>?controller=author&action=detail&name=<?php echo urlencode($authorName); ?>&tab=<?php echo $tab; ?>&page=<?php echo $pagination['total_pages']; ?>&per_page=<?php echo $pagination['per_page']; ?>">末页</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
