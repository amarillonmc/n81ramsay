<?php
// 简单的setcode调试
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>简单Setcode调试</h1>";

$targetSetcode = 13386; // 0x344a
echo "<p>目标setcode: $targetSetcode (0x" . dechex($targetSetcode) . ")</p>";

// 直接测试Card模型的方法
try {
    require_once 'includes/Core/Utils.php';
    require_once 'includes/Core/Database.php';
    require_once 'includes/Core/CardParser.php';
    require_once 'includes/Models/Card.php';
    
    echo "<p style='color: green;'>✓ 所有类加载成功</p>";
    
    $cardModel = new Card();
    echo "<p style='color: green;'>✓ Card模型创建成功</p>";
    
    // 测试getCardsBySetcode方法
    echo "<h2>测试getCardsBySetcode方法</h2>";
    $cards = $cardModel->getCardsBySetcode($targetSetcode);
    
    echo "<p>找到 " . count($cards) . " 张卡片</p>";
    
    if (!empty($cards)) {
        echo "<h3>卡片列表 (前10张):</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>名称</th><th>Setcode</th><th>Setcode (十六进制)</th><th>数据库</th></tr>";
        
        foreach (array_slice($cards, 0, 10) as $card) {
            echo "<tr>";
            echo "<td>" . $card['id'] . "</td>";
            echo "<td>" . htmlspecialchars($card['name']) . "</td>";
            echo "<td>" . $card['setcode'] . "</td>";
            echo "<td>0x" . dechex($card['setcode']) . "</td>";
            echo "<td>" . $card['database_file'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (count($cards) > 10) {
            echo "<p>... 还有 " . (count($cards) - 10) . " 张卡片</p>";
        }
    } else {
        echo "<p style='color: red;'>没有找到匹配的卡片</p>";
        
        // 尝试直接查询数据库
        echo "<h3>直接数据库查询测试:</h3>";
        
        // 获取数据库文件列表
        $dbFiles = [];
        if (defined('CARD_DATA_PATH') && is_dir(CARD_DATA_PATH)) {
            $files = glob(CARD_DATA_PATH . '/*.cdb');
            foreach ($files as $file) {
                $dbFiles[] = $file;
            }
        }
        
        echo "<p>数据库文件数量: " . count($dbFiles) . "</p>";
        
        foreach ($dbFiles as $dbFile) {
            $filename = basename($dbFile);
            
            // 跳过TCG数据库
            if (defined('TCG_CARD_DATA_PATH') && $filename === basename(TCG_CARD_DATA_PATH)) {
                echo "<p>跳过TCG数据库: $filename</p>";
                continue;
            }
            
            echo "<h4>测试数据库: $filename</h4>";
            
            try {
                $db = new PDO('sqlite:' . $dbFile);
                
                // 测试不同的查询方式
                $queries = [
                    '精确匹配' => "SELECT COUNT(*) as count FROM datas WHERE setcode = $targetSetcode",
                    '位运算匹配' => "SELECT COUNT(*) as count FROM datas WHERE (setcode & $targetSetcode) = $targetSetcode",
                    '反向位运算' => "SELECT COUNT(*) as count FROM datas WHERE ($targetSetcode & setcode) = setcode AND setcode > 0",
                    '包含匹配' => "SELECT COUNT(*) as count FROM datas WHERE (setcode & $targetSetcode) > 0"
                ];
                
                echo "<table border='1'>";
                echo "<tr><th>查询方式</th><th>结果数量</th></tr>";
                
                foreach ($queries as $name => $sql) {
                    try {
                        $result = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
                        echo "<tr><td>$name</td><td>" . $result['count'] . "</td></tr>";
                    } catch (Exception $e) {
                        echo "<tr><td>$name</td><td style='color: red;'>错误: " . $e->getMessage() . "</td></tr>";
                    }
                }
                echo "</table>";
                
                // 显示一些setcode示例
                echo "<h5>数据库中的setcode示例:</h5>";
                $sql = "SELECT DISTINCT setcode, COUNT(*) as count FROM datas WHERE setcode > 0 GROUP BY setcode ORDER BY count DESC LIMIT 10";
                $stmt = $db->query($sql);
                $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table border='1'>";
                echo "<tr><th>Setcode</th><th>十六进制</th><th>卡片数量</th></tr>";
                foreach ($examples as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['setcode'] . "</td>";
                    echo "<td>0x" . dechex($row['setcode']) . "</td>";
                    echo "<td>" . $row['count'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>数据库连接错误: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>配置信息</h2>";
echo "<p>CARD_DATA_PATH: " . (defined('CARD_DATA_PATH') ? CARD_DATA_PATH : '未定义') . "</p>";
echo "<p>TCG_CARD_DATA_PATH: " . (defined('TCG_CARD_DATA_PATH') ? TCG_CARD_DATA_PATH : '未定义') . "</p>";
echo "<p>DEBUG_MODE: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'true' : 'false') : '未定义') . "</p>";

echo "<hr>";
echo "<h2>测试API调用</h2>";
$apiUrl = BASE_URL . '?controller=api&action=getSeriesCards&setcode=0x344a';
echo "<p><a href='$apiUrl' target='_blank'>$apiUrl</a></p>";
?>
