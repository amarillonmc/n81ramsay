# RAMSAY 界面优化更新 (2024年6月24日)

本文档整合了2024年6月24日对RAMSAY系统界面的优化更新，包括投票界面优化、管理员界面修复等内容。

## 目录

1. [修复管理员投票管理界面的显示问题](#1-修复管理员投票管理界面的显示问题)
2. [已关闭投票卡图灰度显示优化](#2-已关闭投票卡图灰度显示优化)
3. [投票界面优化](#3-投票界面优化)
4. [投票概览页面优化](#4-投票概览页面优化)
5. [投票者唯一标识功能](#5-投票者唯一标识功能)

## 1. 修复管理员投票管理界面的显示问题

### 问题描述
在管理员的投票管理界面，如果投票卡片有多于一个，则会出现第一张投票卡片重复显示，且覆盖掉之后的一张卡片的显示的情况。

### 原因分析
问题出在 `AdminController.php` 和 `VoteController.php` 文件中的 `votes()` 方法。在处理投票数据时，使用了引用变量 `&$vote` 进行循环，但循环结束后没有解除引用，导致 `$vote` 变量仍然引用着数组中的最后一个元素。当这个变量在视图中被使用时，就会导致显示问题。

### 修复方法
在循环结束后添加 `unset($vote)` 来解除引用，防止后续操作影响数组。

### 修改文件
1. `includes\Controllers\AdminController.php`
2. `includes\Controllers\VoteController.php`

### 具体修改
在两个文件中的循环结束后添加了 `unset($vote)` 语句：

```php
// 处理投票数据
foreach ($votes as &$vote) {
    // 获取卡片信息
    $card = $this->cardModel->getCardById($vote['card_id']);
    $vote['card'] = $card;

    // 获取环境信息
    $environment = Utils::getEnvironmentById($vote['environment_id']);
    $vote['environment'] = $environment;

    // 获取投票统计
    $vote['stats'] = $this->voteModel->getVoteStats($vote['id']);

    // 获取投票记录
    $vote['records'] = $this->voteModel->getVoteRecords($vote['id']);
}
// 解除引用，防止后续操作影响数组
unset($vote);
```

## 2. 已关闭投票卡图灰度显示优化

### 需求描述
在投票概览页面中，将已关闭的投票卡图以灰度方式显示，以便用户能够直观地区分哪些投票已经关闭。

### 实现方案
1. 为已关闭投票的卡片图片添加 `grayscale` CSS 类
2. 添加 CSS 样式，实现灰度效果和交互动画
3. 优化已关闭投票卡片的整体样式

### 修改内容

#### 2.1 修改卡片图片显示
在 `includes\Views\votes\index.php` 文件中，为已关闭投票的卡片图片添加 `grayscale` 类：

```php
<img src="<?php echo $vote['card']['image_path']; ?>" 
     alt="<?php echo Utils::escapeHtml($vote['card']['name']); ?>" 
     class="<?php echo $vote['is_closed'] ? 'grayscale' : ''; ?>">
```

#### 2.2 添加灰度效果的 CSS 样式
在 `includes\Views\votes\index.php` 文件的样式部分，添加以下 CSS 样式：

```css
/* 已关闭投票的灰度效果 */
.grayscale {
    filter: grayscale(100%);
    opacity: 0.8;
    transition: filter 0.3s, opacity 0.3s;
}

.card-item.closed {
    opacity: 0.9;
    background-color: #f8f8f8;
    transition: opacity 0.3s, background-color 0.3s;
}

.card-item.closed:hover {
    opacity: 1;
    background-color: #fff;
}

.card-item.closed:hover .grayscale {
    filter: grayscale(50%);
    opacity: 0.9;
}
```

### 效果说明
1. 已关闭的投票卡片图片会以 100% 灰度显示，并且透明度降低到 80%
2. 已关闭的投票卡片整体背景色变为浅灰色，透明度为 90%
3. 当鼠标悬停在已关闭的投票卡片上时：
   - 卡片透明度恢复到 100%
   - 背景色变回白色
   - 卡片图片的灰度降低到 50%，透明度提高到 90%
4. 所有变化都有平滑的过渡动画效果

## 3. 投票界面优化

### 需求描述
用户反馈，在投票界面进行投票时，无法看到该卡在对应环境下的实际禁止限制情况，会造成判断问题。需要在投票界面：
1. 以醒目方式标出该投票卡目前在该投票对应环境中的禁限情况
2. 根据该情况，在对应的投票选项旁边标注其为"进一步限制"，"不变"，还是"限制缓和"

### 实现方案
1. 修改 VoteController.php 的 vote 方法，获取卡片在当前环境中的禁限状态
2. 更新 votes/vote.php 视图，显示当前禁限状态并在投票选项旁添加变化类型标签
3. 添加 CSS 样式，使当前禁限状态和变化类型标签视觉上更加醒目

### 修改内容

#### 3.1 修改 VoteController.php
在 `includes\Controllers\VoteController.php` 文件中，修改 vote 方法，获取卡片在当前环境中的禁限状态：

```php
// 获取卡片在当前环境中的禁限状态
$currentLimitStatus = $this->cardModel->getCardLimitStatus($vote['card_id'], $environment['header']);
```

#### 3.2 更新 votes/vote.php 视图
在 `includes\Views\votes\vote.php` 文件中，添加当前禁限状态显示和投票选项标签：

```php
<!-- 显示当前禁限状态 -->
<div class="current-status">
    <strong>当前状态：</strong>
    <span class="status-badge status-<?php echo $currentLimitStatus; ?>">
        <?php echo Utils::getLimitStatusText($currentLimitStatus); ?>
    </span>
</div>

<!-- 投票选项 -->
<div class="form-group">
    <label>选择禁限状态：</label>
    <?php foreach ([0, 1, 2, 3] as $status): ?>
        <div class="form-check">
            <input type="radio" name="status" value="<?php echo $status; ?>" id="status_<?php echo $status; ?>" class="form-check-input" <?php echo $status == 3 ? 'checked' : ''; ?>>
            <label for="status_<?php echo $status; ?>" class="form-check-label">
                <?php echo Utils::getLimitStatusText($status); ?>
                <?php if ($status < $currentLimitStatus): ?>
                    <span class="change-type stricter">进一步限制</span>
                <?php elseif ($status > $currentLimitStatus): ?>
                    <span class="change-type relaxed">限制缓和</span>
                <?php else: ?>
                    <span class="change-type unchanged">不变</span>
                <?php endif; ?>
            </label>
        </div>
    <?php endforeach; ?>
</div>
```

#### 3.3 添加 CSS 样式
在 `includes\Views\votes\vote.php` 文件的样式部分，添加以下 CSS 样式：

```css
/* 当前状态样式 */
.current-status {
    margin-bottom: 15px;
    padding: 10px;
    background-color: #f8f8f8;
    border-radius: 5px;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    color: white;
    font-weight: bold;
}

.status-0 { background-color: #dc3545; } /* 禁止 */
.status-1 { background-color: #fd7e14; } /* 限制 */
.status-2 { background-color: #ffc107; color: #212529; } /* 准限制 */
.status-3 { background-color: #28a745; } /* 无限制 */

/* 变化类型标签 */
.change-type {
    display: inline-block;
    margin-left: 8px;
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 0.8em;
}

.stricter { background-color: #f8d7da; color: #721c24; }
.relaxed { background-color: #d4edda; color: #155724; }
.unchanged { background-color: #e2e3e5; color: #383d41; }
```

## 4. 投票概览页面优化

### 需求描述
在投票概览页面中，默认显示所有投票，包括当前周期和历史周期的投票，这使得页面过长且不易查找当前活跃的投票。需要优化投票概览页面，使其默认只显示当前周期的投票，并允许用户展开查看历史周期的投票。

### 实现方案
1. 修改投票概览页面，按投票周期分组显示投票
2. 默认只展开当前周期的投票，历史周期的投票默认折叠
3. 添加展开/折叠功能，允许用户控制显示内容

### 修改内容

#### 4.1 修改投票概览页面结构
在 `includes\Views\votes\index.php` 文件中，修改投票显示结构，按周期分组：

```php
<?php
// 按周期分组投票
$votesByVoteCycle = [];
foreach ($votes as $vote) {
    $voteCycle = $vote['vote_cycle'];
    if (!isset($votesByVoteCycle[$voteCycle])) {
        $votesByVoteCycle[$voteCycle] = [];
    }
    $votesByVoteCycle[$voteCycle][] = $vote;
}

// 按周期倒序排序
krsort($votesByVoteCycle);
?>

<?php foreach ($votesByVoteCycle as $voteCycle => $cycleVotes): ?>
    <div class="vote-cycle-group">
        <h3 class="vote-cycle-header" data-cycle="<?php echo $voteCycle; ?>">
            投票周期 <?php echo $voteCycle; ?>
            <span class="toggle-icon"><?php echo ($voteCycle == $currentCycle) ? '▼' : '►'; ?></span>
        </h3>
        <div class="vote-cycle-content" style="display: <?php echo ($voteCycle == $currentCycle) ? 'block' : 'none'; ?>">
            <div class="card-grid">
                <?php foreach ($cycleVotes as $vote): ?>
                    <!-- 投票卡片内容 -->
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
```

#### 4.2 添加展开/折叠功能的 JavaScript
在 `includes\Views\votes\index.php` 文件的脚本部分，添加以下 JavaScript 代码：

```javascript
// 投票周期展开/折叠功能
document.addEventListener('DOMContentLoaded', function() {
    const cycleHeaders = document.querySelectorAll('.vote-cycle-header');
    
    cycleHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const icon = this.querySelector('.toggle-icon');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.textContent = '▼';
            } else {
                content.style.display = 'none';
                icon.textContent = '►';
            }
        });
    });
});
```

## 5. 投票者唯一标识功能

### 需求描述
在投票详情页面中，需要为每个投票者生成一个唯一的标识符，以便在保护用户隐私的同时，能够区分不同的投票者。

### 实现方案
1. 基于投票者的IP地址生成一个9位的唯一标识符
2. 在投票记录中显示这个标识符，而不是直接显示IP地址
3. 确保同一IP地址总是生成相同的标识符，以便跟踪

### 修改内容

#### 5.1 添加生成唯一标识符的函数
在 `includes\Core\Utils.php` 文件中，添加以下函数：

```php
/**
 * 根据IP地址生成唯一标识符
 *
 * @param string $ip IP地址
 * @return string 9位唯一标识符
 */
public static function generateUniqueIdentifier($ip) {
    // 使用IP地址和固定盐值生成哈希
    $hash = md5($ip . 'RAMSAY_SALT_VALUE');
    
    // 取哈希的前9个字符作为标识符
    $identifier = substr($hash, 0, 9);
    
    // 确保标识符包含字母和数字
    $identifier = preg_replace('/[^a-zA-Z0-9]/', '0', $identifier);
    
    return $identifier;
}
```

#### 5.2 修改投票记录显示
在 `includes\Views\votes\vote.php` 文件中，修改投票记录显示部分：

```php
<div class="vote-records">
    <h3>投票记录</h3>
    <?php if (empty($vote['records'])): ?>
        <p>暂无投票记录</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>投票者ID</th>
                    <th>唯一标识</th>
                    <th>投票状态</th>
                    <th>投票时间</th>
                    <th>备注</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vote['records'] as $record): ?>
                    <tr>
                        <td><?php echo Utils::escapeHtml($record['user_id']); ?></td>
                        <td><code><?php echo Utils::generateUniqueIdentifier($record['ip']); ?></code></td>
                        <td class="<?php echo Utils::getLimitStatusClass($record['status']); ?>">
                            <?php echo Utils::getLimitStatusText($record['status']); ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($record['created_at'])); ?></td>
                        <td><?php echo Utils::escapeHtml($record['comment']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
```
