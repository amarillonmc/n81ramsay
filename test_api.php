<?php
// 简单的API测试页面
require_once 'config.php';
require_once 'includes/autoload.php';

// 设置调试模式
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

echo "<h1>API测试页面</h1>";

// 测试API控制器
try {
    $apiController = new ApiController();
    
    // 模拟GET参数
    $_GET['setcode'] = isset($_GET['setcode']) ? $_GET['setcode'] : '0x1';
    
    echo "<h2>测试系列卡片API</h2>";
    echo "<p>测试setcode: " . $_GET['setcode'] . "</p>";
    
    // 捕获输出
    ob_start();
    $apiController->getSeriesCards();
    $output = ob_get_clean();
    
    echo "<h3>API响应:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // 尝试解析JSON
    $data = json_decode($output, true);
    if ($data) {
        echo "<h3>解析后的数据:</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>JSON解析失败</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>直接测试Card模型</h2>";

try {
    $cardModel = new Card();
    $setcode = hexdec(str_replace('0x', '', $_GET['setcode']));
    
    echo "<p>十进制setcode: " . $setcode . "</p>";
    
    $cards = $cardModel->getCardsBySetcode($setcode);
    
    echo "<p>找到 " . count($cards) . " 张卡片</p>";
    
    if (!empty($cards)) {
        echo "<h3>卡片列表:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>名称</th><th>Setcode</th><th>数据库</th></tr>";
        foreach (array_slice($cards, 0, 10) as $card) { // 只显示前10张
            echo "<tr>";
            echo "<td>" . $card['id'] . "</td>";
            echo "<td>" . htmlspecialchars($card['name']) . "</td>";
            echo "<td>0x" . dechex($card['setcode']) . "</td>";
            echo "<td>" . $card['database_file'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (count($cards) > 10) {
            echo "<p>... 还有 " . (count($cards) - 10) . " 张卡片</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Card模型错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<form method='get'>";
echo "<label>测试其他setcode: <input type='text' name='setcode' value='" . htmlspecialchars($_GET['setcode']) . "' placeholder='例如: 0x1, 0x2, 0x10'></label>";
echo "<input type='submit' value='测试'>";
echo "</form>";
?>
