<h2>投票详情</h2>

<div class="card">
    <div class="card-header">
        <h3>
            <?php echo Utils::escapeHtml($card['name']); ?> (<?php echo $card['id']; ?>)
            <?php if ($vote['is_series_vote']): ?>
                <span class="series-vote-badge">系列投票</span>
            <?php elseif ($vote['is_advanced_vote']): ?>
                <span class="advanced-vote-badge">高级投票</span>
            <?php endif; ?>
        </h3>
        <p>环境: <?php echo Utils::escapeHtml($environment['text']); ?></p>
        <p>投票周期: <?php echo $vote['vote_cycle']; ?></p>
        <p>发起人: <?php echo Utils::escapeHtml($vote['initiator_id']); ?></p>
        <p>发起时间: <?php echo Utils::formatDatetime($vote['created_at']); ?></p>
        <?php if ($vote['is_series_vote']): ?>
            <div class="alert alert-warning">
                <strong>系列投票说明：</strong>此投票将对 "<?php echo Utils::escapeHtml($card['setcode_text']); ?>" 系列下的所有卡片统一应用投票结果。
            </div>
        <?php elseif ($vote['is_advanced_vote']): ?>
            <div class="alert alert-info">
                <strong>高级投票说明：</strong>此投票涉及多张卡片，您可以对每张卡片分别选择投票状态。
                <?php if (!empty($vote['card_ids'])): ?>
                    <?php
                    $cardIds = json_decode($vote['card_ids'], true);
                    if (is_array($cardIds)) {
                        echo '涉及 ' . count($cardIds) . ' 张卡片。';
                    }
                    ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 显示当前禁限状态 -->
        <div class="current-limit-status">
            <p>当前禁限状态: <span class="status-badge <?php echo Utils::getLimitStatusClass($currentLimitStatus); ?>">
                <?php echo Utils::getLimitStatusText($currentLimitStatus); ?>
            </span></p>
        </div>

        <?php if ($vote['is_closed']): ?>
            <div class="alert alert-info">此投票已关闭</div>
        <?php endif; ?>
    </div>

    <!-- 高级投票卡片列表 - 移到顶部显示 -->
    <?php if ($vote['is_advanced_vote'] && !empty($advancedCards)): ?>
        <div class="card-body advanced-cards-section">
            <h4 class="mb-3">
                <i class="fas fa-layer-group text-primary"></i>
                涉及的卡片 (<?php echo count($advancedCards); ?>张)
            </h4>
            <div class="advanced-cards-grid">
                <?php foreach ($advancedCards as $index => $advancedCard): ?>
                    <div class="advanced-card-item" data-card-id="<?php echo $advancedCard['id']; ?>">
                        <div class="card-preview-container">
                            <img src="<?php echo $advancedCard['image_path']; ?>"
                                 alt="<?php echo Utils::escapeHtml($advancedCard['name']); ?>"
                                 class="card-thumbnail"
                                 onmouseover="showCardPreview(this, <?php echo $advancedCard['id']; ?>)"
                                 onmouseout="hideCardPreview()">
                            <div class="card-info">
                                <div class="card-name" title="<?php echo Utils::escapeHtml($advancedCard['name']); ?>">
                                    <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $advancedCard['id']; ?>" target="_blank">
                                        <?php echo Utils::escapeHtml($advancedCard['name']); ?>
                                    </a>
                                </div>
                                <div class="card-id">ID: <?php echo $advancedCard['id']; ?></div>
                                <div class="card-current-status">
                                    <span class="status-badge <?php echo Utils::getLimitStatusClass($advancedCard['current_limit_status']); ?>">
                                        <?php echo Utils::getLimitStatusText($advancedCard['current_limit_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
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

                <?php if ($vote['is_advanced_vote']): ?>
                    <!-- 高级投票：显示每张卡片的统计 -->
                    <div class="advanced-vote-stats">
                        <h4>各卡片投票统计</h4>
                        <?php foreach ($advancedCards as $advancedCard): ?>
                            <div class="card-vote-stats">
                                <div class="card-stats-header">
                                    <img src="<?php echo $advancedCard['image_path']; ?>"
                                         alt="<?php echo Utils::escapeHtml($advancedCard['name']); ?>"
                                         class="card-stats-thumbnail">
                                    <div class="card-stats-info">
                                        <div class="card-stats-name"><?php echo Utils::escapeHtml($advancedCard['name']); ?></div>
                                        <div class="card-stats-id">ID: <?php echo $advancedCard['id']; ?></div>
                                    </div>
                                </div>
                                <div class="vote-stats">
                                    <div class="vote-stat-item">
                                        <div class="vote-stat-label">禁止</div>
                                        <div class="vote-stat-count forbidden"><?php echo $advancedCard['stats'][0]; ?></div>
                                    </div>
                                    <div class="vote-stat-item">
                                        <div class="vote-stat-label">限制</div>
                                        <div class="vote-stat-count limited"><?php echo $advancedCard['stats'][1]; ?></div>
                                    </div>
                                    <div class="vote-stat-item">
                                        <div class="vote-stat-label">准限制</div>
                                        <div class="vote-stat-count semi-limited"><?php echo $advancedCard['stats'][2]; ?></div>
                                    </div>
                                    <div class="vote-stat-item">
                                        <div class="vote-stat-label">无限制</div>
                                        <div class="vote-stat-count unlimited"><?php echo $advancedCard['stats'][3]; ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- 普通投票：显示整体统计 -->
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
                <?php endif; ?>

                <?php if (!$vote['is_closed']): ?>
                    <?php if ($vote['is_advanced_vote']): ?>
                        <!-- 高级投票表单 - 支持分别投票 -->
                        <form id="advanced-vote-form" action="<?php echo BASE_URL; ?>?controller=vote&action=submitAdvanced&id=<?php echo $vote['vote_link']; ?>" method="post">
                            <div class="form-group">
                                <label for="user_id">您的ID</label>
                                <input type="text" id="user_id" name="user_id" required>
                            </div>

                            <div class="form-group">
                                <label for="comment">评论（可选）</label>
                                <textarea id="comment" name="comment" rows="3"></textarea>
                            </div>

                            <div class="advanced-voting-section">
                                <h5>对每张卡片的投票</h5>
                                <p class="text-muted">请为每张卡片选择您认为合适的禁限状态：</p>

                                <div class="cards-voting-grid">
                                    <?php foreach ($advancedCards as $advancedCard): ?>
                                        <div class="card-voting-item" data-card-id="<?php echo $advancedCard['id']; ?>">
                                            <div class="card-voting-header">
                                                <img src="<?php echo $advancedCard['image_path']; ?>"
                                                     alt="<?php echo Utils::escapeHtml($advancedCard['name']); ?>"
                                                     class="card-mini-thumbnail">
                                                <div class="card-voting-info">
                                                    <div class="card-voting-name"><?php echo Utils::escapeHtml($advancedCard['name']); ?></div>
                                                    <div class="card-voting-id">ID: <?php echo $advancedCard['id']; ?></div>
                                                    <div class="card-voting-current">
                                                        当前: <span class="status-badge <?php echo Utils::getLimitStatusClass($advancedCard['current_limit_status']); ?>">
                                                            <?php echo Utils::getLimitStatusText($advancedCard['current_limit_status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-voting-options">
                                                <div class="voting-option">
                                                    <label>
                                                        <input type="radio" name="card_votes[<?php echo $advancedCard['id']; ?>]" value="0" required>
                                                        <span class="option-text">禁止</span>
                                                        <?php if ($advancedCard['current_limit_status'] > 0): ?>
                                                            <span class="change-indicator further">进一步限制</span>
                                                        <?php elseif ($advancedCard['current_limit_status'] == 0): ?>
                                                            <span class="change-indicator no-change">不变</span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                                <div class="voting-option">
                                                    <label>
                                                        <input type="radio" name="card_votes[<?php echo $advancedCard['id']; ?>]" value="1">
                                                        <span class="option-text">限制</span>
                                                        <?php if ($advancedCard['current_limit_status'] > 1): ?>
                                                            <span class="change-indicator further">进一步限制</span>
                                                        <?php elseif ($advancedCard['current_limit_status'] == 1): ?>
                                                            <span class="change-indicator no-change">不变</span>
                                                        <?php elseif ($advancedCard['current_limit_status'] < 1): ?>
                                                            <span class="change-indicator relaxed">限制缓和</span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                                <div class="voting-option">
                                                    <label>
                                                        <input type="radio" name="card_votes[<?php echo $advancedCard['id']; ?>]" value="2">
                                                        <span class="option-text">准限制</span>
                                                        <?php if ($advancedCard['current_limit_status'] > 2): ?>
                                                            <span class="change-indicator further">进一步限制</span>
                                                        <?php elseif ($advancedCard['current_limit_status'] == 2): ?>
                                                            <span class="change-indicator no-change">不变</span>
                                                        <?php elseif ($advancedCard['current_limit_status'] < 2): ?>
                                                            <span class="change-indicator relaxed">限制缓和</span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                                <div class="voting-option">
                                                    <label>
                                                        <input type="radio" name="card_votes[<?php echo $advancedCard['id']; ?>]" value="3">
                                                        <span class="option-text">无限制</span>
                                                        <?php if ($advancedCard['current_limit_status'] == 3): ?>
                                                            <span class="change-indicator no-change">不变</span>
                                                        <?php elseif ($advancedCard['current_limit_status'] < 3): ?>
                                                            <span class="change-indicator relaxed">限制缓和</span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">提交投票</button>
                                <button type="button" class="btn btn-secondary" onclick="selectAllSame()">全部设为相同</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- 普通投票表单 -->
                        <form id="vote-form" action="<?php echo BASE_URL; ?>?controller=vote&id=<?php echo $vote['vote_link']; ?>" method="post">
                            <div class="form-group">
                                <label>您的投票</label>
                                <div>
                                    <label>
                                        <input type="radio" name="status" value="0" required> 禁止
                                        <?php
                                        $changeType = '';
                                        if ($currentLimitStatus > 0) {
                                            $changeType = '<span class="change-type further-restriction">进一步限制</span>';
                                        } elseif ($currentLimitStatus == 0) {
                                            $changeType = '<span class="change-type no-change">不变</span>';
                                        }
                                        echo $changeType;
                                        ?>
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="radio" name="status" value="1"> 限制
                                        <?php
                                        $changeType = '';
                                        if ($currentLimitStatus > 1) {
                                            $changeType = '<span class="change-type further-restriction">进一步限制</span>';
                                        } elseif ($currentLimitStatus == 1) {
                                            $changeType = '<span class="change-type no-change">不变</span>';
                                        } elseif ($currentLimitStatus < 1) {
                                            $changeType = '<span class="change-type relaxed-restriction">限制缓和</span>';
                                        }
                                        echo $changeType;
                                        ?>
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="radio" name="status" value="2"> 准限制
                                        <?php
                                        $changeType = '';
                                        if ($currentLimitStatus > 2) {
                                            $changeType = '<span class="change-type further-restriction">进一步限制</span>';
                                        } elseif ($currentLimitStatus == 2) {
                                            $changeType = '<span class="change-type no-change">不变</span>';
                                        } elseif ($currentLimitStatus < 2) {
                                            $changeType = '<span class="change-type relaxed-restriction">限制缓和</span>';
                                        }
                                        echo $changeType;
                                        ?>
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="radio" name="status" value="3"> 无限制
                                        <?php
                                        $changeType = '';
                                        if ($currentLimitStatus == 3) {
                                            $changeType = '<span class="change-type no-change">不变</span>';
                                        } elseif ($currentLimitStatus < 3) {
                                            $changeType = '<span class="change-type relaxed-restriction">限制缓和</span>';
                                        }
                                        echo $changeType;
                                        ?>
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
                <?php endif; ?>

                <div class="vote-records">
                    <h4>投票记录 (<?php echo count($records); ?>)</h4>

                    <?php if (empty($records)): ?>
                        <div class="alert alert-info">暂无投票记录</div>
                    <?php else: ?>
                        <?php
                        // 获取当前用户权限信息
                        $auth = Auth::getInstance();
                        $isAdmin = $auth->isLoggedIn() && $auth->hasPermission(1);
                        $allowUserDeletion = defined('ALLOW_VOTE_DELETION') && ALLOW_VOTE_DELETION;
                        $currentIp = Utils::getClientIp();
                        ?>
                        <?php foreach ($records as $record): ?>
                            <?php
                            // 检查投票者是否被封禁
                            $voterBanModel = new VoterBan();
                            $ban = $voterBanModel->checkBan($record['identifier']);
                            $isBanned = $ban !== false;
                            $banLevel = $isBanned ? $ban['ban_level'] : 0;

                            // 对于等级1封禁，检查评论长度是否足够
                            $isWeakened = false;
                            if ($banLevel == 1) {
                                $minLength = defined('SERIES_VOTING_REASON_MIN_LENGTH') ? SERIES_VOTING_REASON_MIN_LENGTH : 400;
                                if (strlen($record['comment']) < $minLength) {
                                    $isWeakened = true;
                                }
                            }
                            ?>
                            <div class="vote-record <?php echo $isWeakened ? 'vote-record-weakened' : ''; ?>" id="vote-record-<?php echo $record['id']; ?>">
                                <div class="vote-record-header">
                                    <div class="vote-record-user">
                                        <strong><?php echo Utils::escapeHtml($record['user_id']); ?></strong>
                                        <?php if (!empty($record['identifier'])): ?>
                                            <span class="vote-record-identifier">#<?php echo $record['identifier']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($isBanned): ?>
                                            <span class="ban-indicator ban-level-<?php echo $banLevel; ?>" title="该投票者已被封禁">
                                                <?php if ($banLevel == 1): ?>
                                                    <i class="fas fa-exclamation-triangle"></i> 受限
                                                <?php elseif ($banLevel == 2): ?>
                                                    <i class="fas fa-ban"></i> 封禁
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($isWeakened): ?>
                                            <span class="weakened-indicator" title="该投票因理由不足而不计入统计">
                                                <i class="fas fa-eye-slash"></i> 不计入
                                            </span>
                                        <?php endif; ?>
                                        <span class="vote-record-time"><?php echo Utils::getRelativeTime($record['created_at']); ?></span>
                                    </div>
                                    <?php if ($vote['is_closed'] == 0): // 只有未关闭的投票才能删除记录 ?>
                                        <div class="vote-record-actions">
                                            <?php
                                            $canDelete = false;
                                            if ($isAdmin) {
                                                $canDelete = true;
                                            } elseif ($allowUserDeletion) {
                                                $currentIdentifier = Utils::generateVoterIdentifier($currentIp, $record['user_id']);
                                                if ($currentIdentifier === $record['identifier']) {
                                                    $canDelete = true;
                                                }
                                            }
                                            ?>
                                            <?php if ($canDelete): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-vote-btn"
                                                        data-record-id="<?php echo $record['id']; ?>"
                                                        data-vote-link="<?php echo Utils::escapeHtml($vote['vote_link']); ?>"
                                                        title="删除此投票记录">
                                                    <i class="fas fa-trash"></i> 删除
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="vote-record-status <?php echo Utils::getLimitStatusClass($record['status']); ?>">
                                    <?php echo Utils::getLimitStatusText($record['status']); ?>
                                    <?php if ($vote['is_advanced_vote'] && !empty($record['card_id'])): ?>
                                        <span class="vote-record-card-info">
                                            (针对卡片: <?php echo $record['card_id']; ?>
                                            <?php if (!empty($record['card_name'])): ?>
                                                - <?php echo Utils::escapeHtml($record['card_name']); ?>
                                            <?php endif; ?>)
                                        </span>
                                    <?php endif; ?>
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

<?php if ($vote['is_series_vote']): ?>
    <div class="card mt-3">
        <div class="card-header">
            <h4>系列中的卡片</h4>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleSeriesCards()">
                <span id="toggle-text">展开显示</span>
            </button>
        </div>
        <div class="card-body" id="series-cards-container" style="display: none;">
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
                    <tbody id="series-cards-tbody">
                        <tr>
                            <td colspan="7" class="text-center">
                                <button type="button" class="btn btn-primary" onclick="loadSeriesCards()">加载系列卡片</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    let seriesCardsLoaded = false;

    function toggleSeriesCards() {
        const container = document.getElementById('series-cards-container');
        const toggleText = document.getElementById('toggle-text');

        if (container.style.display === 'none') {
            container.style.display = 'block';
            toggleText.textContent = '收起显示';
        } else {
            container.style.display = 'none';
            toggleText.textContent = '展开显示';
        }
    }

    function loadSeriesCards() {
        if (seriesCardsLoaded) return;

        const setcode = <?php echo $card['setcode']; ?>;
        const tbody = document.getElementById('series-cards-tbody');

        // 显示加载中
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">加载中...</td></tr>';

        // 使用AJAX加载系列卡片数据
        const apiUrl = '<?php echo BASE_URL; ?>?controller=api&action=getSeriesCards&setcode=' + setcode;
        console.log('Loading series cards from:', apiUrl);

        fetch(apiUrl)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                console.log('Parsed data:', data);
                if (data.success && data.cards && data.cards.length > 0) {
                    let html = '';
                    data.cards.forEach(card => {
                        html += '<tr>';
                        html += '<td><a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=' + card.id + '">' + card.id + '</a></td>';
                        html += '<td>' + escapeHtml(card.name) + '</td>';
                        html += '<td>' + escapeHtml(card.type_text) + '</td>';
                        html += '<td>' + escapeHtml(card.attribute_text) + '</td>';
                        html += '<td>' + escapeHtml(card.race_text) + '</td>';
                        html += '<td>' + ((card.type & 1) > 0 ? (card.atk < 0 ? '?' : card.atk) : '-') + '</td>';
                        html += '<td>' + ((card.type & 1) > 0 ? (card.def < 0 ? '?' : card.def) : '-') + '</td>';
                        html += '</tr>';
                    });
                    tbody.innerHTML = html;
                } else {
                    const message = data.message || '暂无数据';
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">' + escapeHtml(message) + '</td></tr>';
                }
                seriesCardsLoaded = true;
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">加载失败: ' + escapeHtml(error.message) + '</td></tr>';
            });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
<?php endif; ?>



<style>
/* 系列投票标识 */
.series-vote-badge {
    background-color: #ff6b35;
    color: white;
    padding: 4px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-left: 10px;
    font-weight: bold;
}

/* 高级投票标识 */
.advanced-vote-badge {
    background-color: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-left: 10px;
    font-weight: bold;
}

/* 高级投票统计 */
.advanced-vote-stats {
    margin-bottom: 20px;
}

.card-vote-stats {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #dee2e6;
}

.card-stats-header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    gap: 10px;
}

.card-stats-thumbnail {
    width: 40px;
    height: 58px;
    object-fit: cover;
    border-radius: 4px;
}

.card-stats-info {
    flex: 1;
}

.card-stats-name {
    font-weight: bold;
    font-size: 1em;
    color: #007bff;
}

.card-stats-id {
    font-size: 0.85em;
    color: #666;
}

/* 高级投票卡片网格 */
.advanced-cards-section {
    background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
    border-radius: 8px;
    margin-bottom: 20px;
    border: 2px solid #007bff;
}

.advanced-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.advanced-card-item {
    background: white;
    border-radius: 8px;
    padding: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.advanced-card-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.card-preview-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-thumbnail {
    width: 60px;
    height: 87px;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
}

.card-info {
    flex: 1;
    min-width: 0;
}

.card-name {
    font-weight: bold;
    font-size: 0.9em;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-name a {
    color: #007bff;
    text-decoration: none;
}

.card-name a:hover {
    text-decoration: underline;
}

.card-id {
    font-size: 0.8em;
    color: #666;
    margin-bottom: 4px;
}

.card-current-status .status-badge {
    font-size: 0.7em;
    padding: 2px 6px;
}

/* 卡片预览悬浮窗 */
.card-preview-popup {
    position: fixed;
    z-index: 9999;
    background: white;
    border: 2px solid #007bff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    max-width: 350px;
    min-width: 250px;
    pointer-events: none;
}

.card-preview-popup .preview-info {
    width: 100%;
}

.card-preview-popup .preview-name {
    font-weight: bold;
    font-size: 1.2em;
    margin-bottom: 10px;
    color: #007bff;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 5px;
}

.card-preview-popup .preview-details {
    font-size: 0.9em;
    line-height: 1.4;
}

.card-preview-popup .preview-details > div:first-child {
    margin-bottom: 8px;
    color: #666;
    font-size: 0.85em;
}

.card-preview-popup .preview-desc {
    margin-top: 8px;
    font-size: 0.85em;
    color: #333;
    line-height: 1.5;
    max-height: 120px;
    overflow-y: auto;
    padding: 8px;
    background-color: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #007bff;
}

/* 投票记录样式 */
.vote-record-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.vote-record-actions {
    margin-left: 10px;
}

.delete-vote-btn {
    font-size: 0.8em;
    padding: 2px 6px;
}

/* 封禁和弱化显示样式 */
.vote-record-weakened {
    opacity: 0.6;
    background-color: #f8f9fa;
    border-left: 3px solid #ffc107;
}

.ban-indicator {
    font-size: 0.8em;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 5px;
}

.ban-indicator.ban-level-1 {
    background-color: #ffc107;
    color: #212529;
}

.ban-indicator.ban-level-2 {
    background-color: #dc3545;
    color: white;
}

.weakened-indicator {
    font-size: 0.8em;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 5px;
    background-color: #6c757d;
    color: white;
}

/* 高级投票表单样式 */
.advanced-voting-section {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.cards-voting-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.card-voting-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    transition: border-color 0.2s;
}

.card-voting-item:hover {
    border-color: #007bff;
}

.card-voting-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.card-mini-thumbnail {
    width: 40px;
    height: 58px;
    object-fit: cover;
    border-radius: 4px;
}

.card-voting-info {
    flex: 1;
}

.card-voting-name {
    font-weight: bold;
    font-size: 0.9em;
    margin-bottom: 2px;
}

.card-voting-id {
    font-size: 0.8em;
    color: #666;
    margin-bottom: 4px;
}

.card-voting-current {
    font-size: 0.8em;
}

.card-voting-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.voting-option {
    position: relative;
}

.voting-option label {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9em;
}

.voting-option label:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
}

.voting-option input[type="radio"] {
    margin-right: 8px;
}

.voting-option input[type="radio"]:checked + .option-text {
    font-weight: bold;
}

.voting-option label:has(input:checked) {
    background-color: #e3f2fd;
    border-color: #007bff;
    color: #007bff;
}

.change-indicator {
    font-size: 0.7em;
    padding: 2px 4px;
    border-radius: 3px;
    margin-left: 4px;
}

.change-indicator.further {
    background-color: #dc3545;
    color: white;
}

.change-indicator.no-change {
    background-color: #6c757d;
    color: white;
}

.change-indicator.relaxed {
    background-color: #28a745;
    color: white;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.form-actions .btn {
    padding: 10px 20px;
}

/* 高级投票记录样式 */
.vote-record-card-info {
    font-size: 0.8em;
    color: #666;
    margin-left: 8px;
    font-weight: normal;
}
</style>

<script>
// 删除投票记录功能
document.addEventListener('DOMContentLoaded', function() {
    // 为所有删除按钮添加事件监听器
    document.querySelectorAll('.delete-vote-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const recordId = this.getAttribute('data-record-id');
            const voteLink = this.getAttribute('data-vote-link');

            if (confirm('确定要删除这条投票记录吗？删除后该投票者可以重新投票。')) {
                deleteVoteRecord(recordId, voteLink);
            }
        });
    });
});

function deleteVoteRecord(recordId, voteLink) {
    // 显示加载状态
    const button = document.querySelector(`[data-record-id="${recordId}"]`);
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 删除中...';
    button.disabled = true;

    // 发送删除请求
    const formData = new FormData();
    formData.append('record_id', recordId);
    formData.append('vote_link', voteLink);

    fetch('<?php echo BASE_URL; ?>?controller=vote&action=deleteRecord', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 删除成功，移除该投票记录的DOM元素
            const recordElement = document.getElementById(`vote-record-${recordId}`);
            if (recordElement) {
                recordElement.style.transition = 'opacity 0.3s';
                recordElement.style.opacity = '0';
                setTimeout(() => {
                    recordElement.remove();
                    // 更新投票记录数量
                    updateVoteRecordCount();
                }, 300);
            }

            // 显示成功消息
            showMessage(data.message, 'success');
        } else {
            // 删除失败，恢复按钮状态
            button.innerHTML = originalText;
            button.disabled = false;

            // 显示错误消息
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);

        // 恢复按钮状态
        button.innerHTML = originalText;
        button.disabled = false;

        // 显示错误消息
        showMessage('删除失败，请稍后重试', 'error');
    });
}

function updateVoteRecordCount() {
    const recordsContainer = document.querySelector('.vote-records');
    const remainingRecords = recordsContainer.querySelectorAll('.vote-record').length;
    const countElement = recordsContainer.querySelector('h4');

    if (remainingRecords === 0) {
        // 如果没有记录了，显示提示信息
        const recordsDiv = recordsContainer.querySelector('.vote-records > div:last-child') || recordsContainer;
        recordsDiv.innerHTML = '<div class="alert alert-info">暂无投票记录</div>';
        countElement.textContent = '投票记录 (0)';
    } else {
        countElement.textContent = `投票记录 (${remainingRecords})`;
    }
}

function showMessage(message, type) {
    // 创建消息元素
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    messageDiv.style.position = 'fixed';
    messageDiv.style.top = '20px';
    messageDiv.style.right = '20px';
    messageDiv.style.zIndex = '9999';
    messageDiv.style.minWidth = '300px';

    messageDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // 添加到页面
    document.body.appendChild(messageDiv);

    // 3秒后自动移除
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 3000);
}

// 卡片预览功能
let previewPopup = null;
let previewTimeout = null;

function showCardPreview(imgElement, cardId) {
    // 清除之前的定时器
    if (previewTimeout) {
        clearTimeout(previewTimeout);
    }

    // 延迟显示预览，避免鼠标快速移动时频繁显示
    previewTimeout = setTimeout(() => {
        // 通过AJAX获取卡片详细信息
        getCardDetailFromAPI(cardId).then(cardData => {
            if (!cardData) return;

            // 重用现有的预览弹窗以减少DOM操作
            if (!previewPopup) {
                previewPopup = document.createElement('div');
                previewPopup.className = 'card-preview-popup';
                document.body.appendChild(previewPopup);
            }

            // 更新内容 - 显示卡名、ID和卡片效果
            previewPopup.innerHTML = `
                <div class="preview-info">
                    <div class="preview-name">${escapeHtml(cardData.name)}</div>
                    <div class="preview-details">
                        <div><strong>ID:</strong> ${cardData.id}</div>
                        <div class="preview-desc">${escapeHtml(cardData.desc || '无效果描述').replace(/\n/g, '<br>')}</div>
                    </div>
                </div>
            `;

            // 显示并定位预览弹窗
            previewPopup.style.display = 'block';
            positionPreview(imgElement, previewPopup);
        }).catch(error => {
            console.error('获取卡片信息失败:', error);
        });
    }, 300);
}

function hideCardPreview() {
    if (previewTimeout) {
        clearTimeout(previewTimeout);
        previewTimeout = null;
    }

    if (previewPopup) {
        previewPopup.style.display = 'none';
    }
}

function positionPreview(triggerElement, popup) {
    const rect = triggerElement.getBoundingClientRect();
    const popupRect = popup.getBoundingClientRect();

    let left = rect.right + 10;
    let top = rect.top;

    // 确保预览窗口不超出屏幕边界
    if (left + popupRect.width > window.innerWidth) {
        left = rect.left - popupRect.width - 10;
    }

    if (top + popupRect.height > window.innerHeight) {
        top = window.innerHeight - popupRect.height - 10;
    }

    if (top < 0) top = 10;
    if (left < 0) left = 10;

    popup.style.left = left + 'px';
    popup.style.top = top + 'px';
}

/**
 * 通过API获取卡片详细信息
 */
function getCardDetailFromAPI(cardId) {
    return fetch(`<?php echo BASE_URL; ?>?controller=api&action=getCardDetail&id=${cardId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data.card;
            } else {
                console.error('API错误:', data.message);
                return null;
            }
        })
        .catch(error => {
            console.error('网络错误:', error);
            return null;
        });
}

/**
 * HTML转义函数
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getCardData(cardId) {
    // 从页面中的高级投票卡片数据获取信息
    const cardElement = document.querySelector(`[data-card-id="${cardId}"]`);
    if (!cardElement) return null;

    const img = cardElement.querySelector('.card-thumbnail, .card-mini-thumbnail');
    const nameElement = cardElement.querySelector('.card-name a, .card-voting-name');
    const statusElement = cardElement.querySelector('.status-badge');

    if (!img || !nameElement) return null;

    return {
        id: cardId,
        name: nameElement.textContent.trim(),
        image_path: img.src,
        type_text: '卡片',
        current_status: statusElement ? statusElement.textContent.trim() : '未知'
    };
}

// 批量设置功能
function selectAllSame() {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;

    modal.innerHTML = `
        <div style="background: white; padding: 20px; border-radius: 8px; max-width: 300px;">
            <h4>批量设置投票状态</h4>
            <p>选择要为所有卡片设置的状态：</p>
            <div style="margin: 15px 0;">
                <label style="display: block; margin: 8px 0;">
                    <input type="radio" name="bulk_status" value="0"> 禁止
                </label>
                <label style="display: block; margin: 8px 0;">
                    <input type="radio" name="bulk_status" value="1"> 限制
                </label>
                <label style="display: block; margin: 8px 0;">
                    <input type="radio" name="bulk_status" value="2"> 准限制
                </label>
                <label style="display: block; margin: 8px 0;">
                    <input type="radio" name="bulk_status" value="3"> 无限制
                </label>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button onclick="applyBulkSetting()" style="margin-right: 10px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px;">确定</button>
                <button onclick="closeBulkModal()" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px;">取消</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    window.bulkModal = modal;
}

function applyBulkSetting() {
    const selectedStatus = document.querySelector('input[name="bulk_status"]:checked');
    if (!selectedStatus) {
        alert('请选择一个状态');
        return;
    }

    const status = selectedStatus.value;
    const cardVoteInputs = document.querySelectorAll('input[name^="card_votes["]');

    cardVoteInputs.forEach(input => {
        if (input.value === status) {
            input.checked = true;
        }
    });

    closeBulkModal();
}

function closeBulkModal() {
    if (window.bulkModal) {
        document.body.removeChild(window.bulkModal);
        window.bulkModal = null;
    }
}
</script>
