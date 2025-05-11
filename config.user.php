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

define('CARD_RANKING_ENABLED', true);