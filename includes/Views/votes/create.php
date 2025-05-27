<h2>发起投票</h2>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo Utils::escapeHtml($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><?php echo Utils::escapeHtml($card['name']); ?> (<?php echo $card['id']; ?>)</h3>
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

                <form id="create-vote-form" action="<?php echo BASE_URL; ?>?controller=vote&action=create" method="post">
                    <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">

                    <div class="form-group">
                        <label for="environment_id">选择环境</label>
                        <select id="environment_id" name="environment_id" required>
                            <option value="">-- 请选择环境 --</option>
                            <?php foreach ($environments as $env): ?>
                                <option value="<?php echo $env['id']; ?>" data-current-status="<?php echo $limitStatus[$env['id']]; ?>">
                                    <?php echo Utils::escapeHtml($env['text']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 当前禁限状态显示区域 -->
                    <div id="current-status-display" class="form-group" style="display: none;">
                        <div class="alert alert-info">
                            <strong>当前禁限状态：</strong>
                            <span id="current-status-text" class="status-badge"></span>
                        </div>
                        <?php if (!ALLOW_MEANINGLESS_VOTING): ?>
                        <div class="alert alert-warning">
                            <small>注意：系统不允许发起无意义投票（对卡片发起与其当前禁限状态相同的投票）</small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>选择禁限状态</label>
                        <div class="vote-option" data-status="0">
                            <label>
                                <input type="radio" name="status" value="0" required> 禁止
                                <span class="meaningless-indicator" style="display: none; color: #dc3545; font-size: 0.8em;">（无意义投票）</span>
                            </label>
                        </div>
                        <div class="vote-option" data-status="1">
                            <label>
                                <input type="radio" name="status" value="1"> 限制
                                <span class="meaningless-indicator" style="display: none; color: #dc3545; font-size: 0.8em;">（无意义投票）</span>
                            </label>
                        </div>
                        <div class="vote-option" data-status="2">
                            <label>
                                <input type="radio" name="status" value="2"> 准限制
                                <span class="meaningless-indicator" style="display: none; color: #dc3545; font-size: 0.8em;">（无意义投票）</span>
                            </label>
                        </div>
                        <div class="vote-option" data-status="3">
                            <label>
                                <input type="radio" name="status" value="3"> 无限制
                                <span class="meaningless-indicator" style="display: none; color: #dc3545; font-size: 0.8em;">（无意义投票）</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason">理由（可选）</label>
                        <textarea id="reason" name="reason" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="initiator_id">您的ID</label>
                        <input type="text" id="initiator_id" name="initiator_id" required>
                    </div>

                    <button type="submit" class="btn">发起投票</button>
                    <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $card['id']; ?>" class="btn btn-secondary">返回卡片详情</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const environmentSelect = document.getElementById('environment_id');
    const currentStatusDisplay = document.getElementById('current-status-display');
    const currentStatusText = document.getElementById('current-status-text');
    const voteOptions = document.querySelectorAll('.vote-option');
    const meaninglessIndicators = document.querySelectorAll('.meaningless-indicator');
    const allowMeaninglessVoting = <?php echo ALLOW_MEANINGLESS_VOTING ? 'true' : 'false'; ?>;

    // 状态文本映射
    const statusTexts = {
        '0': '禁止',
        '1': '限制',
        '2': '准限制',
        '3': '无限制'
    };

    // 状态CSS类映射
    const statusClasses = {
        '0': 'forbidden',
        '1': 'limited',
        '2': 'semi-limited',
        '3': 'unlimited'
    };

    environmentSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];

        if (selectedOption.value) {
            const currentStatus = selectedOption.getAttribute('data-current-status');

            // 显示当前状态
            currentStatusDisplay.style.display = 'block';
            currentStatusText.textContent = statusTexts[currentStatus];
            currentStatusText.className = 'status-badge ' + statusClasses[currentStatus];

            // 处理无意义投票标识
            meaninglessIndicators.forEach(indicator => {
                indicator.style.display = 'none';
            });

            if (!allowMeaninglessVoting) {
                voteOptions.forEach(option => {
                    const optionStatus = option.getAttribute('data-status');
                    const indicator = option.querySelector('.meaningless-indicator');
                    const radioInput = option.querySelector('input[type="radio"]');

                    if (optionStatus === currentStatus) {
                        indicator.style.display = 'inline';
                        radioInput.disabled = true;
                        option.style.opacity = '0.6';
                    } else {
                        indicator.style.display = 'none';
                        radioInput.disabled = false;
                        option.style.opacity = '1';
                    }
                });
            }
        } else {
            // 隐藏当前状态显示
            currentStatusDisplay.style.display = 'none';

            // 重置所有选项
            meaninglessIndicators.forEach(indicator => {
                indicator.style.display = 'none';
            });

            voteOptions.forEach(option => {
                const radioInput = option.querySelector('input[type="radio"]');
                radioInput.disabled = false;
                option.style.opacity = '1';
            });
        }
    });
});
</script>
