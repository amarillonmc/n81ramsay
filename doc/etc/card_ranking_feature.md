# 卡片排行榜功能文档

## 功能概述

卡片排行榜功能通过解析服务器记录的卡组文件（YDK文件），分析玩家使用的卡片情况，生成卡片使用率排行榜。该功能可以帮助玩家了解当前环境中最流行的卡片，为构筑卡组提供参考。

## 主要特性

1. **卡组文件解析**
   - 解析DECK_LOG_PATH目录下的YDK文件
   - 支持解析文件名格式：`YYYY-MM-DD HH-mm-ss [进程ID] [位置] [客户端IP最后几位] [玩家名].ydk`
   - 区分主卡组和副卡组卡片

2. **卡片使用率统计**
   - 支持按时间范围筛选：一周内、两周内、一个月内、全部
   - 统计卡片在主卡组中的使用情况（投入1/2/3张的数量）
   - 统计卡片在副卡组中的使用情况
   - 计算卡片的总体使用率

3. **排行榜展示**
   - 热门卡片展示：支持显示前3名/前7名/前10名
   - 详细统计展示：支持显示前10名/前30名/前50名/全部
   - 显示卡片ID、卡名、类别、使用情况和使用率
   - 卡名超链接至卡片详情页面

4. **TCG卡片处理**
   - 支持从TCG_CARD_DATA_PATH指定的数据库中查找TCG卡片
   - TCG卡片详情页面特殊处理：不显示卡图、作者显示为"TCG/OCG卡片"
   - 可配置是否允许对TCG卡发起禁卡投票

5. **缓存机制**
   - 避免频繁重新生成统计数据，提高性能
   - 管理员可以强制更新统计信息

## 配置项

在`config.php`中添加了以下配置项：

```php
// TCG卡片数据位置
if (!defined('TCG_CARD_DATA_PATH')) {
    define('TCG_CARD_DATA_PATH', __DIR__ . '/assets/cards.cdb');
}

// 是否允许对TCG卡发起禁卡投票
if (!defined('ALLOW_TCG_CARD_VOTING')) {
    define('ALLOW_TCG_CARD_VOTING', false);
}

// 服务器记录卡组文件存放位置
if (!defined('DECK_LOG_PATH')) {
    define('DECK_LOG_PATH', __DIR__ . '/deck_log');
}

// 卡片排行榜配置
if (!defined('CARD_RANKING_ENABLED')) {
    define('CARD_RANKING_ENABLED', false); // 是否启用卡片排行榜功能
}
if (!defined('CARD_RANKING_CACHE_DAYS')) {
    define('CARD_RANKING_CACHE_DAYS', 7); // 卡片排行榜缓存天数，超过此天数将重新生成
}
```

同时，添加了`config.user.php`文件，用于覆盖默认配置：

```php
// 当config.php和config.user.php同时存在相同的配置时，config.user.php中的配置值优先
```

## 技术实现

1. **核心类**
   - `DeckParser`：解析卡组文件，提取卡片使用情况
   - `CardRanking`：生成卡片使用率排行榜

2. **模型类**
   - `CardRankingModel`：处理卡片排行榜相关的数据操作

3. **控制器**
   - `CardRankingController`：处理卡片排行榜相关的请求

4. **视图**
   - `card_ranking/index.php`：卡片排行榜页面

## 使用说明

1. 在`config.php`中将`CARD_RANKING_ENABLED`设置为`true`启用功能
2. 确保`DECK_LOG_PATH`指向正确的卡组文件目录
3. 访问卡片排行榜页面：`?controller=card_ranking`
4. 可以通过下拉菜单选择时间范围和显示数量
5. 管理员可以通过"更新排行榜"按钮强制更新统计信息

## 注意事项

1. 当选择"全部"选项时，可能会显示大量数据，可能影响页面加载性能
2. 建议在服务器负载较高时，限制用户选择"全部"选项的权限
3. 确保`TCG_CARD_DATA_PATH`配置项正确指向TCG卡片数据库文件
4. 确保`ALLOW_TCG_CARD_VOTING`配置项正确设置，控制是否允许对TCG卡片发起禁卡投票
