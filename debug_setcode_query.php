<?php
// 调试setcode查询
require_once 'config.php';

// 手动加载必要的类
require_once 'includes/Core/Utils.php';
require_once 'includes/Core/Database.php';
require_once 'includes/Core/CardParser.php';
require_once 'includes/Models/Card.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Setcode查询调试</h1>";

$targetSetcode = 13386; // 0x344a
echo "<p>目标setcode: $targetSetcode (0x" . dechex($targetSetcode) . ")</p>";

try {
    // 通过Card模型来访问CardParser
    $cardModel = new Card();

    // 使用反射来访问私有方法和属性
    $reflection = new ReflectionClass($cardModel);
    $cardParserProperty = $reflection->getProperty('cardParser');
    $cardParserProperty->setAccessible(true);
    $cardParser = $cardParserProperty->getValue($cardModel);

    // 获取数据库文件列表
    $dbFiles = [];
    if (defined('CARD_DATA_PATH') && is_dir(CARD_DATA_PATH)) {
        $files = glob(CARD_DATA_PATH . '/*.cdb');
        foreach ($files as $file) {
            $dbFiles[] = $file;
        }
    }
    
    echo "<h2>数据库文件列表:</h2>";
    foreach ($dbFiles as $dbFile) {
        echo "<p>" . basename($dbFile) . " - " . $dbFile . "</p>";
    }
    
    echo "<h2>查询结果:</h2>";
    
    foreach ($dbFiles as $dbFile) {
        // 跳过TCG数据库
        if (basename($dbFile) === basename(TCG_CARD_DATA_PATH)) {
            echo "<h3>" . basename($dbFile) . " (跳过TCG数据库)</h3>";
            continue;
        }
        
        echo "<h3>" . basename($dbFile) . "</h3>";
        
        try {
            // 直接连接到SQLite数据库
            $db = new PDO('sqlite:' . $dbFile);
            
            // 首先查看数据库中所有的setcode值
            echo "<h4>数据库中的setcode分布:</h4>";
            $sql = "SELECT DISTINCT d.setcode, COUNT(*) as count FROM datas d WHERE d.setcode > 0 GROUP BY d.setcode ORDER BY d.setcode";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $setcodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1'>";
            echo "<tr><th>Setcode (十进制)</th><th>Setcode (十六进制)</th><th>卡片数量</th><th>位运算匹配</th></tr>";
            
            $matchCount = 0;
            foreach ($setcodes as $row) {
                $setcode = $row['setcode'];
                $count = $row['count'];
                $hex = '0x' . dechex($setcode);
                
                // 检查位运算匹配
                $bitwiseMatch = ($setcode & $targetSetcode) == $targetSetcode;
                $exactMatch = $setcode == $targetSetcode;
                
                $matchType = '';
                if ($exactMatch) {
                    $matchType = '精确匹配';
                    $matchCount += $count;
                } elseif ($bitwiseMatch) {
                    $matchType = '位运算匹配';
                    $matchCount += $count;
                }
                
                if ($matchType || $setcode == $targetSetcode) {
                    echo "<tr style='background-color: " . ($exactMatch ? '#90EE90' : '#FFE4B5') . "'>";
                    echo "<td>$setcode</td>";
                    echo "<td>$hex</td>";
                    echo "<td>$count</td>";
                    echo "<td>$matchType</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
            
            echo "<p>总匹配卡片数: $matchCount</p>";
            
            // 测试不同的查询方式
            echo "<h4>测试不同查询方式:</h4>";
            
            $queries = [
                '精确匹配' => "SELECT COUNT(*) as count FROM datas d WHERE d.setcode = :setcode",
                '位运算匹配' => "SELECT COUNT(*) as count FROM datas d WHERE (d.setcode & :setcode) = :setcode",
                '包含匹配' => "SELECT COUNT(*) as count FROM datas d WHERE (d.setcode & :setcode) > 0",
                '反向位运算' => "SELECT COUNT(*) as count FROM datas d WHERE (:setcode & d.setcode) = d.setcode"
            ];
            
            echo "<table border='1'>";
            echo "<tr><th>查询方式</th><th>结果数量</th></tr>";
            
            foreach ($queries as $name => $sql) {
                $stmt = $db->prepare($sql);
                $stmt->execute(['setcode' => $targetSetcode]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<tr><td>$name</td><td>" . $result['count'] . "</td></tr>";
            }
            echo "</table>";
            
            // 显示一些具体的卡片示例
            echo "<h4>具体卡片示例 (前10张):</h4>";
            $sql = "
                SELECT d.id, d.setcode, t.name 
                FROM datas d 
                JOIN texts t ON d.id = t.id 
                WHERE (d.setcode & :setcode) = :setcode 
                LIMIT 10
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute(['setcode' => $targetSetcode]);
            $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($examples)) {
                echo "<table border='1'>";
                echo "<tr><th>卡片ID</th><th>卡名</th><th>Setcode</th><th>Setcode (十六进制)</th></tr>";
                foreach ($examples as $card) {
                    echo "<tr>";
                    echo "<td>" . $card['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($card['name']) . "</td>";
                    echo "<td>" . $card['setcode'] . "</td>";
                    echo "<td>0x" . dechex($card['setcode']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>没有找到匹配的卡片</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>数据库错误: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>测试API调用</h2>";
$apiUrl = BASE_URL . '?controller=api&action=getSeriesCards&setcode=0x344a';
echo "<p><a href='$apiUrl' target='_blank'>$apiUrl</a></p>";
?>
