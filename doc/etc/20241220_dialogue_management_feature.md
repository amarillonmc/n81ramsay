# 召唤词管理功能实装 - 2024年12月20日

## 功能概述

为RAMSAY系统实装了完整的召唤词管理功能，包括召唤词一览、用户投稿、管理员审核和直接管理等功能。该功能基于dialogues-custom.json文件，支持用户投稿召唤词并由管理员审核。

## 实装内容

### 1. 配置文件扩展

**文件**: `config.php`

添加了新的配置项：
```php
// 召唤词配置
if (!defined('DIALOGUES_FILE_PATH')) {
    define('DIALOGUES_FILE_PATH', __DIR__ . '/data/const/dialogues-custom.json');
}

// 召唤词投稿配置
if (!defined('MAX_PENDING_DIALOGUES_PER_USER')) {
    define('MAX_PENDING_DIALOGUES_PER_USER', 5); // 用户可以同时投稿的召唤词数量
}

if (!defined('DIALOGUE_SUBMISSION_STRICTNESS')) {
    define('DIALOGUE_SUBMISSION_STRICTNESS', 2); // 召唤词投稿严格度
}
```

**严格度说明**：
- 0：无限制，直接通过审核
- 1：仅验证作者存在，不强制要求前缀匹配
- 2：验证作者存在且卡片前缀匹配

### 2. 数据库表结构

**文件**: `includes/Core/Database.php`

新增召唤词投稿表：
```sql
CREATE TABLE IF NOT EXISTS dialogue_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    card_id TEXT NOT NULL,
    dialogue TEXT NOT NULL,
    author_id TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT "pending",
    reviewed_by TEXT,
    reviewed_at TIMESTAMP,
    reject_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

### 3. 模型层实现

**文件**: `includes/Models/DialogueModel.php`

#### 3.1 核心功能
- `loadDialogues()` - 读取召唤词文件，支持临时路径回退
- `saveDialogues($dialogues)` - 保存召唤词到文件，包含详细错误处理
- `validateAuthor($authorId, $cardId, $strictness)` - 验证作者信息

#### 3.2 投稿管理
- `getPendingSubmissions()` - 获取待审核投稿
- `getUserPendingCount($userId)` - 获取用户待审核数量
- `submitDialogue()` - 提交召唤词投稿
- `reviewSubmission()` - 审核投稿（接受/拒绝）
- `deleteSubmission()` - 删除投稿

#### 3.3 作者验证机制
- 集成作者管理系统，支持作者名称和别名验证
- 根据严格度设置进行不同级别的验证
- 支持卡片前缀匹配检查

### 4. 控制器层实现

**文件**: `includes/Controllers/DialogueController.php`

#### 4.1 用户功能
- `index()` - 召唤词一览页面
- `submit()` - 召唤词投稿页面
- `submitDialogue()` - 处理召唤词投稿

#### 4.2 管理员功能
- `admin()` - 管理员召唤词管理页面
- `reviewSubmission()` - 审核召唤词投稿
- `deleteSubmission()` - 删除投稿
- `addDialogue()` - 直接添加召唤词
- `editDialogue()` - 编辑现有召唤词
- `deleteDialogue()` - 删除召唤词

#### 4.3 权限控制
- 普通用户：查看召唤词、投稿召唤词
- 管理员（等级1+）：审核投稿、直接管理召唤词

### 5. 视图层实现

#### 5.1 召唤词一览页面
**文件**: `includes/Views/dialogues/index.php`

- 显示所有现有召唤词
- 卡片信息展示（ID、名称、类型、种族、属性）
- 卡片名称可点击跳转到详情页面
- 支持临时路径警告显示

#### 5.2 召唤词投稿页面
**文件**: `includes/Views/dialogues/submit.php`

- 投稿表单（卡片ID、召唤词内容、作者ID、用户ID）
- 投稿说明和规则展示
- 前端验证（卡片ID格式、内容长度等）
- 根据严格度显示不同的验证说明

#### 5.3 管理员管理页面
**文件**: `includes/Views/dialogues/admin.php`

- 待审核投稿列表（支持接受/拒绝/删除）
- 前缀不匹配警告标识
- 直接添加召唤词功能
- 现有召唤词管理（编辑/删除）
- 拒绝投稿模态框

### 6. 导航菜单集成

**文件**: `includes/Views/layout.php`

- 主导航添加"召唤词一览"链接
- 管理员菜单添加"召唤词管理"链接（等级1+）

## 技术特性

### 1. 文件系统处理
- 自动检测原始路径权限
- 临时路径回退机制
- 自动复制现有数据到临时位置
- 详细的错误信息反馈

### 2. 作者验证系统
- 集成现有作者管理系统
- 支持作者名称和别名匹配
- 卡片前缀验证
- 可配置的严格度等级

### 3. 投稿管理
- 用户投稿数量限制
- 投稿状态跟踪（pending/approved/rejected）
- 管理员审核记录
- 拒绝原因记录

### 4. 用户体验
- 响应式设计
- 实时前端验证
- 详细的操作反馈
- 内联编辑功能

### 5. 安全性
- 所有用户输入都经过转义
- 权限验证
- SQL注入防护
- 文件操作安全检查

## 使用说明

### 1. 普通用户
1. **查看召唤词**：访问"召唤词一览"页面
2. **投稿召唤词**：
   - 点击"投稿召唤词"按钮
   - 填写卡片ID、召唤词内容、作者ID、用户ID
   - 提交等待审核

### 2. 管理员（等级1+）
1. **审核投稿**：
   - 访问"召唤词管理"页面
   - 查看待审核投稿列表
   - 选择接受、拒绝或删除投稿

2. **直接管理召唤词**：
   - 添加新召唤词
   - 编辑现有召唤词
   - 删除召唤词

## 配置选项

### 1. 文件路径
- `DIALOGUES_FILE_PATH`：召唤词文件路径
- 支持权限检查和临时路径回退

### 2. 投稿限制
- `MAX_PENDING_DIALOGUES_PER_USER`：用户同时投稿数量限制

### 3. 验证严格度
- `DIALOGUE_SUBMISSION_STRICTNESS`：投稿验证严格度
  - 0：无限制
  - 1：仅验证作者存在
  - 2：验证作者和前缀匹配

## 文件结构

```
config.php                                    # 配置文件（已扩展）
includes/Core/Database.php                    # 数据库表结构（已扩展）
includes/Models/DialogueModel.php             # 召唤词模型（新建）
includes/Controllers/DialogueController.php   # 召唤词控制器（新建）
includes/Views/dialogues/index.php            # 召唤词一览页面（新建）
includes/Views/dialogues/submit.php           # 召唤词投稿页面（新建）
includes/Views/dialogues/admin.php            # 管理员管理页面（新建）
includes/Views/layout.php                     # 导航菜单（已修改）
data/const/dialogues-custom.json              # 召唤词数据文件
```

## 注意事项

1. **文件权限**：确保Web服务器对召唤词文件目录有读写权限
2. **作者管理**：需要先配置作者管理系统才能使用严格验证
3. **数据备份**：建议定期备份召唤词文件
4. **编码支持**：文件使用UTF-8编码，支持中文字符

## 问题排查和修复

### 问题：路由无法正常工作
**现象**:
1. 访问"召唤词一览"（/?controller=dialogue）时显示卡片检索页面
2. 访问"召唤词管理"（?controller=dialogue&action=admin）时返回404 Not Found

**排查过程**:
1. **检查控制器映射**: 发现index.php中的$controllerMap缺少'dialogue' => 'DialogueController'映射
2. **检查类文件**: 确认DialogueController.php和DialogueModel.php文件存在且语法正确
3. **检查依赖**: 确认AuthorMapping、CardParser等依赖类正常工作

**修复内容**:
1. **添加路由映射** (`index.php`):
   ```php
   $controllerMap = [
       'card' => 'CardController',
       'vote' => 'VoteController',
       'admin' => 'AdminController',
       'banlist' => 'BanlistController',
       'author' => 'AuthorController',
       'card_ranking' => 'CardRankingController',
       'dialogue' => 'DialogueController'  // 新增
   ];
   ```

2. **增强错误处理** (`includes/Controllers/DialogueController.php`):
   - 在index()和admin()方法中添加try-catch错误处理
   - 显示详细的错误信息和堆栈跟踪，便于调试

**根本原因**:
在index.php的控制器映射数组中遗漏了dialogue控制器的映射，导致路由系统无法找到对应的控制器类。

### 问题：数据库表不存在错误
**现象**:
访问"召唤词管理"时出现SQL错误：`SQLSTATE[HY000]: General error: 1 no such table: users`

**排查过程**:
1. **检查数据库结构**: 发现RAMSAY系统中没有`users`表
2. **检查用户管理**: 确认系统使用配置文件中的`ADMIN_CONFIG`管理用户，而不是数据库表
3. **检查SQL查询**: 发现`getPendingSubmissions()`方法尝试LEFT JOIN不存在的users表

**修复内容**:
1. **修改SQL查询** (`includes/Models/DialogueModel.php`):
   ```php
   // 修改前：尝试连接不存在的users表
   SELECT ds.*, u.username
   FROM dialogue_submissions ds
   LEFT JOIN users u ON ds.user_id = u.id
   WHERE ds.status = "pending"

   // 修改后：只查询dialogue_submissions表
   SELECT *
   FROM dialogue_submissions
   WHERE status = "pending"
   ```

2. **调整控制器逻辑** (`includes/Controllers/DialogueController.php`):
   - 在admin()方法中为每个投稿添加username字段
   - 使用user_id作为username的值（因为系统中没有独立的用户名）

**根本原因**:
DialogueModel中的getPendingSubmissions()方法尝试连接不存在的users表，而RAMSAY系统使用配置文件管理用户而不是数据库表。

## 后续扩展建议

1. 支持多条召唤词per卡片
2. 添加召唤词分类功能
3. 支持召唤词的点赞/评分系统
4. 添加召唤词搜索功能
5. 支持批量导入/导出功能
