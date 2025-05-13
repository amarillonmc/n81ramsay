# 卡片排行榜功能更新记录 (2024-06-24)

本文档整合了卡片排行榜功能的最新更新，包括"只看DIY卡排名"功能的实现和TCG卡片检测逻辑的修复。

## 1. "只看DIY卡排名"功能实现

### 需求描述
在卡片排行榜界面上添加一个"只看DIY卡排名"的选项，用户选择该选项后，从榜单中排除全部TCG卡片（即只能在TCG_CARD_DATA_PATH中找到的卡片）。

### 实现方案
1. 修改 CardRankingController.php，添加"只看DIY卡排名"的选项
2. 修改 CardRanking.php 核心类，实现过滤 TCG 卡片的功能
3. 更新 card_ranking/index.php 视图，添加"只看DIY卡排名"的选项
4. 在详细统计表格中添加标识 TCG 卡片的列

### 修改内容

#### 1. 修改 CardRankingController.php
在 `includes\Controllers\CardRankingController.php` 文件中，修改 index 方法，添加 diyOnly 参数：

```php
// 获取请求参数
$timeRange = isset($_GET['time_range']) ? $_GET['time_range'] : 'week';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$detailLimit = isset($_GET['detail_limit']) ? (int)$_GET['detail_limit'] : 10;
$diyOnly = isset($_GET['diy_only']) ? (bool)$_GET['diy_only'] : false;

// ...

// 获取卡片排行榜数据
$rankingData = $this->cardRankingModel->getCardRanking($timeRange, $limit, false, $diyOnly);
```

#### 2. 修改 CardRanking 模型类
在 `includes\Models\CardRanking.php` 文件中，修改 getCardRanking 方法，添加 diyOnly 参数：

```php
/**
 * 获取卡片排行榜
 *
 * @param string $timeRange 时间范围 (week, two_weeks, month, all)
 * @param int $limit 显示数量限制
 * @param bool $forceUpdate 是否强制更新
 * @param bool $diyOnly 是否只显示DIY卡片
 * @return array 卡片排行榜数据
 */
public function getCardRanking($timeRange = 'week', $limit = 10, $forceUpdate = false, $diyOnly = false) {
    return $this->cardRankingCore->getCardRanking($timeRange, $limit, $forceUpdate, $diyOnly);
}
```

#### 3. 修改 CardRanking 核心类
在 `includes\Core\CardRanking.php` 文件中，进行以下修改：

##### 3.1 修改 getCardRanking 方法，添加 diyOnly 参数：

```php
/**
 * 获取卡片排行榜
 *
 * @param string $timeRange 时间范围 (week, two_weeks, month, all)
 * @param int $limit 显示数量限制
 * @param bool $forceUpdate 是否强制更新
 * @param bool $diyOnly 是否只显示DIY卡片
 * @return array 卡片排行榜数据
 */
public function getCardRanking($timeRange = 'week', $limit = 10, $forceUpdate = false, $diyOnly = false) {
    // 检查缓存
    $cacheFile = $this->getCacheFilePath($timeRange, $diyOnly);
    
    // ...
    
    // 获取卡片详细信息并排序
    $rankingData = $this->processRankingData($cardUsage, $limit, $diyOnly);
    
    // 缓存数据
    $this->cacheRankingData($rankingData, $timeRange, $diyOnly);
    
    return $rankingData;
}
```

#### 4. 更新 card_ranking/index.php 视图
在 `includes\Views\card_ranking\index.php` 文件中，添加"只看DIY卡排名"的选项：

```php
<div class="form-group ml-3">
    <div class="checkbox">
        <label>
            <input type="checkbox" name="diy_only" value="1" <?php echo $diyOnly ? 'checked' : ''; ?> onchange="this.form.submit()">
            只看DIY卡排名
        </label>
    </div>
</div>
```

在详细统计表格中添加标识 TCG 卡片的列：

```php
<th>使用率</th>
<?php if (!$diyOnly): ?>
<th>卡片类型</th>
<?php endif; ?>
```

在表格行中添加 TCG 卡片的标识：

```php
<td><?php echo $card['usage_rate']; ?>%</td>
<?php if (!$diyOnly): ?>
<td><?php echo isset($card['is_tcg']) && $card['is_tcg'] ? 'TCG卡' : 'DIY卡'; ?></td>
<?php endif; ?>
```

## 2. 修复TCG卡片检测逻辑

### 问题描述
在卡片排行榜的"只看DIY卡排名"功能中，TCG卡片的检测逻辑存在问题。系统错误地将大部分TCG卡片标识为DIY卡片，导致在选择"只看DIY卡排名"选项时，仍然显示了大量TCG卡片。

### 原因分析
当前的检测逻辑是先尝试从DIY卡数据库中查找卡片，如果找不到，再从TCG卡数据库中查找。只有当卡片在DIY卡数据库中找不到，但在TCG卡数据库中找到时，才会将其标记为TCG卡片。

问题在于：有些卡片可能同时存在于DIY卡数据库和TCG卡数据库中，这种情况下，系统会错误地将其识别为DIY卡片。

### 修复方案
1. 修改卡片检测逻辑，先检查卡片是否存在于TCG卡数据库中
2. 如果卡片存在于TCG卡数据库中，则将其标记为TCG卡片，无论它是否也存在于DIY卡数据库中
3. 添加清除缓存功能，确保修改生效

### 修改内容

#### 1. 修改 CardRanking.php 中的 processRankingData 方法

```php
// 处理每张卡的详细信息
foreach ($cardUsage as $cardId => $usage) {
    // 先检查卡片是否存在于TCG卡数据库中
    $isTcgCard = false;
    $tcgCardInfo = null;
    
    if (defined('TCG_CARD_DATA_PATH') && file_exists(TCG_CARD_DATA_PATH)) {
        $tcgCardInfo = $this->getTcgCardInfo($cardId);
        if ($tcgCardInfo) {
            $isTcgCard = true;
        }
    }
    
    // 从DIY卡数据库中查找卡片信息
    $cardInfo = $this->cardParser->getCardById($cardId);
    
    // 如果在DIY卡数据库中找不到，但在TCG卡数据库中找到，则使用TCG卡片信息
    if (!$cardInfo && $tcgCardInfo) {
        $cardInfo = $tcgCardInfo;
    }
    
    // 如果找不到卡片信息，则跳过
    if (!$cardInfo) {
        continue;
    }
    
    // 如果只显示DIY卡片且当前卡片是TCG卡片，则跳过
    if ($diyOnly && $isTcgCard) {
        continue;
    }
}
```

#### 2. 添加清除缓存功能

在 CardRanking.php 中添加 clearAllCaches 方法：

```php
/**
 * 清除所有缓存文件
 */
public function clearAllCaches() {
    $timeRanges = ['week', 'two_weeks', 'month', 'all'];
    $diyOnlyOptions = [true, false];

    foreach ($timeRanges as $timeRange) {
        foreach ($diyOnlyOptions as $diyOnly) {
            $cacheFile = $this->getCacheFilePath($timeRange, $diyOnly);
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
        }
    }
}
```

在 CardRankingController.php 中添加 clearCache 方法：

```php
/**
 * 清除所有卡片排行榜缓存
 */
public function clearCache() {
    // 检查功能是否启用
    if (!defined('CARD_RANKING_ENABLED') || !CARD_RANKING_ENABLED) {
        header('Location: ' . BASE_URL);
        exit;
    }

    // 要求管理员权限
    $this->userModel->requirePermission(1);

    // 清除所有缓存
    $this->cardRankingModel->clearAllCaches();

    // 设置成功消息
    $_SESSION['success_message'] = '卡片排行榜缓存已清除';

    // 重定向回卡片排行榜页面
    header('Location: ' . BASE_URL . '?controller=card_ranking');
    exit;
}
```

#### 3. 在卡片排行榜页面添加清除缓存按钮

```php
<?php if ($this->userModel->hasPermission(1)): ?>
    <div>
        <a href="<?php echo BASE_URL; ?>?controller=card_ranking&action=update&time_range=<?php echo $timeRange; ?>" class="btn btn-primary">更新排行榜</a>
        <a href="<?php echo BASE_URL; ?>?controller=card_ranking&action=clearCache" class="btn btn-warning ml-2">清除缓存</a>
    </div>
<?php endif; ?>
```

## 效果说明
1. 用户可以在卡片排行榜界面上选择"只看DIY卡排名"选项，系统会从榜单中排除全部TCG卡片
2. 在不选择"只看DIY卡排名"时，系统会在详细统计表格中显示一个额外的列，标识每张卡片是TCG卡还是DIY卡
3. 修复了TCG卡片检测逻辑，确保系统能够正确识别TCG卡片和DIY卡片
4. 添加了清除缓存功能，管理员可以通过点击"清除缓存"按钮清除所有卡片排行榜缓存

## 修改时间
2024年6月24日
