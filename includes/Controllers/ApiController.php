<?php
/**
 * API控制器
 *
 * 处理AJAX请求和API调用
 */
class ApiController {
    /**
     * 卡片模型
     * @var Card
     */
    private $cardModel;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->cardModel = new Card();
    }

    /**
     * 获取系列卡片
     */
    public function getSeriesCards() {
        // 设置响应头
        header('Content-Type: application/json');

        // 获取参数，支持十六进制格式
        $setcodeParam = isset($_GET['setcode']) ? trim($_GET['setcode']) : '0';

        // 处理十六进制格式（如 0x344a）
        if (strpos($setcodeParam, '0x') === 0) {
            $setcode = hexdec(substr($setcodeParam, 2));
        } else {
            $setcode = (int)$setcodeParam;
        }

        // 调试信息
        $debug = defined('DEBUG_MODE') && DEBUG_MODE;

        if ($debug) {
            error_log("API getSeriesCards called with setcode param: " . $setcodeParam . " -> converted to: " . $setcode);
        }

        if ($setcode <= 0) {
            echo json_encode([
                'success' => false,
                'message' => '无效的系列代码',
                'debug' => $debug ? [
                    'original_param' => $setcodeParam,
                    'converted_setcode' => $setcode
                ] : null
            ]);
            return;
        }

        try {
            // 获取系列卡片
            $cards = $this->cardModel->getCardsBySetcode($setcode);

            if ($debug) {
                error_log("API getSeriesCards found " . count($cards) . " cards");
            }

            echo json_encode([
                'success' => true,
                'cards' => $cards,
                'count' => count($cards),
                'debug' => $debug ? [
                    'original_param' => $setcodeParam,
                    'converted_setcode' => $setcode,
                    'found_cards' => count($cards)
                ] : null
            ]);
        } catch (Exception $e) {
            if ($debug) {
                error_log("API getSeriesCards error: " . $e->getMessage());
                error_log("API getSeriesCards trace: " . $e->getTraceAsString());
            }

            echo json_encode([
                'success' => false,
                'message' => '获取系列卡片失败：' . $e->getMessage(),
                'debug' => $debug ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ]);
        }
    }

    /**
     * 获取卡片详细信息
     */
    public function getCardDetail() {
        // 设置响应头
        header('Content-Type: application/json');

        // 获取卡片ID参数
        $cardId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($cardId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => '无效的卡片ID'
            ]);
            return;
        }

        try {
            // 获取卡片详细信息
            $card = $this->cardModel->getCardById($cardId);

            if (!$card) {
                echo json_encode([
                    'success' => false,
                    'message' => '卡片不存在'
                ]);
                return;
            }

            // 返回卡片信息
            echo json_encode([
                'success' => true,
                'card' => [
                    'id' => $card['id'],
                    'name' => $card['name'],
                    'desc' => $card['desc'],
                    'type_text' => $card['type_text'],
                    'race_text' => $card['race_text'],
                    'attribute_text' => $card['attribute_text'],
                    'level_text' => $card['level_text'],
                    'atk' => $card['atk'],
                    'def' => $card['def'],
                    'image_path' => $card['image_path']
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '获取卡片信息失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 测试方法
     */
    public function test() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'API测试成功',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 默认方法
     */
    public function index() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '无效的API请求'
        ]);
    }
}
