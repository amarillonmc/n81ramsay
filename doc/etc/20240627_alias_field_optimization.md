# 卡片别名(alias)字段优化

## 需求描述
CDB数据库中datas表的alias字段标记了异画卡或同名卡，该字段中的内容为一个目标卡片ID，标志了该卡为对应ID的同名卡。为此，需要进行以下逻辑优化：

1. 在卡片详情界面中，如果该卡片有alias字段，则以超链接形式显示该同名卡，点击后转至该卡。
2. 发起投票时，如果该卡片有alias字段，则在点击发起投票时，实际发起和该字段中ID相同的卡的投票。
3. 在进行卡片排名记录时，如果识别到的卡片有alias字段，则改为记录和那个字段同ID的卡，而不记录该卡片本身。

## 实现方案

### 1. 卡片详情页面优化
在卡片详情页面中，将alias字段显示改为超链接形式，点击后跳转到对应卡片的详情页面。同时，修改"发起投票"按钮，使其使用alias对应的卡片ID。

### 2. 投票功能优化
修改投票控制器，在创建投票时检查卡片是否有alias字段，如果有则使用alias对应的卡片ID创建投票。

### 3. 卡片排名系统优化
修改卡组解析类，在分析卡片使用情况时，检查卡片是否有alias字段，如果有则记录alias对应的卡片ID的使用情况。

## 修改内容

### 1. 卡片详情页面 (includes/Views/cards/detail.php)
- 将alias字段显示改为超链接形式
- 修改"发起投票"按钮，使用alias对应的卡片ID

```php
// 将alias字段显示改为超链接
<?php if ($card['alias'] > 0): ?>
    <tr>
        <th>同名卡</th>
        <td><a href="<?php echo BASE_URL; ?>?controller=card&action=detail&id=<?php echo $card['alias']; ?>"><?php echo $card['alias']; ?></a></td>
    </tr>
<?php endif; ?>

// 修改"发起投票"按钮
<?php if (!$isTcgCard || ($isTcgCard && $allowTcgCardVoting)): // 非TCG卡片或允许对TCG卡投票时显示投票按钮 ?>
    <?php 
    // 如果卡片有alias字段，则使用alias对应的卡片ID发起投票
    $voteCardId = ($card['alias'] > 0) ? $card['alias'] : $card['id']; 
    ?>
    <a href="<?php echo BASE_URL; ?>?controller=vote&action=create&card_id=<?php echo $voteCardId; ?>" class="btn">发起投票</a>
<?php endif; ?>
```

### 2. 投票控制器 (includes/Controllers/VoteController.php)
- 在GET请求中检查卡片是否有alias字段
- 在POST请求中检查卡片是否有alias字段

```php
// GET请求处理
// 如果卡片有alias字段，则使用alias对应的卡片ID
if ($card['alias'] > 0) {
    $aliasCard = $this->cardModel->getCardById($card['alias']);
    if ($aliasCard) {
        $cardId = $card['alias'];
        $card = $aliasCard;
    }
}

// POST请求处理
// 检查卡片是否有alias字段，如果有则使用alias对应的卡片ID
$card = $this->cardModel->getCardById($cardId);
if ($card && $card['alias'] > 0) {
    $aliasCard = $this->cardModel->getCardById($card['alias']);
    if ($aliasCard) {
        $cardId = $card['alias'];
    }
}
```

### 3. 卡组解析类 (includes/Core/DeckParser.php)
- 添加获取真实卡片ID的方法
- 修改卡片使用情况分析方法，使用真实卡片ID

```php
/**
 * 获取卡片的真实ID（考虑alias字段）
 *
 * @param int $cardId 卡片ID
 * @return int 真实卡片ID
 */
private function getRealCardId($cardId) {
    // 获取卡片解析器实例
    $cardParser = CardParser::getInstance();
    
    // 获取卡片信息
    $card = $cardParser->getCardById($cardId);
    
    // 如果卡片存在且有alias字段，则返回alias对应的卡片ID
    if ($card && $card['alias'] > 0) {
        return $card['alias'];
    }
    
    // 否则返回原始卡片ID
    return $cardId;
}

// 在analyzeCardUsage方法中处理卡片ID
// 处理主卡组卡片，将卡片ID映射到真实ID（考虑alias字段）
$mainDeck = [];
foreach ($deck['main'] as $cardId) {
    $realCardId = $this->getRealCardId($cardId);
    $mainDeck[] = $realCardId;
}

// 处理副卡组卡片，将卡片ID映射到真实ID（考虑alias字段）
$sideDeck = [];
foreach ($deck['side'] as $cardId) {
    $realCardId = $this->getRealCardId($cardId);
    $sideDeck[] = $realCardId;
}
```

## 注意事项
1. 在卡片详情页面中，同时显示原始卡片ID和alias对应的卡片ID，方便用户了解卡片关系。
2. 在投票功能中，如果alias对应的卡片不存在，则仍使用原始卡片ID。
3. 在卡片排名系统中，需要考虑性能问题，避免频繁查询数据库。
