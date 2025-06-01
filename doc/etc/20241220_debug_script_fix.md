# 调试脚本修复 - 2024年12月20日

## 问题描述

运行 `debug_setcode_query.php` 时出现错误：
```
Fatal error: Uncaught Error: Call to private CardParser::__construct() from invalid context
```

## 问题原因

CardParser类的构造函数是私有的，不能直接实例化。这是一个单例模式的实现，需要通过其他方式访问。

## 解决方案

### 创建简化调试脚本

创建了 `simple_setcode_debug.php`，避免直接实例化CardParser：

1. **通过Card模型访问**: 使用Card模型的公共方法来测试功能
2. **直接数据库查询**: 当Card模型无结果时，直接连接SQLite数据库进行测试
3. **多种查询方式**: 测试不同的setcode匹配逻辑

### 调试功能

#### 1. Card模型测试
- 测试 `getCardsBySetcode()` 方法
- 显示找到的卡片列表
- 显示卡片的详细信息

#### 2. 直接数据库查询
当Card模型无结果时，直接测试数据库：
- 精确匹配查询
- 位运算匹配查询  
- 反向位运算查询
- 包含匹配查询

#### 3. 数据库分析
- 显示数据库文件列表
- 显示每个数据库中setcode的分布
- 显示最常见的setcode值

### 查询方式对比

| 查询方式 | SQL语句 | 说明 |
|---------|---------|------|
| 精确匹配 | `setcode = 13386` | 完全相等 |
| 位运算匹配 | `(setcode & 13386) = 13386` | 卡片setcode包含目标setcode |
| 反向位运算 | `(13386 & setcode) = setcode` | 目标setcode包含卡片setcode |
| 包含匹配 | `(setcode & 13386) > 0` | 有任何位重叠 |

## 使用方法

1. **运行简化调试**: 访问 `simple_setcode_debug.php`
2. **查看结果**: 
   - 如果Card模型找到卡片，显示卡片列表
   - 如果没找到，显示直接数据库查询结果
3. **分析数据**: 查看不同查询方式的结果数量

## 预期输出

### 成功情况
```
✓ 所有类加载成功
✓ Card模型创建成功
找到 X 张卡片
[卡片列表表格]
```

### 无结果情况
```
没有找到匹配的卡片
直接数据库查询测试:
[各种查询方式的结果统计]
[数据库中setcode示例]
```

## 故障排除

### 如果所有查询都返回0
1. 检查CARD_DATA_PATH配置是否正确
2. 检查数据库文件是否存在
3. 检查数据库文件是否可读
4. 检查目标setcode是否存在于数据库中

### 如果数据库连接失败
1. 检查SQLite扩展是否安装
2. 检查文件权限
3. 检查文件路径是否正确

## 文件清单

### 修改文件
- `debug_setcode_query.php` - 修复了CardParser实例化问题

### 新增文件
- `simple_setcode_debug.php` - 简化的调试脚本
- `doc/etc/20241220_debug_script_fix.md` - 本文档

## 下一步

1. 运行 `simple_setcode_debug.php`
2. 根据结果确定问题所在：
   - 如果数据库中没有目标setcode，需要检查数据源
   - 如果有数据但Card模型找不到，需要调试查询逻辑
   - 如果Card模型能找到，需要检查API层面的问题

## 技术细节

### CardParser单例模式
CardParser使用单例模式，构造函数为私有：
```php
private function __construct() {
    // 初始化逻辑
}
```

### 访问方式
通过Card模型间接访问CardParser功能：
```php
$cardModel = new Card();
$cards = $cardModel->getCardsBySetcode($setcode);
```

### 直接数据库访问
当需要绕过业务逻辑时：
```php
$db = new PDO('sqlite:' . $dbFile);
$result = $db->query($sql);
```
