<h2>投票者封禁管理</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo Utils::escapeHtml($message); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo Utils::escapeHtml($error); ?></div>
<?php endif; ?>

<div class="row">
    <!-- 添加封禁 -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4>添加封禁</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo BASE_URL; ?>?controller=admin&action=addVoterBan">
                    <div class="form-group">
                        <label for="voter_identifier">投票者标识符</label>
                        <input type="text" class="form-control" id="voter_identifier" name="voter_identifier" 
                               placeholder="9位字符标识符" maxlength="9" required>
                        <small class="form-text text-muted">可以从投票详情页面复制投票者的标识符</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="ban_level">封禁等级</label>
                        <select class="form-control" id="ban_level" name="ban_level" required>
                            <option value="">请选择封禁等级</option>
                            <option value="1">等级1 - 限制投票（需要详细理由）</option>
                            <option value="2">等级2 - 禁止投票</option>
                        </select>
                        <small class="form-text text-muted">
                            等级1：用户可以投票，但理由不足时不计入统计<br>
                            等级2：用户完全无法投票
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">封禁理由</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" 
                                  placeholder="请输入封禁理由..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">添加封禁</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 封禁列表 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>当前封禁列表</h4>
            </div>
            <div class="card-body">
                <?php if (empty($bans)): ?>
                    <div class="alert alert-info">暂无封禁记录</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>投票者标识符</th>
                                    <th>封禁等级</th>
                                    <th>封禁理由</th>
                                    <th>封禁时间</th>
                                    <th>操作者</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bans as $ban): ?>
                                    <tr>
                                        <td>
                                            <code><?php echo Utils::escapeHtml($ban['voter_identifier']); ?></code>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $ban['ban_level'] == 1 ? 'warning' : 'danger'; ?>">
                                                <?php echo VoterBan::getBanLevelText($ban['ban_level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="ban-reason" title="<?php echo Utils::escapeHtml($ban['reason']); ?>">
                                                <?php 
                                                $reason = Utils::escapeHtml($ban['reason']);
                                                echo strlen($reason) > 50 ? substr($reason, 0, 50) . '...' : $reason;
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo Utils::formatDatetime($ban['banned_at']); ?>
                                        </td>
                                        <td>
                                            <?php echo Utils::escapeHtml($ban['banned_by']); ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="<?php echo BASE_URL; ?>?controller=admin&action=removeVoterBan" 
                                                  style="display: inline;" 
                                                  onsubmit="return confirm('确定要解除对 <?php echo Utils::escapeHtml($ban['voter_identifier']); ?> 的封禁吗？')">
                                                <input type="hidden" name="voter_identifier" value="<?php echo Utils::escapeHtml($ban['voter_identifier']); ?>">
                                                <button type="submit" class="btn btn-sm btn-success">解封</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.ban-reason {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: help;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.badge-danger {
    background-color: #dc3545;
    color: white;
}

.form-group {
    margin-bottom: 1rem;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    color: #495057;
    background-color: #fff;
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    user-select: none;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    cursor: pointer;
    text-decoration: none;
}

.btn-danger {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-danger:hover {
    color: #fff;
    background-color: #c82333;
    border-color: #bd2130;
}

.btn-success {
    color: #fff;
    background-color: #28a745;
    border-color: #28a745;
}

.btn-success:hover {
    color: #fff;
    background-color: #218838;
    border-color: #1e7e34;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}
</style>

<script>
// 自动格式化投票者标识符输入
document.getElementById('voter_identifier').addEventListener('input', function(e) {
    // 只允许字母和数字
    this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
    
    // 限制长度为9位
    if (this.value.length > 9) {
        this.value = this.value.substring(0, 9);
    }
});

// 封禁等级选择提示
document.getElementById('ban_level').addEventListener('change', function(e) {
    const reasonField = document.getElementById('reason');
    const level = parseInt(this.value);
    
    if (level === 1) {
        reasonField.placeholder = '请详细说明限制投票的原因，该用户仍可投票但理由不足时不计入统计...';
    } else if (level === 2) {
        reasonField.placeholder = '请详细说明禁止投票的原因，该用户将完全无法进行投票操作...';
    } else {
        reasonField.placeholder = '请输入封禁理由...';
    }
});
</script>
