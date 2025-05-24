# 内存优化修复 - 2024年12月20日

## 问题描述

在投票功能中出现了内存耗尽错误：
```
Fatal error: Allowed memory size of 4294967296 bytes exhausted (tried to allocate 262144 bytes) in C:\n81ramsay\includes\Core\Utils.php on line 130
```

尽管生产服务器的Memory Limit已设置为4096MB，仍然出现此问题。

## 问题分析

通过代码分析发现了以下问题：

### 1. 主要问题：无限递归调用
- **位置**：`includes/Core/Utils.php` 第130行
- **函数**：`generateVoterIdentifier()`
- **问题**：当生成的标识符不满足条件（没有字母或数字）时，函数会递归调用自己，可能导致无限递归

### 2. 次要问题：内存管理不足
- 投票页面加载大量投票记录时缺乏内存监控
- 没有适当的垃圾回收机制
- 大量数据处理时缺乏内存使用优化

## 解决方案

### 1. 修复递归调用问题

**文件**：`includes/Core/Utils.php`
**修改**：重写 `generateVoterIdentifier` 函数

**原代码问题**：
```php
if (!$hasLetter || !$hasNumber) {
    // 如果不满足条件，重新生成
    return self::generateVoterIdentifier($ipAddress, $userId);
}
```

**修复后代码**：
```php
// 尝试多次生成，避免无限递归
$maxAttempts = 10;
for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
    // 每次尝试使用不同的偏移量
    $offset = $attempt * 3;
    $identifier = substr($hash, $offset, 9);

    // 如果超出哈希长度，重新生成哈希
    if (strlen($identifier) < 9) {
        $hash = md5($hash . $attempt);
        $identifier = substr($hash, 0, 9);
    }

    // 确保至少包含一个字母和一个数字
    $hasLetter = preg_match('/[a-f]/i', $identifier);
    $hasNumber = preg_match('/[0-9]/', $identifier);

    if ($hasLetter && $hasNumber) {
        return $identifier;
    }
}

// 如果所有尝试都失败，强制生成一个符合条件的标识符
$baseId = substr($hash, 0, 7);
return $baseId . 'a1'; // 确保包含字母和数字
```

### 2. 添加内存监控和管理工具

**文件**：`includes/Core/Utils.php`
**新增功能**：

#### 内存使用检查
```php
public static function checkMemoryUsage($context = '', $warningThreshold = 3072)
```

#### 内存限制转换
```php
private static function convertToBytes($val)
```

#### 强制垃圾回收
```php
public static function forceGarbageCollection($context = '')
```

### 3. 优化投票数据处理

**文件**：`includes/Controllers/VoteController.php`
**修改**：在投票列表处理中添加内存监控

```php
// 处理投票数据
Utils::checkMemoryUsage('投票列表处理开始');

foreach ($votes as &$vote) {
    // ... 处理逻辑 ...

    // 检查内存使用情况，如果超过阈值则进行垃圾回收
    if (Utils::checkMemoryUsage('投票数据处理', 2048)) {
        Utils::forceGarbageCollection('投票列表处理');
    }
}

unset($vote);
Utils::checkMemoryUsage('投票列表处理完成');
```

### 4. 优化投票统计查询

**文件**：`includes/Models/Vote.php`
**修改**：`getVoteStats()` 方法

**原方法问题**：
```php
$records = $this->getVoteRecords($voteId);
foreach ($records as $record) {
    // 处理每条记录
}
```

**优化后**：
```php
// 直接从数据库统计，避免加载所有记录到内存
$result = $this->db->getRows(
    'SELECT status, COUNT(*) as count FROM vote_records WHERE vote_id = ? GROUP BY status',
    [$voteId]
);
```

### 5. 优化管理员投票处理

**文件**：`includes/Controllers/AdminController.php`
**修改**：添加内存监控和垃圾回收

### 6. 修复卡片解析器的内存问题

**文件**：`includes/Core/CardParser.php`
**问题**：在获取多个数据库的卡片时，会先加载所有卡片到内存，然后进行分页

**原代码问题**：
```php
// 这里简化处理，先获取所有卡片，然后在内存中分页
$cardsFromDb = $this->getCardsFromDatabase($file);
$cards = array_merge($cards, $cardsFromDb);
```

**修复方案**：
- 使用数据库级别的分页，避免加载所有卡片到内存
- 计算每个数据库需要跳过和获取的记录数
- 添加内存监控和垃圾回收

### 7. 优化卡片搜索功能

**文件**：`includes/Core/CardParser.php`
**修改**：
- 添加搜索结果数量限制（默认100条）
- 在搜索过程中添加内存监控
- 使用数据库LIMIT子句限制查询结果

### 8. 优化卡组分析功能

**文件**：`includes/Core/DeckParser.php`
**修改**：
- 优化卡片统计算法，避免重复计算
- 使用 `array_unique()` 减少循环次数
- 添加进度监控，每处理100个文件检查一次内存
- 添加内存监控和垃圾回收

**优化前问题**：
```php
// 每次都重新计算整个数组的count值
$cardCount = array_count_values($mainDeck)[$cardId] ?? 0;
```

**优化后**：
```php
// 一次性计算所有卡片数量，然后使用唯一卡片列表
$mainDeckCounts = array_count_values($mainDeck);
$uniqueMainCards = array_unique($mainDeck);
```

## 技术细节

### 内存监控阈值设置
- 警告阈值：3072MB（默认）
- 处理阈值：2048MB（触发垃圾回收）
- 调试模式下会记录详细的内存使用情况

### 垃圾回收策略
- 在处理大量数据时定期检查内存使用
- 超过阈值时自动触发垃圾回收
- 记录垃圾回收的效果（回收对象数量）

### 数据库查询优化
- 使用聚合查询代替加载所有记录
- 减少内存中的数据量
- 提高查询效率

## 预期效果

1. **消除无限递归**：彻底解决 `generateVoterIdentifier` 函数的递归调用问题
2. **内存使用监控**：实时监控内存使用情况，及时发现问题
3. **自动内存管理**：在内存使用过高时自动进行垃圾回收
4. **查询性能优化**：减少不必要的数据加载，提高系统性能
5. **调试信息增强**：在调试模式下提供详细的内存使用信息
6. **数据库分页优化**：避免加载大量数据到内存，使用数据库级别的分页
7. **算法优化**：减少重复计算，提高处理效率
8. **进度监控**：在长时间运行的任务中提供进度反馈和内存管理

## 测试建议

1. **功能测试**：确保投票功能正常工作
2. **压力测试**：模拟大量投票数据的处理
3. **内存监控**：观察内存使用情况和垃圾回收效果
4. **日志检查**：查看调试日志中的内存使用信息

## 注意事项

1. 修改后需要测试所有投票相关功能
2. 建议在测试环境中先验证修复效果
3. 生产环境部署前建议备份相关文件
4. 可以通过调试模式监控内存使用情况的改善
