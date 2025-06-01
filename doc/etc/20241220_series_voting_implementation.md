# 系列投票功能实现 - 2024年12月20日

## 功能概述

为投票系统添加了"为整个系列发起·进行投票"的功能，该功能允许用户对整个卡片系列统一发起投票，投票结果将应用到该系列下的所有卡片。

## 功能特性

### 1. 配置选项
- **SERIES_VOTING_ENABLED**: 是否启用系列投票功能（默认：true）
- **SERIES_VOTING_STRICTNESS**: 系列投票严格度（默认：2）
  - 0: 所有用户均可发起系列投票
  - 1: 需要填写指定长度的理由
  - 2: 需要作者身份验证
- **SERIES_VOTING_REASON_MIN_LENGTH**: 理由最小字节数（默认：400）

### 2. 使用限制
- 只能对DIY卡片系列发起投票
- 不能对TCG系列（setcode落于TCG资源文件内的）发起投票
- 不能对无系列（setcode为0x0）的卡片发起投票
- 根据严格度设置进行权限控制

### 3. 界面功能
- 在卡片详情页面添加"发起系列投票"按钮
- 系列投票创建界面显示严格度信息和系列卡片列表
- 投票概览页面标识系列投票
- 投票详情页面显示系列投票说明和可展开的系列卡片列表

## 实现细节

### 1. 数据库修改

#### 投票表新增字段
```sql
ALTER TABLE votes ADD COLUMN is_series_vote INTEGER DEFAULT 0;
ALTER TABLE votes ADD COLUMN setcode INTEGER DEFAULT 0;
```

### 2. 配置文件修改 (config.php)
```php
// 系列投票配置
if (!defined('SERIES_VOTING_ENABLED')) {
    define('SERIES_VOTING_ENABLED', true);
}
if (!defined('SERIES_VOTING_STRICTNESS')) {
    define('SERIES_VOTING_STRICTNESS', 2);
}
if (!defined('SERIES_VOTING_REASON_MIN_LENGTH')) {
    define('SERIES_VOTING_REASON_MIN_LENGTH', 400);
}
```

### 3. 核心功能实现

#### Vote模型修改
- 修改 `createVote` 方法支持系列投票
- 添加 `getVoteBySetcodeAndEnvironment` 方法
- 修改投票链接生成逻辑

#### Card模型修改
- 添加 `getCardsBySetcode` 方法获取同系列卡片

#### CardParser修改
- 添加 `getCardsBySetcode` 方法实现

#### Utils修改
- 修改 `generateVoteLink` 方法支持系列投票

### 4. 控制器修改

#### VoteController
- 添加 `createSeries` 方法处理系列投票创建
- 添加 `checkAuthorAuthorization` 方法验证作者权限
- 修改 `vote` 方法支持系列投票显示

#### ApiController（新增）
- 添加 `getSeriesCards` 方法提供AJAX接口

### 5. 视图文件修改

#### 卡片详情页面 (includes/Views/cards/detail.php)
- 添加"发起系列投票"按钮
- 按条件显示（非TCG卡片且有系列）

#### 系列投票创建页面 (includes/Views/votes/create_series.php)（新增）
- 显示严格度信息
- 显示系列卡片列表
- 理由字符数实时统计
- 表单验证

#### 投票概览页面 (includes/Views/votes/index.php)
- 添加系列投票标识
- 显示系列信息

#### 投票详情页面 (includes/Views/votes/vote.php)
- 添加系列投票标识和说明
- 可展开的系列卡片列表
- AJAX加载系列卡片数据

## 权限控制

### 严格度级别说明

#### 级别0：开放模式
- 所有用户均可发起系列投票
- 无特殊限制

#### 级别1：理由模式
- 用户必须填写指定长度的理由
- 默认要求400个字符

#### 级别2：作者验证模式
- 用户ID必须与卡片系列作者信息匹配
- 检查管理员配置的作者映射
- 检查卡片描述中的作者信息

### 作者身份验证逻辑
1. 优先检查数据库中的作者映射表
2. 检查作者名称和别名是否匹配
3. 如果数据库无记录，检查卡片作者信息

## 用户界面

### 系列投票标识
- 橙色标识："系列投票"
- 在投票概览和详情页面显示
- 区分普通投票和系列投票

### 系列卡片展示
- 投票详情页面可展开显示
- AJAX异步加载提高性能
- 显示卡片基本信息和链接

## 技术特性

### 性能优化
- 系列卡片数据按需加载
- AJAX接口减少页面加载时间
- 数据库查询优化

### 安全性
- 严格的权限验证
- 防止对TCG卡片的误操作
- 输入数据验证和过滤

### 用户体验
- 实时字符数统计
- 清晰的权限提示
- 直观的界面标识

## 文件清单

### 新增文件
- `includes/Views/votes/create_series.php` - 系列投票创建页面
- `includes/Controllers/ApiController.php` - API控制器

### 修改文件
- `config.php` - 添加系列投票配置
- `includes/Core/Database.php` - 数据库表结构更新
- `includes/Models/Vote.php` - 投票模型扩展
- `includes/Models/Card.php` - 卡片模型扩展
- `includes/Core/CardParser.php` - 卡片解析器扩展
- `includes/Core/Utils.php` - 工具函数扩展
- `includes/Controllers/VoteController.php` - 投票控制器扩展
- `includes/Views/cards/detail.php` - 卡片详情页面
- `includes/Views/votes/index.php` - 投票概览页面
- `includes/Views/votes/vote.php` - 投票详情页面

## 使用说明

### 发起系列投票
1. 在卡片详情页面点击"发起系列投票"按钮
2. 选择环境和投票状态
3. 根据严格度要求填写理由和ID
4. 提交后创建系列投票

### 参与系列投票
1. 在投票概览页面识别系列投票（橙色标识）
2. 点击进入投票详情页面
3. 查看系列投票说明
4. 可展开查看系列中的所有卡片
5. 正常投票，结果将应用到整个系列

## 注意事项

1. 系列投票结果将影响整个系列的所有卡片
2. 严格度为2时需要确保ID与作者信息匹配
3. 只能对DIY卡片系列发起投票
4. 系列投票创建后无法修改范围
