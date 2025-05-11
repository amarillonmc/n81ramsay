# 配置系统更新文档

## 配置优先级机制

为了使系统配置更加灵活，RAMSAY系统实现了配置优先级机制，允许用户通过`config.user.php`文件覆盖默认配置，而不需要修改原始的`config.php`文件。

### 主要特性

1. **配置文件优先级**
   - 当`config.php`和`config.user.php`同时存在相同的配置项时，`config.user.php`中的配置值优先
   - `config.php`中的配置项会先检查是否已在`config.user.php`中定义，如果已定义则不会重复定义

2. **配置检查机制**
   - 使用`if (!defined('CONFIG_NAME'))`条件判断，确保配置项不会被重复定义
   - 在`config.php`开头加载`config.user.php`，确保用户配置优先生效

### 新增配置项

以下是新增的主要配置项：

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

### config.user.php 示例

```php
<?php
/**
 * RAMSAY 用户配置文件
 *
 * 该文件包含用户自定义的配置项，会覆盖config.php中的默认配置
 * 当config.php和config.user.php同时存在相同的配置时，config.user.php中的配置值优先
 */

// 此文件中的配置会覆盖config.php中的默认配置
// 请在此处添加您的自定义配置

// 示例：修改调试模式
// define('DEBUG_MODE', false);

// 示例：修改TCG卡片数据位置
// define('TCG_CARD_DATA_PATH', __DIR__ . '/custom/tcg_cards.cdb');

// 示例：允许对TCG卡发起禁卡投票
// define('ALLOW_TCG_CARD_VOTING', true);

// 示例：修改服务器记录卡组文件存放位置
// define('DECK_LOG_PATH', __DIR__ . '/custom/deck_logs');
```

## 使用说明

1. 如果需要自定义配置，创建`config.user.php`文件
2. 在`config.user.php`中定义需要覆盖的配置项
3. 系统会自动加载`config.user.php`并应用其中的配置

## 注意事项

1. 不要直接修改`config.php`文件，而是通过`config.user.php`覆盖配置
2. 确保`config.user.php`中的配置项名称与`config.php`中的完全一致
3. 在升级系统时，`config.user.php`不会被覆盖，保证自定义配置的持久性
