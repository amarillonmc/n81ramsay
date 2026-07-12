<?php $escapedBaseUrl = Utils::escapeHtml(BASE_URL); ?>

<h2>作者管理</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-success">
        <?php echo Utils::escapeHtml($message); ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo Utils::escapeHtml($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3>从 strings.conf 识别作者</h3>
    </div>
    <div class="card-body">
        <p>系统会从 strings.conf 识别作者信息并导入。自动结果仍应在下方核对总位数、显式区间和重叠前缀。</p>
        <form action="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=identifyAuthors" method="post">
            <?php Utils::renderCsrfFields('admin_identify_authors'); ?>
            <button type="submit" class="btn">识别作者</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3>添加卡号作者映射</h3>
    </div>
    <div class="card-body">
        <form action="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=addAuthor" method="post">
            <?php Utils::renderCsrfFields('admin_add_author'); ?>
            <div class="form-group">
                <label for="card_prefix">卡片前缀</label>
                <input type="text" id="card_prefix" name="card_prefix" inputmode="numeric" maxlength="16" value="<?php echo Utils::escapeHtml($authorForm['card_prefix']); ?>" required>
                <small>按原样填写数字前缀；<code>011</code>、<code>0721</code> 等前导零是匹配的一部分，不能省略。</small>
            </div>

            <div class="form-group">
                <label for="card_id_length">卡号总位数</label>
                <input type="number" id="card_id_length" name="card_id_length" min="1" max="16" value="<?php echo Utils::escapeHtml($authorForm['card_id_length']); ?>">
                <small>可选。卡号从左侧补零到该长度后再匹配前缀；例如数据库中的 <code>1000360</code> 按8位读取为 <code>01000360</code>。</small>
            </div>

            <div class="form-group">
                <label for="card_id_start">显式卡号起始值</label>
                <input type="number" id="card_id_start" name="card_id_start" min="0" value="<?php echo Utils::escapeHtml($authorForm['card_id_start']); ?>">
            </div>

            <div class="form-group">
                <label for="card_id_end">显式卡号结束值</label>
                <input type="number" id="card_id_end" name="card_id_end" min="0" value="<?php echo Utils::escapeHtml($authorForm['card_id_end']); ?>">
                <small>可选，但起止值必须成对填写。显式区间存在时优先于“前缀＋总位数”；同一前缀可添加多条不同区间，系统以独立ID管理。</small>
            </div>

            <div class="form-group">
                <label for="mapping_priority">优先级</label>
                <input type="number" id="mapping_priority" name="priority" value="<?php echo Utils::escapeHtml($authorForm['priority']); ?>" required>
                <small>数值越大越优先；同优先级下会继续比较区间精度。</small>
            </div>

            <div class="form-group">
                <label for="author_name">作者名称</label>
                <input type="text" id="author_name" name="author_name" value="<?php echo Utils::escapeHtml($authorForm['author_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="alias">作者别名</label>
                <input type="text" id="alias" name="alias" value="<?php echo Utils::escapeHtml($authorForm['alias']); ?>">
                <small>可选，作者的其他名称。</small>
            </div>

            <div class="form-group">
                <label for="contact">联系方式</label>
                <input type="text" id="contact" name="contact" value="<?php echo Utils::escapeHtml($authorForm['contact']); ?>">
            </div>

            <div class="form-group">
                <label for="mapping_notes">备注</label>
                <textarea id="mapping_notes" name="notes" rows="3"><?php echo Utils::escapeHtml($authorForm['notes']); ?></textarea>
            </div>

            <button type="submit" class="btn">添加作者映射</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3>卡号作者映射列表</h3>
    </div>
    <div class="card-body">
        <?php if (empty($authorMappings)): ?>
            <div class="alert alert-info">暂无作者映射数据</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>前缀</th>
                            <th>总位数</th>
                            <th>显式区间</th>
                            <th>优先级</th>
                            <th>作者</th>
                            <th>别名</th>
                            <th>联系方式</th>
                            <th>备注</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($authorMappings as $mapping): ?>
                            <tr>
                                <td>#<?php echo (int)$mapping['id']; ?></td>
                                <td><code><?php echo Utils::escapeHtml($mapping['card_prefix']); ?></code></td>
                                <td><?php echo Utils::escapeHtml(isset($mapping['card_id_length']) && $mapping['card_id_length'] !== null ? $mapping['card_id_length'] : '自动'); ?></td>
                                <td>
                                    <?php if (isset($mapping['card_id_start'], $mapping['card_id_end']) && $mapping['card_id_start'] !== null && $mapping['card_id_end'] !== null): ?>
                                        <code><?php echo Utils::escapeHtml($mapping['card_id_start']); ?>–<?php echo Utils::escapeHtml($mapping['card_id_end']); ?></code>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo Utils::escapeHtml(isset($mapping['priority']) ? $mapping['priority'] : 100); ?></td>
                                <td><?php echo Utils::escapeHtml($mapping['author_name']); ?></td>
                                <td><?php echo Utils::escapeHtml(isset($mapping['alias']) ? $mapping['alias'] : ''); ?></td>
                                <td><?php echo Utils::escapeHtml(isset($mapping['contact']) ? $mapping['contact'] : ''); ?></td>
                                <td><?php echo Utils::escapeHtml(isset($mapping['notes']) ? $mapping['notes'] : ''); ?></td>
                                <td>
                                    <a href="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=editAuthor&amp;id=<?php echo (int)$mapping['id']; ?>" class="btn btn-sm">编辑</a>
                                    <form action="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=deleteAuthor" method="post" style="display: inline;">
                                        <?php Utils::renderCsrfFields('admin_delete_author'); ?>
                                        <input type="hidden" name="mapping_id" value="<?php echo (int)$mapping['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger delete-author-btn" onclick="return confirm('确定要删除这个作者映射吗？')">删除</button>
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

<div class="card mb-4">
    <div class="card-header">
        <h3>添加 CDB 文本归属规则</h3>
    </div>
    <div class="card-body">
        <p>文本规则优先于卡号映射，适用于没有稳定卡号区间的散卡或系列。请尽量限定来源、字段并使用足够具体的匹配值。</p>
        <form action="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=addAuthorRule" method="post">
            <?php Utils::renderCsrfFields('admin_add_author_rule'); ?>
            <div class="form-group">
                <label for="database_file">CDB 来源文件</label>
                <input type="text" id="database_file" name="database_file" maxlength="255" placeholder="例如 no42.cdb" value="<?php echo Utils::escapeHtml($ruleForm['database_file']); ?>">
                <small>只填文件名；留空表示所有 CDB。</small>
            </div>

            <div class="form-group">
                <label for="match_field">匹配字段</label>
                <select id="match_field" name="match_field" required>
                    <?php foreach ($matchFieldLabels as $fieldValue => $fieldLabel): ?>
                        <option value="<?php echo Utils::escapeHtml($fieldValue); ?>"<?php echo $ruleForm['match_field'] === $fieldValue ? ' selected' : ''; ?>><?php echo Utils::escapeHtml($fieldLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="match_operator">匹配方式</label>
                <select id="match_operator" name="match_operator" required>
                    <?php foreach ($matchOperatorLabels as $operatorValue => $operatorLabel): ?>
                        <option value="<?php echo Utils::escapeHtml($operatorValue); ?>"<?php echo $ruleForm['match_operator'] === $operatorValue ? ' selected' : ''; ?>><?php echo Utils::escapeHtml($operatorLabel); ?></option>
                    <?php endforeach; ?>
                </select>
                <small>“其中一行相等”适合 <code>Copyright 本体</code> 这类独立署名行，并会兼容不同换行格式。</small>
            </div>

            <div class="form-group">
                <label for="match_value">匹配值</label>
                <textarea id="match_value" name="match_value" rows="3" required><?php echo Utils::escapeHtml($ruleForm['match_value']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="target_type">判定目标</label>
                <select id="target_type" name="target_type" required>
                    <?php foreach ($targetTypeLabels as $targetType => $targetLabel): ?>
                        <option value="<?php echo Utils::escapeHtml($targetType); ?>"<?php echo $ruleForm['target_type'] === $targetType ? ' selected' : ''; ?>><?php echo Utils::escapeHtml($targetLabel); ?></option>
                    <?php endforeach; ?>
                </select>
                <small>作者归属会进入作者榜与作者身份校验；人工系列分组是独立元数据，不会被当成作者。</small>
            </div>

            <div class="form-group">
                <label for="target_value">目标名称</label>
                <input type="text" id="target_value" name="target_value" value="<?php echo Utils::escapeHtml($ruleForm['target_value']); ?>" required>
                <small>系列分组会用于卡片详情及首页同系列推荐，但不会改写 CDB 的数值 setcode。</small>
            </div>

            <div class="form-group">
                <label for="rule_priority">优先级</label>
                <input type="number" id="rule_priority" name="priority" value="<?php echo Utils::escapeHtml($ruleForm['priority']); ?>" required>
                <small>数值越大越先匹配；同优先级时，更严格且更长的规则优先。</small>
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="is_case_sensitive" value="1"<?php echo (int)$ruleForm['is_case_sensitive'] === 1 ? ' checked' : ''; ?>> 区分大小写</label>
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="is_enabled" value="1"<?php echo (int)$ruleForm['is_enabled'] === 1 ? ' checked' : ''; ?>> 创建后立即启用</label>
            </div>

            <div class="form-group">
                <label for="rule_notes">备注</label>
                <textarea id="rule_notes" name="notes" rows="3"><?php echo Utils::escapeHtml($ruleForm['notes']); ?></textarea>
            </div>

            <button type="submit" class="btn">添加文本规则</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>CDB 文本归属规则列表</h3>
    </div>
    <div class="card-body">
        <?php if (empty($authorRules)): ?>
            <div class="alert alert-info">暂无文本匹配规则</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>状态</th>
                            <th>来源</th>
                            <th>字段 / 方式</th>
                            <th>匹配值</th>
                            <th>目标类型 / 名称</th>
                            <th>优先级</th>
                            <th>大小写</th>
                            <th>备注</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($authorRules as $rule): ?>
                            <?php
                            $ruleField = isset($rule['match_field']) ? $rule['match_field'] : '';
                            $ruleOperator = isset($rule['match_operator']) ? $rule['match_operator'] : '';
                            $ruleEnabled = isset($rule['is_enabled']) && (int)$rule['is_enabled'] === 1;
                            $ruleDatabase = isset($rule['database_file']) && $rule['database_file'] !== ''
                                ? $rule['database_file']
                                : '全部 CDB';
                            $ruleTargetType = isset($rule['target_type']) ? $rule['target_type'] : 'author';
                            $ruleTargetValue = isset($rule['target_value']) && $rule['target_value'] !== ''
                                ? $rule['target_value']
                                : (isset($rule['author_name']) ? $rule['author_name'] : '');
                            ?>
                            <tr>
                                <td><?php echo $ruleEnabled ? '启用' : '停用'; ?></td>
                                <td><code><?php echo Utils::escapeHtml($ruleDatabase); ?></code></td>
                                <td>
                                    <?php echo Utils::escapeHtml(isset($matchFieldLabels[$ruleField]) ? $matchFieldLabels[$ruleField] : $ruleField); ?> /
                                    <?php echo Utils::escapeHtml(isset($matchOperatorLabels[$ruleOperator]) ? $matchOperatorLabels[$ruleOperator] : $ruleOperator); ?>
                                </td>
                                <td><code><?php echo Utils::escapeHtml(isset($rule['match_value']) ? $rule['match_value'] : ''); ?></code></td>
                                <td>
                                    <?php echo Utils::escapeHtml(isset($targetTypeLabels[$ruleTargetType]) ? $targetTypeLabels[$ruleTargetType] : $ruleTargetType); ?> /
                                    <?php echo Utils::escapeHtml($ruleTargetValue); ?>
                                </td>
                                <td><?php echo Utils::escapeHtml(isset($rule['priority']) ? $rule['priority'] : 100); ?></td>
                                <td><?php echo !empty($rule['is_case_sensitive']) ? '区分' : '忽略'; ?></td>
                                <td><?php echo Utils::escapeHtml(isset($rule['notes']) ? $rule['notes'] : ''); ?></td>
                                <td>
                                    <a href="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=editAuthorRule&amp;id=<?php echo (int)$rule['id']; ?>" class="btn btn-sm">编辑</a>
                                    <form action="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=toggleAuthorRule" method="post" style="display: inline;">
                                        <?php Utils::renderCsrfFields('admin_toggle_author_rule'); ?>
                                        <input type="hidden" name="rule_id" value="<?php echo (int)$rule['id']; ?>">
                                        <input type="hidden" name="is_enabled" value="<?php echo $ruleEnabled ? '0' : '1'; ?>">
                                        <button type="submit" class="btn btn-sm"><?php echo $ruleEnabled ? '停用' : '启用'; ?></button>
                                    </form>
                                    <form action="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=deleteAuthorRule" method="post" style="display: inline;">
                                        <?php Utils::renderCsrfFields('admin_delete_author_rule'); ?>
                                        <input type="hidden" name="rule_id" value="<?php echo (int)$rule['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除这条文本匹配规则吗？')">删除</button>
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
