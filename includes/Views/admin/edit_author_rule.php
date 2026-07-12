<?php $escapedBaseUrl = Utils::escapeHtml(BASE_URL); ?>

<h2>编辑 CDB 文本归属规则</h2>

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
    <div class="card-header">
        <h3>编辑文本匹配条件</h3>
    </div>
    <div class="card-body">
        <p>文本规则优先于卡号映射。请尽量限定来源和字段，避免宽泛规则抢先匹配其他作者的卡片。</p>
        <form action="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=editAuthorRule&amp;id=<?php echo (int)$rule['id']; ?>" method="post">
            <?php Utils::renderCsrfFields('admin_edit_author_rule'); ?>
            <input type="hidden" name="rule_id" value="<?php echo (int)$rule['id']; ?>">

            <div class="form-group">
                <label for="database_file">CDB 来源文件</label>
                <input type="text" id="database_file" name="database_file" maxlength="255" placeholder="例如 no42.cdb" value="<?php echo Utils::escapeHtml(isset($rule['database_file']) ? $rule['database_file'] : ''); ?>">
                <small>只填文件名；留空表示所有 CDB。</small>
            </div>

            <div class="form-group">
                <label for="match_field">匹配字段</label>
                <select id="match_field" name="match_field" required>
                    <?php foreach ($matchFieldLabels as $fieldValue => $fieldLabel): ?>
                        <option value="<?php echo Utils::escapeHtml($fieldValue); ?>"<?php echo isset($rule['match_field']) && $rule['match_field'] === $fieldValue ? ' selected' : ''; ?>><?php echo Utils::escapeHtml($fieldLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="match_operator">匹配方式</label>
                <select id="match_operator" name="match_operator" required>
                    <?php foreach ($matchOperatorLabels as $operatorValue => $operatorLabel): ?>
                        <option value="<?php echo Utils::escapeHtml($operatorValue); ?>"<?php echo isset($rule['match_operator']) && $rule['match_operator'] === $operatorValue ? ' selected' : ''; ?>><?php echo Utils::escapeHtml($operatorLabel); ?></option>
                    <?php endforeach; ?>
                </select>
                <small>“其中一行相等”适合 <code>Copyright 本体</code> 这类独立署名行，并会兼容不同换行格式。</small>
            </div>

            <div class="form-group">
                <label for="match_value">匹配值</label>
                <textarea id="match_value" name="match_value" rows="3" required><?php echo Utils::escapeHtml(isset($rule['match_value']) ? $rule['match_value'] : ''); ?></textarea>
            </div>

            <?php
            $selectedTargetType = isset($rule['target_type']) ? $rule['target_type'] : 'author';
            $selectedTargetValue = isset($rule['target_value']) && $rule['target_value'] !== ''
                ? $rule['target_value']
                : (isset($rule['author_name']) ? $rule['author_name'] : '');
            ?>
            <div class="form-group">
                <label for="target_type">判定目标</label>
                <select id="target_type" name="target_type" required>
                    <?php foreach ($targetTypeLabels as $targetType => $targetLabel): ?>
                        <option value="<?php echo Utils::escapeHtml($targetType); ?>"<?php echo $selectedTargetType === $targetType ? ' selected' : ''; ?>><?php echo Utils::escapeHtml($targetLabel); ?></option>
                    <?php endforeach; ?>
                </select>
                <small>作者归属会进入作者榜与身份校验；人工系列分组不会被当成作者。</small>
            </div>

            <div class="form-group">
                <label for="target_value">目标名称</label>
                <input type="text" id="target_value" name="target_value" value="<?php echo Utils::escapeHtml($selectedTargetValue); ?>" required>
                <small>系列分组会用于详情和首页同系列推荐，但不会改写 CDB 的数值 setcode。</small>
            </div>

            <div class="form-group">
                <label for="priority">优先级</label>
                <input type="number" id="priority" name="priority" value="<?php echo Utils::escapeHtml(isset($rule['priority']) ? $rule['priority'] : 100); ?>" required>
                <small>数值越大越先匹配；同优先级时，更严格且更长的规则优先。</small>
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="is_case_sensitive" value="1"<?php echo !empty($rule['is_case_sensitive']) ? ' checked' : ''; ?>> 区分大小写</label>
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="is_enabled" value="1"<?php echo !isset($rule['is_enabled']) || (int)$rule['is_enabled'] === 1 ? ' checked' : ''; ?>> 启用此规则</label>
            </div>

            <div class="form-group">
                <label for="notes">备注</label>
                <textarea id="notes" name="notes" rows="3"><?php echo Utils::escapeHtml(isset($rule['notes']) ? $rule['notes'] : ''); ?></textarea>
            </div>

            <button type="submit" class="btn">保存修改</button>
            <a href="<?php echo $escapedBaseUrl; ?>?controller=admin&amp;action=authors" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>
