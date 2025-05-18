# 投票结果处理优化

## 需求描述
目前，根据投票结果生成禁卡表时，更严格的结果将会优先考虑。例如：2票禁止，1票限制，1票准限制，2票无限制，那么生成禁卡表时，该卡片会作为禁止处理。

现在，需要优化该逻辑，通过配置项来支持不同的投票结果处理模式：

- 模式0（默认）：维持目前判定不变，以得票最多的最严格限制为准
- 模式1：低限制将会和高限制相互抵消，然后采用抵消后最高的限制
- 模式2：低限制将会和高限制相互抵消，然后采用抵消后最低的限制
- 模式3：以得票最多的最低限制作为实际判定

## 实现方案

### 1. 配置项添加
在`config.php`中添加`VOTING_RELAXED_MODE`配置项，默认值为0。

### 2. 投票结果处理逻辑优化
修改`Vote`类的投票结果处理逻辑，根据配置的模式采用不同的处理方式：

- 添加`determineFinalStatus`方法，根据投票模式确定最终状态
- 添加`calculateNetVotes`方法，计算各限制级别的净票数（用于模式1和模式2）
- 修改`getVoteResults`方法，使用新的状态确定逻辑

## 修改内容

### 1. 配置项添加 (config.php)
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

### 2. 投票结果处理逻辑优化 (includes/Models/Vote.php)

#### 2.1 添加`determineFinalStatus`方法
```php
/**
 * 根据投票模式确定最终状态
 *
 * @param array $stats 投票统计
 * @return int 最终状态
 */
private function determineFinalStatus($stats) {
    // 默认无限制
    $finalStatus = 3;
    
    // 获取投票模式
    $votingMode = defined('VOTING_RELAXED_MODE') ? VOTING_RELAXED_MODE : 0;
    
    // 根据不同的投票模式处理
    switch ($votingMode) {
        case 0: // 默认模式：票数最多的状态
            $maxVotes = 0;
            foreach ($stats as $status => $count) {
                if ($count > $maxVotes) {
                    $maxVotes = $count;
                    $finalStatus = $status;
                }
            }
            break;
            
        case 1: // 抵消后最高限制
            // 计算各限制级别的净票数（高限制和低限制相互抵消）
            $netVotes = $this->calculateNetVotes($stats);
            
            // 找出净票数最多的最高限制
            $maxNetVotes = 0;
            $highestRestriction = 3;
            
            foreach ($netVotes as $status => $votes) {
                if ($votes > $maxNetVotes || ($votes == $maxNetVotes && $status < $highestRestriction)) {
                    $maxNetVotes = $votes;
                    $highestRestriction = $status;
                }
            }
            
            $finalStatus = $highestRestriction;
            break;
            
        case 2: // 抵消后最低限制
            // 计算各限制级别的净票数（高限制和低限制相互抵消）
            $netVotes = $this->calculateNetVotes($stats);
            
            // 找出净票数最多的最低限制
            $maxNetVotes = 0;
            $lowestRestriction = 0;
            
            foreach ($netVotes as $status => $votes) {
                if ($votes > $maxNetVotes || ($votes == $maxNetVotes && $status > $lowestRestriction)) {
                    $maxNetVotes = $votes;
                    $lowestRestriction = $status;
                }
            }
            
            $finalStatus = $lowestRestriction;
            break;
            
        case 3: // 得票最多的最低限制
            $maxVotes = 0;
            $lowestRestriction = 0;
            
            foreach ($stats as $status => $count) {
                if ($count > $maxVotes || ($count == $maxVotes && $status > $lowestRestriction)) {
                    $maxVotes = $count;
                    $lowestRestriction = $status;
                }
            }
            
            $finalStatus = $lowestRestriction;
            break;
    }
    
    return $finalStatus;
}
```

#### 2.2 添加`calculateNetVotes`方法
```php
/**
 * 计算各限制级别的净票数
 *
 * @param array $stats 投票统计
 * @return array 净票数
 */
private function calculateNetVotes($stats) {
    $netVotes = [
        0 => 0, // 禁止
        1 => 0, // 限制
        2 => 0, // 准限制
        3 => 0  // 无限制
    ];
    
    // 初始化净票数为原始票数
    foreach ($stats as $status => $count) {
        $netVotes[$status] = $count;
    }
    
    // 禁止(0)和无限制(3)相互抵消
    $cancelVotes = min($netVotes[0], $netVotes[3]);
    $netVotes[0] -= $cancelVotes;
    $netVotes[3] -= $cancelVotes;
    
    // 限制(1)和准限制(2)相互抵消
    $cancelVotes = min($netVotes[1], $netVotes[2]);
    $netVotes[1] -= $cancelVotes;
    $netVotes[2] -= $cancelVotes;
    
    return $netVotes;
}
```

#### 2.3 修改`getVoteResults`方法
```php
// 获取投票统计
$stats = $this->getVoteStats($voteId);

// 根据投票模式确定最终状态
$finalStatus = $this->determineFinalStatus($stats);
```

## 使用说明
通过修改`config.php`或`config.user.php`中的`VOTING_RELAXED_MODE`配置项，可以切换不同的投票结果处理模式：

- `VOTING_RELAXED_MODE = 0`：默认模式，以得票最多的最严格限制为准
- `VOTING_RELAXED_MODE = 1`：抵消模式，采用抵消后净票数最多的最高限制
- `VOTING_RELAXED_MODE = 2`：抵消模式，采用抵消后净票数最多的最低限制
- `VOTING_RELAXED_MODE = 3`：宽松模式，以得票最多的最低限制为准

## 示例
对于投票结果：2票禁止，1票限制，1票准限制，2票无限制

- 模式0：最终结果为"禁止"（得票最多的最严格限制）
- 模式1：最终结果为"限制"（禁止和无限制互相抵消，限制大于准限制）
- 模式2：最终结果为"准限制"（禁止和无限制互相抵消，准限制小于限制）
- 模式3：最终结果为"无限制"（得票最多的最低限制）
