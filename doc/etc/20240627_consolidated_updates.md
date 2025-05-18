# RAMSAY 系统更新文档 (2024年6月27日)

本文档整合了最近对RAMSAY系统的所有重要更新和优化，包括卡片识别、TCG卡片系列识别和投票结果处理等方面的改进。

## 目录

1. [卡片别名(alias)字段优化](#1-卡片别名alias字段优化)
2. [TCG卡片系列(setcode)识别优化](#2-tcg卡片系列setcode识别优化)
3. [投票结果处理优化](#3-投票结果处理优化)

## 1. 卡片别名(alias)字段优化

### 需求描述
CDB数据库中datas表的alias字段标记了异画卡或同名卡，该字段中的内容为一个目标卡片ID，标志了该卡为对应ID的同名卡。为此，需要进行以下逻辑优化：

1. 在卡片详情界面中，如果该卡片有alias字段，则以超链接形式显示该同名卡，点击后转至该卡。
2. 发起投票时，如果该卡片有alias字段，则在点击发起投票时，实际发起和该字段中ID相同的卡的投票。
3. 在进行卡片排名记录时，如果识别到的卡片有alias字段，则改为记录和那个字段同ID的卡，而不记录该卡片本身。

### 实现方案

#### 1.1 卡片详情页面优化
在卡片详情页面中，将alias字段显示改为超链接形式，点击后跳转到对应卡片的详情页面。同时，修改"发起投票"按钮，使其使用alias对应的卡片ID。

#### 1.2 投票功能优化
修改投票控制器，在创建投票时检查卡片是否有alias字段，如果有则使用alias对应的卡片ID创建投票。

#### 1.3 卡片排名系统优化
修改卡组解析类，在分析卡片使用情况时，检查卡片是否有alias字段，如果有则记录alias对应的卡片ID的使用情况。

### 修改内容

#### 1.1 卡片详情页面 (includes/Views/cards/detail.php)
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

#### 1.2 投票控制器 (includes/Controllers/VoteController.php)
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

#### 1.3 卡组解析类 (includes/Core/DeckParser.php)
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

## 2. TCG卡片系列(setcode)识别优化

### 需求描述
在卡片系列(setcode)识别中，需要对TCG卡片和非TCG卡片进行不同的处理：

1. 对于非TCG卡片：如果在已有的数据中无法匹配到对应setcode的setname，则转至`/assets/strings.conf`文件进行匹配
2. 对于TCG卡片：优先在`/assets/strings.conf`文件中匹配对应setcode的setname

### 实现方案

#### 2.1 修改系列信息加载逻辑
修改`CardParser`类的`loadSetcodes`方法，使其同时从卡片数据目录和`/assets`目录加载系列信息，并将`/assets`目录下的系列信息存储在单独的数组中，以便区分来源。

#### 2.2 修改系列文本获取逻辑
修改`getSetcodeText`方法，添加`isTcgCard`参数，根据卡片类型采用不同的匹配策略：
- 对于TCG卡片：优先从`/assets/strings.conf`中查找
- 对于非TCG卡片：优先从默认数据中查找，如果找不到再从`/assets/strings.conf`中查找

#### 2.3 更新调用代码
在所有调用`getSetcodeText`方法的地方，添加`isTcgCard`参数，以便正确处理不同类型的卡片。

### 修改内容

#### 2.1 系列信息加载 (includes/Core/CardParser.php)
```php
/**
 * 加载系列信息
 */
private function loadSetcodes() {
    // 首先从卡片数据目录加载系列信息
    $cardDataPath = CARD_DATA_PATH;
    $stringsFile = $cardDataPath . '/strings.conf';

    if (file_exists($stringsFile)) {
        $this->loadSetcodesFromFile($stringsFile);
    }

    // 然后加载assets目录下的strings.conf文件
    $assetsStringsFile = __DIR__ . '/../../assets/strings.conf';
    if (file_exists($assetsStringsFile)) {
        // 将这些系列信息存储在单独的数组中，以便区分来源
        $this->loadSetcodesFromFile($assetsStringsFile, 'assets');
    }
}

/**
 * 从文件加载系列信息
 * 
 * @param string $filePath 文件路径
 * @param string $source 来源标识，用于区分不同来源的系列信息
 */
private function loadSetcodesFromFile($filePath, $source = 'default') {
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // 跳过空行和注释行
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // 解析系列信息
            if (strpos($line, '!setname') === 0) {
                $parts = explode(' ', $line, 3);
                if (count($parts) >= 3) {
                    $code = trim($parts[1]);
                    $name = trim($parts[2]);
                    
                    // 如果是assets来源，则存储在单独的数组中
                    if ($source === 'assets') {
                        if (!isset($this->setcodes['assets'])) {
                            $this->setcodes['assets'] = [];
                        }
                        $this->setcodes['assets'][$code] = $name;
                    } else {
                        $this->setcodes[$code] = $name;
                    }
                }
            }
        }
    }
}
```

#### 2.2 系列文本获取 (includes/Core/CardParser.php)
```php
/**
 * 获取系列文本
 *
 * @param int $setcode 系列代码
 * @param bool $isTcgCard 是否为TCG卡片
 * @return string 系列文本
 */
public function getSetcodeText($setcode, $isTcgCard = false) {
    $hexSetcode = '0x' . dechex($setcode);
    
    // 对于TCG卡片，优先从assets/strings.conf中查找
    if ($isTcgCard && isset($this->setcodes['assets']) && isset($this->setcodes['assets'][$hexSetcode])) {
        return $this->setcodes['assets'][$hexSetcode];
    }
    
    // 对于普通卡片，优先从默认数据中查找
    if (isset($this->setcodes[$hexSetcode])) {
        return $this->setcodes[$hexSetcode];
    }
    
    // 如果在默认数据中找不到，尝试从assets/strings.conf中查找
    if (isset($this->setcodes['assets']) && isset($this->setcodes['assets'][$hexSetcode])) {
        return $this->setcodes['assets'][$hexSetcode];
    }
    
    // 如果都找不到，返回未知系列
    return '未知系列 (' . $hexSetcode . ')';
}
```

## 3. 投票结果处理优化

### 需求描述
目前，根据投票结果生成禁卡表时，更严格的结果将会优先考虑。例如：2票禁止，1票限制，1票准限制，2票无限制，那么生成禁卡表时，该卡片会作为禁止处理。

现在，需要优化该逻辑，通过配置项来支持不同的投票结果处理模式：

- 模式0（默认）：维持目前判定不变，以得票最多的最严格限制为准
- 模式1：低限制将会和高限制相互抵消，然后采用抵消后最高的限制
- 模式2：低限制将会和高限制相互抵消，然后采用抵消后最低的限制
- 模式3：以得票最多的最低限制作为实际判定

### 实现方案

#### 3.1 配置项添加
在`config.php`中添加`VOTING_RELAXED_MODE`配置项，默认值为0。

#### 3.2 投票结果处理逻辑优化
修改`Vote`类的投票结果处理逻辑，根据配置的模式采用不同的处理方式：

- 添加`determineFinalStatus`方法，根据投票模式确定最终状态
- 添加`calculateNetVotes`方法，计算各限制级别的净票数（用于模式1和模式2）
- 修改`getVoteResults`方法，使用新的状态确定逻辑

### 修改内容

#### 3.1 配置项添加 (config.php)
```php
// 投票配置
if (!defined('VOTES_PER_PAGE')) {
    define('VOTES_PER_PAGE', 20);
}
if (!defined('VOTE_LINK_PREFIX')) {
    define('VOTE_LINK_PREFIX', BASE_URL . 'vote/');
}
if (!defined('VOTING_RELAXED_MODE')) {
    define('VOTING_RELAXED_MODE', 0); // 0: 默认模式, 1: 抵消后最高限制, 2: 抵消后最低限制, 3: 得票最多的最低限制
}
```

#### 3.2 投票结果处理逻辑优化 (includes/Models/Vote.php)
添加了两个新方法：`determineFinalStatus`和`calculateNetVotes`，并修改了`getVoteResults`方法中的状态确定逻辑。

### 使用说明
通过修改`config.php`或`config.user.php`中的`VOTING_RELAXED_MODE`配置项，可以切换不同的投票结果处理模式：

- `VOTING_RELAXED_MODE = 0`：默认模式，以得票最多的最严格限制为准
- `VOTING_RELAXED_MODE = 1`：抵消模式，采用抵消后净票数最多的最高限制
- `VOTING_RELAXED_MODE = 2`：抵消模式，采用抵消后净票数最多的最低限制
- `VOTING_RELAXED_MODE = 3`：宽松模式，以得票最多的最低限制为准

### 示例
对于投票结果：2票禁止，1票限制，1票准限制，2票无限制

- 模式0：最终结果为"禁止"（得票最多的最严格限制）
- 模式1：最终结果为"限制"（禁止和无限制互相抵消，限制大于准限制）
- 模式2：最终结果为"准限制"（禁止和无限制互相抵消，准限制小于限制）
- 模式3：最终结果为"无限制"（得票最多的最低限制）
