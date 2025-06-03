<h2>投票详情</h2>

<div class="card">
    <div class="card-header">
        <h3>
            <?php echo Utils::escapeHtml($card['name']); ?> (<?php echo $card['id']; ?>)
            <?php if ($vote['is_series_vote']): ?>
                <span class="series-vote-badge">系列投票</span>
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
                            <div class="vote-record" id="vote-record-<?php echo $record['id']; ?>">
                                <div class="vote-record-header">
                                    <div class="vote-record-user">
                                        <strong><?php echo Utils::escapeHtml($record['user_id']); ?></strong>
                                        <?php if (!empty($record['identifier'])): ?>
                                            <span class="vote-record-identifier">#<?php echo $record['identifier']; ?></span>
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
</script>
