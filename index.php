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
$controllerName = isset($_GET['controller']) ? $_GET['controller'] : 'card';
$methodName = isset($_GET['action']) ? $_GET['action'] : 'index';
$params = [];

// 映射控制器名称到类名
$controllerMap = [
    'card' => 'CardController',
    'vote' => 'VoteController',
    'admin' => 'AdminController',
    'banlist' => 'BanlistController'
];

// 特殊路由处理
if ($controllerName === 'vote' && isset($_GET['id'])) {
    // 如果是投票链接，则调用vote方法
    $params = [$_GET['id']];
    $methodName = 'vote';
}

// 确定控制器类名
$controllerClass = isset($controllerMap[$controllerName]) ? $controllerMap[$controllerName] : 'CardController';

// 创建控制器实例
$controller = new $controllerClass();

// 调用方法
if (method_exists($controller, $methodName)) {
    call_user_func_array([$controller, $methodName], $params);
} else {
    // 如果方法不存在，则显示404页面
    header('HTTP/1.0 404 Not Found');
    echo '404 Not Found';
}
