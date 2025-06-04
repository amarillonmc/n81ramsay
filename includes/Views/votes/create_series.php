<h2>发起系列投票</h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo Utils::escapeHtml($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">系列投票说明</h5>
        <div class="alert alert-info">
            <p><strong>注意：</strong>系列投票将对该系列下的所有卡片统一应用投票结果。</p>
            <p><strong>当前严格度：</strong>
                <?php
                switch ($strictness) {
                    case 0:
                        echo '所有用户均可发起系列投票';
                        break;
                    case 1:
                        echo "需要填写至少 {$minReasonLength} 个字符的理由";
                        break;
                    case 2:
                        echo "发起人必须在系统作者列表中";
                        break;
                    case 3:
                        echo "发起人必须在系统作者列表中，且需要验证卡片作者";
                        break;
                }
                ?>
            </p>
        </div>

        <div class="row">
            <div class="col-md-4">
                <?php if (!$isTcgCard): ?>
                    <img src="<?php echo $card['image_path']; ?>" alt="<?php echo Utils::escapeHtml($card['name']); ?>" class="img-fluid">
                <?php else: ?>
                    <div class="tcg-card-placeholder">
                        <p>TCG卡片 - 无图片显示</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-8">
                <table class="table">
                    <tr>
                        <th>代表卡片</th>
                        <td><?php echo Utils::escapeHtml($card['name']); ?> (<?php echo $card['id']; ?>)</td>
                    </tr>
                    <tr>
                        <th>系列名称</th>
                        <td><?php echo Utils::escapeHtml($card['setcode_text']); ?></td>
                    </tr>
                    <tr>
                        <th>系列代码</th>
                        <td>0x<?php echo dechex($card['setcode']); ?></td>
                    </tr>
                    <tr>
                        <th>系列卡片数量</th>
                        <td><?php echo count($seriesCards); ?> 张</td>
                    </tr>
                </table>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>?controller=vote&action=createSeries" method="post">
            <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">

            <div class="form-group">
                <label for="environment_id">环境</label>
                <select name="environment_id" id="environment_id" class="form-control" required>
                    <option value="">请选择环境</option>
                    <?php foreach ($environments as $env): ?>
                        <option value="<?php echo $env['id']; ?>"><?php echo Utils::escapeHtml($env['text']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>投票状态</label>
                <div>
                    <label>
                        <input type="radio" name="status" value="0" required> 禁止
                    </label>
                </div>
                <div>
                    <label>
                        <input type="radio" name="status" value="1" required> 限制
                    </label>
                </div>
                <div>
                    <label>
                        <input type="radio" name="status" value="2" required> 准限制
                    </label>
                </div>
                <div>
                    <label>
                        <input type="radio" name="status" value="3" required> 无限制
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="reason">理由 <?php if ($strictness >= 1): ?><span class="text-danger">*（至少 <?php echo $minReasonLength; ?> 个字符）</span><?php endif; ?></label>
                <textarea name="reason" id="reason" class="form-control" rows="5" <?php if ($strictness >= 1): ?>required<?php endif; ?> placeholder="请详细说明发起系列投票的理由..."></textarea>
                <small class="form-text text-muted">当前字符数：<span id="reason-count">0</span></small>
            </div>

            <div class="form-group">
                <label for="initiator_id">您的ID <?php if ($strictness >= 2): ?><span class="text-danger">*（必须在系统作者列表中）</span><?php endif; ?></label>
                <input type="text" name="initiator_id" id="initiator_id" class="form-control" required placeholder="请输入您的ID">
            </div>

            <?php if ($strictness >= 3): ?>
            <div class="form-group">
                <label for="card_author_id">卡片作者ID <span class="text-danger">*（必须与该系列作者信息匹配）</span></label>
                <input type="text" name="card_author_id" id="card_author_id" class="form-control" required placeholder="请输入该系列卡片的作者ID">
                <small class="form-text text-muted">当前卡片作者：<?php echo Utils::escapeHtml($card['author']); ?></small>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <button type="submit" class="btn btn-warning">发起系列投票</button>
                <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $card['id']; ?>" class="btn btn-secondary">返回卡片详情</a>
            </div>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        <h5 class="card-title">系列中的卡片 (<?php echo count($seriesCards); ?> 张)</h5>
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
                    <?php if (empty($seriesCards)): ?>
                        <tr>
                            <td colspan="7" class="text-center">暂无数据</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($seriesCards as $seriesCard): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $seriesCard['id']; ?>">
                                        <?php echo $seriesCard['id']; ?>
                                    </a>
                                </td>
                                <td><?php echo Utils::escapeHtml($seriesCard['name']); ?></td>
                                <td><?php echo Utils::escapeHtml($seriesCard['type_text']); ?></td>
                                <td><?php echo Utils::escapeHtml($seriesCard['attribute_text']); ?></td>
                                <td><?php echo Utils::escapeHtml($seriesCard['race_text']); ?></td>
                                <td><?php echo ($seriesCard['type'] & 1) > 0 ? ($seriesCard['atk'] < 0 ? '?' : $seriesCard['atk']) : '-'; ?></td>
                                <td><?php echo ($seriesCard['type'] & 1) > 0 ? ($seriesCard['def'] < 0 ? '?' : $seriesCard['def']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reasonTextarea = document.getElementById('reason');
    const reasonCount = document.getElementById('reason-count');
    
    function updateCount() {
        const count = reasonTextarea.value.length;
        reasonCount.textContent = count;
        
        <?php if ($strictness >= 1): ?>
        if (count < <?php echo $minReasonLength; ?>) {
            reasonCount.style.color = 'red';
        } else {
            reasonCount.style.color = 'green';
        }
        <?php endif; ?>
    }
    
    reasonTextarea.addEventListener('input', updateCount);
    updateCount();
});
</script>
