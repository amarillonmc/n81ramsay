# RAMSAY 系统完整文档

## 目录

1. [系统概述](#系统概述)
2. [功能模块](#功能模块)
3. [技术架构](#技术架构)
4. [部署配置](#部署配置)
5. [最新功能](#最新功能)
6. [开发历史](#开发历史)
7. [故障排除](#故障排除)

## 系统概述

RAMSAY是一个专为no81游戏王DIY服务器设计的管理系统，旨在自动化和简化服务器的各种运营事务。该系统提供卡片检索、投票禁卡、禁卡表整理、作者光荣榜、服务器提示管理、召唤词管理等功能，帮助服务器管理员和玩家更高效地管理游戏环境。

### 系统特点

- 基于PHP开发，无需额外框架
- 使用SQLite数据库存储数据
- 响应式设计，适配各种设备
- 支持在IIS服务器上部署
- 模块化架构，易于扩展

## 功能模块

### 1. 卡片检索系统

**核心功能**：
- 支持按卡片ID或卡名搜索
- 显示卡片详细信息和卡图
- 查看卡片在各环境中的禁限状态
- 支持分页显示，提高大量卡片的浏览效率

**技术实现**：
- 卡片数据从CDB文件读取
- 支持多个卡片数据库文件
- 图片路径使用web URL格式
- 卡片类型、种族、属性支持位运算组合显示

### 2. 投票禁卡系统

**核心功能**：
- 用户可以发起对特定卡片的禁限投票
- 生成唯一投票链接供其他玩家参与
- 支持多种禁限状态（禁止、限制、准限制、无限制）
- 同一IP只能投一次票
- 支持多种投票结果处理模式

**技术实现**：
- 投票数据存储在SQLite数据库
- IP地址验证防止重复投票
- 支持投票者唯一标识生成
- 可配置的投票结果处理严格度

### 3. 禁卡表管理

**核心功能**：
- 管理员可以查看和管理所有投票
- 统计投票结果，生成禁卡表文本
- 支持生成lflist.conf格式的禁卡表
- 重置投票并推进投票周期

**权限要求**：管理员等级1以上

### 4. 作者光荣榜

**核心功能**：
- 展示所有卡片作者的统计数据
- 按投稿卡片数量从高到低排序
- 显示每位作者的禁卡比例和被禁系列数量
- 支持作者详情页面和卡片列表

**作者识别机制**：
1. 管理员录入的作者信息（优先级最高）
2. 卡片描述中的"DoItYourself/DIY by AuthorID"格式
3. strings.conf文件中的注释信息
4. 卡片ID前三位匹配

### 5. 服务器提示管理 ⭐ 新功能

**核心功能**：
- 管理员可以查看、添加、编辑、删除服务器提示
- 支持tips.json文件的完整管理
- 内联编辑功能，用户体验友好
- 自动权限检查和临时路径回退

**权限要求**：管理员等级2以上

**技术特性**：
- JSON格式化保存，支持中文
- 自动创建目录结构
- 详细的错误处理和调试信息
- 文件权限问题自动回退到临时目录

### 6. 召唤词管理系统 ⭐ 新功能

**核心功能**：
- 用户可以查看所有现有召唤词
- 用户可以投稿新的召唤词等待审核
- 管理员可以审核投稿（接受/拒绝）
- 管理员可以直接管理召唤词文件

**投稿验证系统**：
- **严格度0**：无限制，直接通过
- **严格度1**：仅验证作者存在
- **严格度2**：验证作者存在且卡片前缀匹配

**权限要求**：
- 普通用户：查看和投稿
- 管理员等级1以上：审核和管理

### 7. 卡片排行榜

**核心功能**：
- 基于deck log文件统计卡片使用频率
- 支持多种时间范围统计
- 可过滤TCG卡片，只显示DIY卡片
- 支持不同数量的排行榜显示

## 技术架构

### 环境要求

- **Web服务器**：IIS 7.0+
- **PHP版本**：7.0+
- **SQLite版本**：3.0+
- **磁盘空间**：根据卡片数据库大小，建议至少500MB

### 目录结构

```
/
├── index.php                 # 入口文件和路由
├── config.php               # 主配置文件
├── config.user.php          # 用户自定义配置（可选）
├── includes/
│   ├── Core/                # 核心类
│   │   ├── Database.php     # 数据库管理
│   │   ├── Auth.php         # 认证系统
│   │   └── Utils.php        # 工具类
│   ├── Models/              # 数据模型
│   │   ├── CardParser.php   # 卡片解析
│   │   ├── DialogueModel.php # 召唤词模型
│   │   └── ...
│   ├── Controllers/         # 控制器
│   │   ├── CardController.php
│   │   ├── DialogueController.php
│   │   └── ...
│   └── Views/               # 视图文件
│       ├── layout.php       # 主布局
│       ├── dialogues/       # 召唤词视图
│       └── ...
├── assets/                  # 静态资源
│   ├── css/
│   ├── js/
│   └── images/
├── data/                    # 数据目录
│   └── const/               # 配置数据
│       ├── tips.json        # 服务器提示
│       └── dialogues-custom.json # 召唤词
└── doc/                     # 文档目录
```

### 数据库表结构

```sql
-- 投票表
CREATE TABLE votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    card_id TEXT NOT NULL,
    voter_ip TEXT NOT NULL,
    vote_value INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 作者映射表
CREATE TABLE author_mappings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    card_prefix TEXT NOT NULL,
    author_name TEXT NOT NULL,
    alias TEXT,
    contact TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 召唤词投稿表
CREATE TABLE dialogue_submissions (
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
);
```

## 部署配置

### 基本部署步骤

1. **文件上传**：将程序文件上传到服务器
2. **目录配置**：确保根目录是index.php所在的位置
3. **IIS配置**：将默认文档设置为index.php
4. **权限设置**：确保PHP有足够的权限读写数据文件
5. **配置修改**：修改config.php中的配置项
6. **功能测试**：访问网站首页，检查系统是否正常运行

### 关键配置项

```php
// 基本配置
define('DEBUG_MODE', false);           // 生产环境设为false
define('BASE_URL', 'http://your-domain.com');
define('SITE_TITLE', 'RAMSAY管理系统');

// 卡片数据配置
define('CARD_DATA_PATH', '/path/to/cards');
define('DB_STRINGS_PATH', '/path/to/strings.conf');
define('EXCLUDED_CDB_FILES', ['Pre_Nerf_cards.cdb', 'SoundStageLib.cdb']);

// 文件路径配置
define('TIPS_FILE_PATH', __DIR__ . '/data/const/tips.json');
define('DIALOGUES_FILE_PATH', __DIR__ . '/data/const/dialogues-custom.json');

// 功能开关
define('CARD_RANKING_ENABLED', true);
define('AUTHOR_HALL_OF_FAME_ENABLED', true);

// 投稿限制
define('MAX_PENDING_DIALOGUES_PER_USER', 5);
define('DIALOGUE_SUBMISSION_STRICTNESS', 2);
```

### 权限配置

```php
// 管理员配置
define('ADMIN_CONFIG', [
    'admin1' => ['password' => 'hashed_password', 'level' => 2],
    'admin2' => ['password' => 'hashed_password', 'level' => 1]
]);
```

## 最新功能

### 2024年12月20日更新

#### 1. 服务器提示管理功能
- **功能**：完整的tips.json文件管理
- **权限**：管理员等级2以上
- **特性**：内联编辑、自动权限检查、临时路径回退

#### 2. 召唤词管理系统
- **功能**：用户投稿、管理员审核、直接管理
- **权限**：用户可投稿，管理员等级1以上可审核
- **特性**：多级验证、投稿数量限制、作者验证

#### 3. 系统优化
- **内存优化**：修复了多个内存泄漏问题
- **投票优化**：添加了无意义投票阻止功能
- **错误处理**：增强了错误报告和调试功能

## 开发历史

### 2023年11月 - 系统基础
- 创建MVC架构
- 实现卡片检索功能
- 添加分页和布局优化

### 2024年6月-7月 - 功能扩展
- 实现投票禁卡系统
- 添加作者光荣榜功能
- 优化界面和用户体验

### 2024年12月 - 重大更新
- 添加服务器提示管理
- 实现召唤词管理系统
- 系统性能和稳定性优化

## 故障排除

### 常见问题

#### 1. 文件权限问题
**现象**：保存失败、文件无法写入
**解决**：
```cmd
icacls "C:\path\to\ramsay\data" /grant Users:F /T
```

#### 2. 数据库表不存在
**现象**：SQL错误，表不存在
**解决**：检查Database.php中的表创建语句，确保数据库初始化正确

#### 3. 路由404错误
**现象**：访问特定控制器时返回404
**解决**：检查index.php中的$controllerMap是否包含对应的控制器映射

#### 4. 卡片图片无法显示
**现象**：卡片详情页面图片显示失败
**解决**：检查config.php中的图片路径配置，确保使用正确的web URL格式

### 调试模式

启用调试模式可以获得详细的错误信息：
```php
define('DEBUG_MODE', true);
```

调试模式下会显示：
- 详细的错误堆栈
- 文件权限信息
- 数据库查询日志
- 系统状态信息

---

**文档版本**：2024年12月20日
**系统版本**：RAMSAY v2.0
**维护者**：RAMSAY开发团队
