# 高级投票功能实装文档

## 修改时间
2025年6月17日

## 功能概述
实装了高级发起投票功能，允许用户对多张卡片同时发起投票，适用于需要统一处理的卡片组合。

## 功能特性

### 1. 高级投票机制
- 允许用户输入一个或多个卡片ID来发起投票
- 支持多种输入格式：每行一个ID、逗号分隔、空格分隔等
- 自动解析并去重卡片ID列表
- 涉及多张卡片时，投票理由与投票发起限制按照系列投票处理

### 2. 确认页面
- 显示确认页面以表格形式向用户展示本次投票限制的卡片
- 显示卡片详细信息：ID、名称、类型、属性/种族、当前状态
- 提供编辑和确认选项

### 3. 投票概览显示
- 在投票概览页面以蓝色色调呈现高级投票
- 使用第一张卡作为缩略图
- 显示"高级投票"标识和涉及卡片数量

## 实现细节

### 1. 配置文件修改 (config.php)
```php
// 高级投票配置
if (!defined('ADVANCED_VOTING_ENABLED')) {
    define('ADVANCED_VOTING_ENABLED', true); // 是否启用高级投票功能
}
```

### 2. 数据库修改 (includes/Core/Database.php)
#### 投票表新增字段
```sql
ALTER TABLE votes ADD COLUMN is_advanced_vote INTEGER DEFAULT 0;
ALTER TABLE votes ADD COLUMN card_ids TEXT;
```

### 3. 核心功能实现

#### Vote模型修改 (includes/Models/Vote.php)
- 修改 `createVote` 方法支持高级投票
- 添加 `getVoteByAdvancedAndEnvironment` 方法
- 修改投票链接生成逻辑

#### Utils修改 (includes/Core/Utils.php)
- 修改 `generateVoteLink` 方法支持高级投票

#### VoteController修改 (includes/Controllers/VoteController.php)
- 添加 `createAdvanced` 方法处理高级投票创建
- 添加 `showAdvancedVotePreview` 方法显示预览确认页面
- 添加 `confirmAdvancedVote` 方法确认创建投票
- 添加 `parseCardIds` 方法解析卡片ID列表
- 修改投票概览处理，添加高级投票卡片数量统计
- 修改投票详情处理，支持显示高级投票信息

### 4. 视图文件修改

#### 投票概览页面 (includes/Views/votes/index.php)
- 添加"发起高级投票"按钮
- 添加高级投票标识和样式
- 使用蓝色色调显示高级投票

#### 高级投票创建页面 (includes/Views/votes/create_advanced.php)（新增）
- 卡片ID列表输入框，支持多种格式
- 环境和投票状态选择
- 理由输入和字符数统计
- 使用说明和注意事项

#### 高级投票确认页面 (includes/Views/votes/confirm_advanced.php)（新增）
- 投票信息摘要显示
- 涉及卡片列表表格
- 无效卡片ID警告
- 确认和编辑操作

#### 投票详情页面 (includes/Views/votes/vote.php)
- 添加高级投票标识
- 添加高级投票说明
- 可展开的高级投票卡片列表
- 显示每张卡片的详细信息和当前状态

## 用户界面

### 高级投票标识
- 蓝色标识："高级投票"
- 在投票概览和详情页面显示
- 区分普通投票、系列投票和高级投票

### 高级投票卡片展示
- 投票详情页面可展开显示
- 表格形式显示卡片信息
- 显示卡片基本信息和当前禁限状态

## 技术特性

### 性能优化
- 卡片数据按需加载
- 数据库查询优化
- 内存使用监控

### 安全性
- 严格的输入验证
- 卡片ID格式检查
- 防止重复投票

### 用户体验
- 实时字符数统计
- 清晰的操作流程
- 直观的界面标识
- 多种输入格式支持

## 文件清单

### 新增文件
- `includes/Views/votes/create_advanced.php` - 高级投票创建页面
- `includes/Views/votes/confirm_advanced.php` - 高级投票确认页面
- `doc/etc/20250617_advanced_voting_implementation.md` - 本文档

### 修改文件
- `config.php` - 添加高级投票配置
- `includes/Core/Database.php` - 数据库表结构更新
- `includes/Models/Vote.php` - 投票模型扩展
- `includes/Core/Utils.php` - 工具函数扩展
- `includes/Controllers/VoteController.php` - 投票控制器扩展
- `includes/Views/votes/index.php` - 投票概览页面
- `includes/Views/votes/vote.php` - 投票详情页面

## 使用说明

### 发起高级投票
1. 在投票概览页面点击"发起高级投票"按钮
2. 输入一个或多个卡片ID（支持多种格式）
3. 选择环境和投票状态
4. 填写详细理由（涉及多张卡片时要求与系列投票相同）
5. 输入发起人ID
6. 点击"预览确认"查看涉及的卡片
7. 确认无误后提交投票

### 参与高级投票
1. 在投票概览页面识别高级投票（蓝色标识）
2. 点击进入投票详情页面
3. 查看高级投票说明
4. 可展开查看涉及的所有卡片
5. 正常投票，结果将应用到所有涉及的卡片

## 注意事项

1. 高级投票结果将影响所有涉及的卡片
2. 涉及多张卡片时理由要求与系列投票相同
3. 所有卡片将使用相同的投票状态
4. 高级投票创建后无法修改卡片列表
5. 支持的卡片ID格式：数字，可用换行、逗号、分号、空格等分隔
