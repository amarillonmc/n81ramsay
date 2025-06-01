<?php
// 独立的API测试，不依赖主路由系统
header('Content-Type: text/html; charset=utf-8');

echo "<h1>独立API测试</h1>";

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 加载配置
    require_once __DIR__ . '/config.php';
    echo "<p style='color: green;'>✓ 配置文件加载成功</p>";
    
    // 手动加载所有必要的类
    $classFiles = [
        'includes/Core/Utils.php',
        'includes/Core/Database.php', 
        'includes/Core/CardParser.php',
        'includes/Models/Card.php',
        'includes/Controllers/ApiController.php'
    ];
    
    foreach ($classFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
            echo "<p style='color: green;'>✓ 加载: $file</p>";
        } else {
            echo "<p style='color: red;'>✗ 文件不存在: $file</p>";
            throw new Exception("Required file not found: $file");
        }
    }
    
    // 测试ApiController创建
    echo "<h2>测试ApiController</h2>";
    $api = new ApiController();
    echo "<p style='color: green;'>✓ ApiController实例创建成功</p>";
    
    // 检查方法是否存在
    $methods = ['test', 'getSeriesCards', 'index'];
    foreach ($methods as $method) {
        if (method_exists($api, $method)) {
            echo "<p style='color: green;'>✓ 方法存在: $method</p>";
        } else {
            echo "<p style='color: red;'>✗ 方法不存在: $method</p>";
        }
    }
    
    // 测试test方法
    echo "<h2>测试test方法</h2>";
    ob_start();
    $api->test();
    $output = ob_get_clean();
    echo "<p>输出:</p><pre>" . htmlspecialchars($output) . "</pre>";
    
    // 测试getSeriesCards方法
    echo "<h2>测试getSeriesCards方法</h2>";
    $_GET['setcode'] = '1';
    ob_start();
    $api->getSeriesCards();
    $output = ob_get_clean();
    echo "<p>输出:</p><pre>" . htmlspecialchars($output) . "</pre>";
    
    // 解析JSON
    $data = json_decode($output, true);
    if ($data) {
        echo "<p style='color: green;'>✓ JSON解析成功</p>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>✗ JSON解析失败</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>异常: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<p style='color: red;'>致命错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>环境信息</h2>";
echo "<p>PHP版本: " . PHP_VERSION . "</p>";
echo "<p>当前目录: " . __DIR__ . "</p>";
echo "<p>BASE_URL: " . (defined('BASE_URL') ? BASE_URL : '未定义') . "</p>";
echo "<p>DEBUG_MODE: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'true' : 'false') : '未定义') . "</p>";
?>
