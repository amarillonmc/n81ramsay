<?php
/**
 * RAMSAY 入口文件
 *
 * 处理所有请求并路由到相应的控制器
 */

// 加载配置文件
require_once __DIR__ . '/config.php';

// 自动加载类
spl_autoload_register(function ($className) {
    // 定义类文件的可能路径
    $paths = [
        __DIR__ . '/includes/Core/' . $className . '.php',
        __DIR__ . '/includes/Models/' . $className . '.php',
        __DIR__ . '/includes/Controllers/' . $className . '.php'
    ];

    // 尝试加载类文件
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// 使用查询参数来确定控制器和方法
$defaultController = 'card';
$defaultMethod = 'index';
if (defined('HOME_PAGE')) {
    switch (HOME_PAGE) {
        case 'home':
            $defaultController = 'home';
            break;
        case 'vote':
            $defaultController = 'vote';
            break;
        case 'card':
        default:
            $defaultController = 'card';
    }
}

$controllerName = isset($_GET['controller']) ? $_GET['controller'] : $defaultController;
$methodName = isset($_GET['action']) ? $_GET['action'] : $defaultMethod;
$params = [];

// 调试信息
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("Route debug - Controller: $controllerName, Method: $methodName");
}

// 映射控制器名称到类名
$controllerMap = [
    'card' => 'CardController',
    'vote' => 'VoteController',
    'home' => 'HomeController',
    'admin' => 'AdminController',
    'banlist' => 'BanlistController',
    'author' => 'AuthorController',
    'card_ranking' => 'CardRankingController',
    'dialogue' => 'DialogueController',
    'api' => 'ApiController'
];

// 特殊路由处理
if ($controllerName === 'vote' && isset($_GET['id']) && !isset($_GET['action'])) {
    // 如果是投票链接且没有指定action，则调用vote方法
    $params = [$_GET['id']];
    $methodName = 'vote';
}

// 确定控制器类名
$controllerClass = isset($controllerMap[$controllerName]) ? $controllerMap[$controllerName] : 'CardController';

// 调试信息
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("Route debug - Controller class: $controllerClass");
}

try {
    // 创建控制器实例
    $controller = new $controllerClass();

    // 调试信息
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Route debug - Controller instance created successfully");
        error_log("Route debug - Method exists: " . (method_exists($controller, $methodName) ? 'yes' : 'no'));
    }

    // 调用方法
    if (method_exists($controller, $methodName)) {
        call_user_func_array([$controller, $methodName], $params);
    } else {
        // 如果方法不存在，则显示404页面
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Route debug - Method $methodName not found in $controllerClass");
        }
        header('HTTP/1.0 404 Not Found');
        echo '404 Not Found';
    }
} catch (Exception $e) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Route debug - Exception: " . $e->getMessage());
        error_log("Route debug - Trace: " . $e->getTraceAsString());
    }
    header('HTTP/1.0 500 Internal Server Error');
    echo '500 Internal Server Error';
} catch (Error $e) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Route debug - Fatal Error: " . $e->getMessage());
        error_log("Route debug - Trace: " . $e->getTraceAsString());
    }
    header('HTTP/1.0 500 Internal Server Error');
    echo '500 Internal Server Error';
}
