<?php
// 调试路由问题
require_once 'config.php';

echo "<h1>路由调试</h1>";

// 模拟API请求
$_GET['controller'] = 'api';
$_GET['action'] = 'getSeriesCards';
$_GET['setcode'] = '1';

echo "<h2>模拟请求参数:</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// 自动加载类
spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/includes/Core/' . $className . '.php',
        __DIR__ . '/includes/Models/' . $className . '.php',
        __DIR__ . '/includes/Controllers/' . $className . '.php'
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            echo "<p>加载类: $className 从 $path</p>";
            require_once $path;
            return;
        }
    }
    echo "<p style='color: red;'>无法找到类: $className</p>";
});

echo "<h2>测试类加载:</h2>";

// 测试ApiController是否可以加载
try {
    echo "<p>尝试创建ApiController...</p>";
    $apiController = new ApiController();
    echo "<p style='color: green;'>ApiController创建成功</p>";
    
    // 检查方法是否存在
    if (method_exists($apiController, 'getSeriesCards')) {
        echo "<p style='color: green;'>getSeriesCards方法存在</p>";
    } else {
        echo "<p style='color: red;'>getSeriesCards方法不存在</p>";
    }
    
    // 测试方法调用
    echo "<h3>测试API调用:</h3>";
    ob_start();
    $apiController->getSeriesCards();
    $output = ob_get_clean();
    
    echo "<p>API输出:</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>测试URL构建:</h2>";
$testUrl = BASE_URL . '?controller=api&action=getSeriesCards&setcode=1';
echo "<p>测试URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";

echo "<h2>检查BASE_URL配置:</h2>";
echo "<p>BASE_URL: " . BASE_URL . "</p>";
?>
