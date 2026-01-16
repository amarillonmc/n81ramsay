<h2>卡片检索</h2>

<div class="card">
    <div class="card-body">
        <form id="search-form" action="<?php echo BASE_URL; ?>" method="get">
            <input type="hidden" name="controller" value="card">
            <input type="hidden" name="action" value="search">
            <div class="form-group">
                <label for="keyword">搜索卡片</label>
                <input type="text" id="keyword" name="keyword" placeholder="输入卡片ID或卡名">
            </div>
            <button type="submit" class="btn">搜索</button>

            <!-- 高级检索区域 -->
            <div class="advanced-search">
                <div class="advanced-search-toggle" id="advanced-search-toggle">
                    <span class="toggle-icon">▶</span>
                    <span>高级检索</span>
                </div>
                <div class="advanced-search-panel" id="advanced-search-panel">
                    <!-- 卡片类型标签 -->
                    <div class="search-tabs">
                        <button type="button" class="search-tab active" data-tab="all">所有卡</button>
                        <button type="button" class="search-tab" data-tab="monster">怪兽卡</button>
                        <button type="button" class="search-tab" data-tab="spell">魔法卡</button>
                        <button type="button" class="search-tab" data-tab="trap">陷阱卡</button>
                    </div>
                    <input type="hidden" name="card_type" id="card_type" value="">

                    <!-- 属性 -->
                    <div class="search-row" data-filter="monster">
                        <div class="search-row-label">属性 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <button type="button" class="search-btn" data-field="attribute" data-value="0x20">暗属性</button>
                            <button type="button" class="search-btn" data-field="attribute" data-value="0x10">光属性</button>
                            <button type="button" class="search-btn" data-field="attribute" data-value="0x1">地属性</button>
                            <button type="button" class="search-btn" data-field="attribute" data-value="0x2">水属性</button>
                            <button type="button" class="search-btn" data-field="attribute" data-value="0x4">炎属性</button>
                            <button type="button" class="search-btn" data-field="attribute" data-value="0x8">风属性</button>
                            <button type="button" class="search-btn" data-field="attribute" data-value="0x40">神属性</button>
                        </div>
                    </div>
                    <input type="hidden" name="attribute" id="attribute" value="">

                    <!-- 效果（魔法/陷阱类型） -->
                    <div class="search-row" data-filter="spell,trap">
                        <div class="search-row-label">效果 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <button type="button" class="search-btn" data-field="spell_trap_type" data-value="0x40000">装备</button>
                            <button type="button" class="search-btn" data-field="spell_trap_type" data-value="0x80000">场地</button>
                            <button type="button" class="search-btn" data-field="spell_trap_type" data-value="0x10000">速攻</button>
                            <button type="button" class="search-btn" data-field="spell_trap_type" data-value="0x80">仪式</button>
                            <button type="button" class="search-btn" data-field="spell_trap_type" data-value="0x20000">永续</button>
                            <button type="button" class="search-btn" data-field="spell_trap_type" data-value="0x100000">反击</button>
                            <button type="button" class="search-btn" data-field="spell_trap_type" data-value="0x10">通常</button>
                        </div>
                    </div>
                    <input type="hidden" name="spell_trap_type" id="spell_trap_type" value="">

                    <!-- 种族 -->
                    <div class="search-row" data-filter="monster">
                        <div class="search-row-label">种族 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <button type="button" class="search-btn" data-field="race" data-value="0x2">魔法师族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x2000">龙族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x10">不死族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x1">战士族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x8000">兽战士族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x4000">兽族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x200">鸟兽族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x8">恶魔族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x4">天使族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x800">昆虫族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x10000">恐龙族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x80000">爬虫类族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x20000">鱼族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x40000">海龙族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x40">水族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x80">炎族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x1000">雷族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x100">岩石族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x400">植物族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x20">机械族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x100000">念动力族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x200000">幻神兽族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x400000">创造神族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x800000">幻龙族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x1000000">电子界族</button>
                            <button type="button" class="search-btn" data-field="race" data-value="0x2000000">幻想魔族</button>
                        </div>
                    </div>
                    <input type="hidden" name="race" id="race" value="">

                    <!-- 其他项目 -->
                    <div class="search-row" data-filter="monster">
                        <div class="search-row-label">其他项目 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <div class="logic-selector">
                                <label><input type="radio" name="type_logic" value="and" checked> and</label>
                                <label><input type="radio" name="type_logic" value="or"> or</label>
                            </div>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x10">通常</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x20">效果</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x80">仪式</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x40">融合</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x2000">同步</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x800000">超量</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x62">卡通</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x200">灵魂</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x400">同盟</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x800">二重</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x1000">调整</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x200000">反转</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x1000000">灵摆</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x2000000">特殊召唤</button>
                            <button type="button" class="search-btn" data-field="type_include" data-value="0x4000000">连接</button>
                        </div>
                    </div>
                    <input type="hidden" name="type_include" id="type_include" value="">

                    <!-- 除外项目 -->
                    <div class="search-row" data-filter="monster">
                        <div class="search-row-label">除外项目 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x10">通常</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x20">效果</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x80">仪式</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x40">融合</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x2000">同步</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x800000">超量</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x62">卡通</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x200">灵魂</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x400">同盟</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x800">二重</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x1000">调整</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x200000">反转</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x1000000">灵摆</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x2000000">特殊召唤</button>
                            <button type="button" class="search-btn" data-field="type_exclude" data-value="0x4000000">连接</button>
                        </div>
                    </div>
                    <input type="hidden" name="type_exclude" id="type_exclude" value="">

                    <!-- 等级/阶级 -->
                    <div class="search-row" data-filter="monster">
                        <div class="search-row-label">等级/阶级 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <div class="level-grid">
                                <?php for ($i = 0; $i <= 13; $i++): ?>
                                <button type="button" class="search-btn level-btn" data-field="level" data-value="<?php echo $i; ?>"><?php echo $i; ?></button>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="level" id="level" value="">

                    <!-- 灵摆刻度 -->
                    <div class="search-row" data-filter="monster">
                        <div class="search-row-label">灵摆刻度 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <div class="level-grid">
                                <?php for ($i = 0; $i <= 13; $i++): ?>
                                <button type="button" class="search-btn level-btn" data-field="scale" data-value="<?php echo $i; ?>"><?php echo $i; ?></button>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="scale" id="scale" value="">

                    <!-- 连接 -->
                    <div class="search-row" data-filter="monster">
                        <div class="search-row-label">连接 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <div class="link-section">
                                <div class="link-value-selector">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <button type="button" class="search-btn level-btn" data-field="link_value" data-value="<?php echo $i; ?>"><?php echo $i; ?></button>
                                    <?php endfor; ?>
                                </div>
                                <div class="link-marker-diagram" id="link-marker-diagram">
                                    <div class="link-marker" data-marker="0x40" title="左上">↖</div>
                                    <div class="link-marker" data-marker="0x80" title="上">↑</div>
                                    <div class="link-marker" data-marker="0x100" title="右上">↗</div>
                                    <div class="link-marker" data-marker="0x8" title="左">←</div>
                                    <div class="link-marker center"></div>
                                    <div class="link-marker" data-marker="0x20" title="右">→</div>
                                    <div class="link-marker" data-marker="0x1" title="左下">↙</div>
                                    <div class="link-marker" data-marker="0x2" title="下">↓</div>
                                    <div class="link-marker" data-marker="0x4" title="右下">↘</div>
                                </div>
                                <div class="logic-selector">
                                    <label><input type="radio" name="link_logic" value="and"> and</label>
                                    <label><input type="radio" name="link_logic" value="or" checked> or</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="link_value" id="link_value" value="">
                    <input type="hidden" name="link_markers" id="link_markers" value="">

                    <!-- 攻击力/守备力 -->
                    <div class="search-row" data-filter="monster">
                        <div class="search-row-label">攻击力 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <div class="stat-range">
                                <div class="stat-range-group">
                                    <span class="stat-range-icon">≥</span>
                                    <input type="text" class="stat-range-input" name="atk_min" id="atk_min" placeholder="最小值">
                                </div>
                                <div class="stat-range-group">
                                    <span class="stat-range-icon">≤</span>
                                    <input type="text" class="stat-range-input" name="atk_max" id="atk_max" placeholder="最大值">
                                </div>
                            </div>
                        </div>
                        <div class="search-row-label">守备力 <span class="help-icon">?</span></div>
                        <div class="search-row-clear"><button type="button" class="clear-row-btn">×</button></div>
                        <div class="search-row-content">
                            <div class="stat-range">
                                <div class="stat-range-group">
                                    <span class="stat-range-icon">≥</span>
                                    <input type="text" class="stat-range-input" name="def_min" id="def_min" placeholder="最小值">
                                </div>
                                <div class="stat-range-group">
                                    <span class="stat-range-icon">≤</span>
                                    <input type="text" class="stat-range-input" name="def_max" id="def_max" placeholder="最大值">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 搜索按钮 -->
                    <div class="search-submit-row">
                        <button type="submit" class="search-submit-btn">搜索</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($dbFiles)): ?>
    <div class="card">
        <div class="card-header">
            <h3>数据库文件</h3>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs">
                <?php foreach ($dbFiles as $index => $dbFile): ?>
                    <?php $fileName = basename($dbFile); ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $selectedDb === $dbFile ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>?db=<?php echo urlencode(basename($dbFile)); ?>">
                            <?php echo $fileName; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($cards)): ?>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3>卡片列表 (<?php echo $pagination['total']; ?>)</h3>
                <div class="per-page-selector">
                    <form id="per-page-form" action="<?php echo BASE_URL; ?>" method="get">
                        <input type="hidden" name="controller" value="card">
                        <input type="hidden" name="action" value="index">
                        <input type="hidden" name="db" value="<?php echo urlencode(basename($selectedDb)); ?>">
                        <label for="per_page">每页显示：</label>
                        <select id="per_page" name="per_page" onchange="document.getElementById('per-page-form').submit();">
                            <?php foreach ($perPageOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo $pagination['per_page'] == $option ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="card-grid">
                <?php foreach ($cards as $card): ?>
                    <div class="card-item">
                        <a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $card['id']; ?>">
                            <img src="<?php echo $card['image_path']; ?>" alt="<?php echo Utils::escapeHtml($card['name']); ?>">
                            <div class="card-item-body">
                                <div class="card-item-title"><?php echo Utils::escapeHtml($card['name']); ?></div>
                                <div>ID: <?php echo $card['id']; ?></div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination">
                    <ul>
                        <?php if ($pagination['page'] > 1): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>?controller=card&action=index&db=<?php echo urlencode(basename($selectedDb)); ?>&page=1&per_page=<?php echo $pagination['per_page']; ?>">首页</a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>?controller=card&action=index&db=<?php echo urlencode(basename($selectedDb)); ?>&page=<?php echo $pagination['page'] - 1; ?>&per_page=<?php echo $pagination['per_page']; ?>">上一页</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        // 显示页码
                        $startPage = max(1, $pagination['page'] - 2);
                        $endPage = min($pagination['total_pages'], $pagination['page'] + 2);

                        // 确保显示至少5个页码（如果有足够的页数）
                        if ($endPage - $startPage < 4 && $pagination['total_pages'] > 4) {
                            if ($startPage == 1) {
                                $endPage = min($pagination['total_pages'], 5);
                            } elseif ($endPage == $pagination['total_pages']) {
                                $startPage = max(1, $pagination['total_pages'] - 4);
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li <?php echo $i == $pagination['page'] ? 'class="active"' : ''; ?>>
                                <a href="<?php echo BASE_URL; ?>?controller=card&action=index&db=<?php echo urlencode(basename($selectedDb)); ?>&page=<?php echo $i; ?>&per_page=<?php echo $pagination['per_page']; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>?controller=card&action=index&db=<?php echo urlencode(basename($selectedDb)); ?>&page=<?php echo $pagination['page'] + 1; ?>&per_page=<?php echo $pagination['per_page']; ?>">下一页</a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>?controller=card&action=index&db=<?php echo urlencode(basename($selectedDb)); ?>&page=<?php echo $pagination['total_pages']; ?>&per_page=<?php echo $pagination['per_page']; ?>">末页</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php elseif (isset($selectedDb)): ?>
    <div class="alert alert-info">
        没有找到卡片
    </div>
<?php endif; ?>
