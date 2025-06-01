# Setcode查询逻辑修复 - 2024年12月20日

## 问题描述

在运行调试脚本后发现，虽然API能找到30张卡片，但这些卡片的setcode值并不是目标值13386 (0x344a)，而是其他不相关的值如1234、8、10、4098等。

## 问题分析

### 原有查询逻辑问题

原来的多层级查询策略中，第三种查询方式有问题：

```sql
-- 问题查询：反向位运算
WHERE (13386 & setcode) = setcode AND setcode > 0
```

这个查询会匹配所有setcode值小于或等于13386的卡片，因为：
- `(13386 & 8) = 8` ✓ 匹配
- `(13386 & 10) = 10` ✓ 匹配  
- `(13386 & 1234) = 1234` ✓ 匹配

这显然是错误的逻辑。

### 正确的游戏王Setcode匹配逻辑

在游戏王中，setcode匹配应该遵循以下规则：
1. **精确匹配**: `setcode = target` 
2. **包含匹配**: `(setcode & target) = target` - 卡片的setcode包含目标setcode的所有位

## 解决方案

### 修复查询逻辑

移除了错误的反向位运算查询，只保留正确的两种查询方式：

```sql
-- 方式1: 精确匹配 (优先)
WHERE d.setcode = :setcode

-- 方式2: 位运算匹配 (备选)
WHERE (d.setcode & :setcode) = :setcode 
  AND d.setcode > 0 
  AND d.setcode != :setcode
```

### 查询优先级

1. **优先使用精确匹配结果**
2. **如果精确匹配无结果，使用位运算匹配**
3. **移除了错误的反向位运算匹配**

## 修复内容

### CardParser.php修改

**修改前**:
```php
// 三种查询方式，包括错误的反向位运算
if (!empty($exactResults)) {
    $results = $exactResults;
} elseif (!empty($bitwiseResults)) {
    $results = $bitwiseResults;
} else {
    // 错误的反向位运算查询
    $results = $reverseResults;
}
```

**修改后**:
```php
// 只保留正确的两种查询方式
if (!empty($exactResults)) {
    $results = $exactResults;
} else {
    // 正确的位运算匹配
    $results = $bitwiseResults;
}
```

### 位运算匹配说明

正确的位运算匹配 `(setcode & target) = target` 的含义：
- 卡片的setcode必须包含目标setcode的所有位
- 例如：setcode=0x344a (13386) 可以匹配 setcode=0x344b (13387)
- 但不应该匹配 setcode=0x8 (8) 或 setcode=0xa (10)

## 测试验证

### 创建测试工具
- `test_fixed_setcode_query.php` - 验证修复后的查询逻辑

### 测试内容
1. **查询结果验证**: 检查返回的卡片是否都正确匹配
2. **匹配类型分析**: 区分精确匹配和位运算匹配
3. **位运算测试**: 验证不同setcode值的位运算关系
4. **数据库验证**: 检查数据库中是否真的存在目标setcode

### 预期结果

修复后的查询应该：
- 只返回setcode为13386的卡片（精确匹配）
- 或返回setcode包含13386所有位的卡片（位运算匹配）
- 不再返回无关的小数值setcode卡片

## 位运算示例

| 卡片Setcode | 十六进制 | (setcode & 0x344a) = 0x344a | 应该匹配 |
|------------|----------|---------------------------|---------|
| 13386 | 0x344a | 是 | 是 (精确) |
| 13387 | 0x344b | 是 | 是 (位运算) |
| 1234 | 0x4d2 | 否 | 否 |
| 8 | 0x8 | 否 | 否 |
| 10 | 0xa | 否 | 否 |

## 调试信息改进

更新了调试日志，显示：
- 使用的查询方法（exact/bitwise）
- 各种查询的结果数量
- setcode的十六进制表示

## 文件修改清单

### 修改文件
- `includes/Core/CardParser.php` - 修复setcode查询逻辑

### 新增文件
- `test_fixed_setcode_query.php` - 查询逻辑验证工具
- `doc/etc/20241220_setcode_query_logic_fix.md` - 本文档

## 验证步骤

1. 运行 `test_fixed_setcode_query.php` 验证修复效果
2. 检查返回的卡片是否都正确匹配目标setcode
3. 测试API调用: `?controller=api&action=getSeriesCards&setcode=0x344a`
4. 确认不再返回无关的小数值setcode卡片

## 注意事项

1. **向后兼容**: 修复不影响现有的精确匹配功能
2. **性能优化**: 移除了错误的第三种查询，减少了不必要的数据库操作
3. **逻辑正确**: 现在的查询逻辑符合游戏王setcode的实际匹配规则

## 预期效果

修复后，系列投票详情页面的"加载系列卡片"功能应该：
- 只显示真正属于该系列的卡片
- 不再显示无关的其他系列卡片
- 提供准确的系列卡片统计信息
