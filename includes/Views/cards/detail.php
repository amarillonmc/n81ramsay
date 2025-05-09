<h2><?php echo Utils::escapeHtml($card['name']); ?></h2>

<div class="card">
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
                    <?php if ($card['alias'] > 0): ?>
                        <tr>
                            <th>同名卡</th>
                            <td><?php echo $card['alias']; ?></td>
                        </tr>
                    <?php endif; ?>
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
                    <?php if (($card['type'] & 1) > 0): // 怪兽卡 ?>
                        <tr>
                            <th>种族</th>
                            <td><?php echo Utils::escapeHtml($card['race_text']); ?></td>
                        </tr>
                        <tr>
                            <th>属性</th>
                            <td><?php echo Utils::escapeHtml($card['attribute_text']); ?></td>
                        </tr>
                        <tr>
                            <th>等级/阶级/刻度</th>
                            <td><?php echo Utils::escapeHtml($card['level_text']); ?></td>
                        </tr>
                        <tr>
                            <th>攻击力</th>
                            <td><?php echo $card['atk'] < 0 ? '?' : $card['atk']; ?></td>
                        </tr>
                        <tr>
                            <th>守备力</th>
                            <td><?php echo $card['def'] < 0 ? '?' : $card['def']; ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>卡片描述</th>
                        <td><?php echo nl2br(Utils::escapeHtml($card['desc'])); ?></td>
                    </tr>
                    <?php if (!empty($card['author'])): ?>
                    <tr>
                        <th>卡片作者</th>
                        <td><?php echo Utils::escapeHtml($card['author']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>数据库文件</th>
                        <td><?php echo Utils::escapeHtml($card['database_file']); ?></td>
                    </tr>
                </table>

                <h3>禁限状态</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>环境</th>
                            <th>状态</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($environments as $env): ?>
                            <tr>
                                <td><?php echo Utils::escapeHtml($env['text']); ?></td>
                                <td class="<?php echo Utils::getLimitStatusClass($limitStatus[$env['header']]); ?>">
                                    <?php echo Utils::getLimitStatusText($limitStatus[$env['header']]); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>?controller=vote&action=create&card_id=<?php echo $card['id']; ?>" class="btn">发起投票</a>
                    <a href="<?php echo BASE_URL; ?>" class="btn btn-secondary">返回列表</a>
                </div>
            </div>
        </div>
    </div>
</div>
