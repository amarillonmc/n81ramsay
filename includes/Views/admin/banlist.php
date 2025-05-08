<h2>禁卡表整理</h2>

<div class="card">
    <div class="card-header">
        <h3>当前投票周期: <?php echo $voteCycle; ?></h3>
    </div>
    <div class="card-body">
        <form action="<?php echo BASE_URL; ?>admin/generate" method="post">
            <div class="form-group">
                <label for="environment_id">选择环境</label>
                <select id="environment_id" name="environment_id" required>
                    <option value="">-- 请选择环境 --</option>
                    <?php foreach ($environments as $env): ?>
                        <option value="<?php echo $env['id']; ?>"><?php echo Utils::escapeHtml($env['text']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn">生成禁卡表</button>
        </form>
        
        <?php if ($this->userModel->hasPermission(2)): ?>
            <hr>
            
            <form action="<?php echo BASE_URL; ?>admin/reset" method="post" class="mt-3">
                <button type="submit" id="reset-vote-btn" class="btn btn-danger">重置投票并增加投票周期</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($environments as $env): ?>
    <?php $envResults = $groupedResults[$env['id']] ?? []; ?>
    
    <div class="card mt-4">
        <div class="card-header">
            <h3><?php echo Utils::escapeHtml($env['text']); ?></h3>
        </div>
        <div class="card-body">
            <?php if (empty($envResults)): ?>
                <div class="alert alert-info">
                    该环境暂无投票结果
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>卡片ID</th>
                                <th>卡名</th>
                                <th>最终状态</th>
                                <th>投票统计</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($envResults as $result): ?>
                                <tr>
                                    <td><?php echo $result['card_id']; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>card/detail?id=<?php echo $result['card_id']; ?>">
                                            <?php echo Utils::escapeHtml($result['card_name']); ?>
                                        </a>
                                    </td>
                                    <td class="<?php echo Utils::getLimitStatusClass($result['final_status']); ?>">
                                        <?php echo Utils::getLimitStatusText($result['final_status']); ?>
                                    </td>
                                    <td>
                                        <div>禁止: <?php echo $result['stats'][0]; ?></div>
                                        <div>限制: <?php echo $result['stats'][1]; ?></div>
                                        <div>准限制: <?php echo $result['stats'][2]; ?></div>
                                        <div>无限制: <?php echo $result['stats'][3]; ?></div>
                                        <div>总票数: <?php echo $result['total_votes']; ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
