<h2>卡片排行榜</h2>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <span>卡片排行榜 - 生成时间：<?php echo $rankingData['generated_time']; ?></span>

            <?php if ($this->userModel->hasPermission(1)): ?>
                <div>
                    <a href="<?php echo BASE_URL; ?>?controller=card_ranking&action=update&time_range=<?php echo $timeRange; ?>" class="btn btn-primary">更新排行榜</a>
                    <a href="<?php echo BASE_URL; ?>?controller=card_ranking&action=clearCache" class="btn btn-warning ml-2">清除缓存</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-options">
            <form action="<?php echo BASE_URL; ?>" method="get" class="form-inline">
                <input type="hidden" name="controller" value="card_ranking">

                <div class="form-group">
                    <label for="time_range">时间范围：</label>
                    <select id="time_range" name="time_range" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($timeRangeOptions as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $timeRange === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group ml-3">
                    <label for="limit">热门卡片显示：</label>
                    <select id="limit" name="limit" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($limitOptions as $value): ?>
                            <option value="<?php echo $value; ?>" <?php echo $limit === $value ? 'selected' : ''; ?>>
                                前<?php echo $value; ?>名
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group ml-3">
                    <label for="detail_limit">详细统计显示：</label>
                    <select id="detail_limit" name="detail_limit" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($detailLimitOptions as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $detailLimit === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group ml-3">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="diy_only" value="1" <?php echo $diyOnly ? 'checked' : ''; ?> onchange="this.form.submit()">
                            只看DIY卡排名
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <div class="top-cards">
            <h3>热门卡片</h3>
            <div class="card-grid">
                <?php foreach ($rankingData['top_cards'] as $index => $card): ?>
                    <div class="card-item">
                        <div class="rank">#<?php echo $index + 1; ?></div>
                        <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $card['id']; ?>">
                            <div class="card-item-body">
                                <div class="card-item-title"><?php echo Utils::escapeHtml($card['name']); ?></div>
                                <div>ID: <?php echo $card['id']; ?></div>
                                <div>使用率: <?php echo $card['usage_rate']; ?>%</div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-responsive mt-4">
            <h3>详细统计</h3>
            <p>总计分析卡组数量: <?php echo $rankingData['total_decks']; ?></p>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>显示: <?php echo $detailLimitOptions[$detailLimit]; ?></span>
                <span>总计卡片数量: <?php echo count($rankingData['all_cards']); ?></span>
            </div>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>排名</th>
                        <th>卡片ID</th>
                        <th>卡名</th>
                        <th>类别</th>
                        <th>投入1数量</th>
                        <th>投入2数量</th>
                        <th>投入3数量</th>
                        <th>SIDE投入数量</th>
                        <th>使用率</th>
                        <?php if (!$diyOnly): ?>
                        <th>卡片类型</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailCards as $index => $card): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo $card['id']; ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $card['id']; ?>">
                                    <?php echo Utils::escapeHtml($card['name']); ?>
                                </a>
                            </td>
                            <td><?php echo Utils::escapeHtml($card['type_text']); ?></td>
                            <td><?php echo $card['main_count_1']; ?></td>
                            <td><?php echo $card['main_count_2']; ?></td>
                            <td><?php echo $card['main_count_3']; ?></td>
                            <td><?php echo $card['side_count']; ?></td>
                            <td><?php echo $card['usage_rate']; ?>%</td>
                            <?php if (!$diyOnly): ?>
                            <td><?php echo isset($card['is_tcg']) && $card['is_tcg'] ? 'TCG卡' : 'DIY卡'; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
