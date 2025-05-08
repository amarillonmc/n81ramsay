<h2>发起投票</h2>

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
                                <option value="<?php echo $env['id']; ?>"><?php echo Utils::escapeHtml($env['text']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>选择禁限状态</label>
                        <div>
                            <label>
                                <input type="radio" name="status" value="0" required> 禁止
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="radio" name="status" value="1"> 限制
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="radio" name="status" value="2"> 准限制
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="radio" name="status" value="3"> 无限制
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
