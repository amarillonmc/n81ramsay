# Setcode参数处理修复 - 2024年12月20日

## 问题描述

在系列投票API中，当传递十六进制格式的setcode参数（如`setcode=0x344a`）时，API返回"无效的系列代码"错误。调试信息显示setcode被转换为0。

## 问题原因

PHP的`(int)`类型转换无法直接处理十六进制字符串格式。当传递`0x344a`时：
- `(int)"0x344a"` 结果为 `0`
- 因为PHP遇到非数字字符时停止转换

## 解决方案

### 修改ApiController.php

**原代码**:
```php
$setcode = isset($_GET['setcode']) ? (int)$_GET['setcode'] : 0;
```

**修改后**:
```php
// 获取参数，支持十六进制格式
$setcodeParam = isset($_GET['setcode']) ? trim($_GET['setcode']) : '0';

// 处理十六进制格式（如 0x344a）
if (strpos($setcodeParam, '0x') === 0) {
    $setcode = hexdec(substr($setcodeParam, 2));
} else {
    $setcode = (int)$setcodeParam;
}
```

### 功能特性

1. **支持十六进制格式**: `0x344a` → `13386`
2. **支持十进制格式**: `13386` → `13386`
3. **向后兼容**: 原有的十进制参数仍然正常工作
4. **增强调试**: 显示原始参数和转换后的值

### 转换示例

| 输入参数 | 检测格式 | 转换结果 | 说明 |
|---------|---------|---------|------|
| `0x344a` | 十六进制 | `13386` | 3×16³ + 4×16² + 4×16¹ + 10×16⁰ |
| `0x1` | 十六进制 | `1` | 基本十六进制 |
| `344a` | 十进制 | `344` | 遇到非数字字符停止 |
| `13386` | 十进制 | `13386` | 直接转换 |

## 测试验证

### 创建测试工具
- `test_setcode_conversion.php` - 验证setcode转换逻辑

### 测试用例
1. **十六进制格式**: `?controller=api&action=getSeriesCards&setcode=0x344a`
2. **十进制格式**: `?controller=api&action=getSeriesCards&setcode=13386`
3. **基本测试**: `?controller=api&action=getSeriesCards&setcode=1`

### 预期结果
- 十六进制和对应的十进制参数应该返回相同的结果
- 调试信息应该显示参数转换过程
- API应该成功返回系列卡片数据

## 调试信息增强

### 错误情况
```json
{
    "success": false,
    "message": "无效的系列代码",
    "debug": {
        "original_param": "0x344a",
        "converted_setcode": 13386
    }
}
```

### 成功情况
```json
{
    "success": true,
    "cards": [...],
    "count": 5,
    "debug": {
        "original_param": "0x344a",
        "converted_setcode": 13386,
        "found_cards": 5
    }
}
```

## 技术细节

### hexdec()函数
- `hexdec("344a")` → `13386`
- 自动处理大小写：`hexdec("344A")` → `13386`
- 忽略无效字符：`hexdec("344g")` → `836` (只处理到g之前)

### 错误处理
- 空字符串默认为0
- 无效格式回退到整数转换
- 保持原有的验证逻辑（setcode <= 0检查）

## 影响范围

### 前端JavaScript
前端代码无需修改，因为：
- URL构建逻辑保持不变
- 十六进制格式在URL中正常传递
- API现在能正确解析这些参数

### 其他API调用
此修复对其他功能无影响：
- 只影响getSeriesCards方法
- 向后兼容现有的十进制参数
- 不改变API响应格式

## 文件修改清单

### 修改文件
- `includes/Controllers/ApiController.php` - 修复setcode参数处理

### 新增文件
- `test_setcode_conversion.php` - setcode转换测试工具
- `doc/etc/20241220_setcode_parameter_fix.md` - 本文档

## 验证步骤

1. 访问 `test_setcode_conversion.php` 验证转换逻辑
2. 测试原问题URL: `?controller=api&action=getSeriesCards&setcode=0x344a`
3. 验证等效的十进制URL: `?controller=api&action=getSeriesCards&setcode=13386`
4. 确认两个URL返回相同的结果

## Setcode查询逻辑优化

### 问题发现
虽然setcode参数转换正确，但查询结果为空。这表明原有的位运算查询逻辑可能不适用于所有情况。

### 解决方案
实现多层级查询策略：

1. **精确匹配**: `d.setcode = :setcode`
2. **位运算匹配**: `(d.setcode & :setcode) = :setcode`
3. **反向位运算**: `(:setcode & d.setcode) = d.setcode`

### 查询优先级
- 优先使用精确匹配结果
- 如果精确匹配无结果，使用位运算匹配
- 如果位运算匹配无结果，使用反向位运算匹配

### 调试工具
- `debug_setcode_query.php` - 详细分析setcode查询结果

## 注意事项

1. **大小写不敏感**: `0x344a` 和 `0x344A` 结果相同
2. **前缀必须**: 必须以 `0x` 开头才被识别为十六进制
3. **无效字符**: 十六进制中的无效字符会被忽略
4. **性能影响**: 字符串处理开销极小，可忽略不计
5. **查询策略**: 使用多层级查询确保找到相关卡片
