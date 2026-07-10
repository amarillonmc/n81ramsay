<?php
/**
 * srvpro2 卡组快照解码器
 *
 * srvpro2 将 YGOProDeck::toUpdateDeckPayload() 的结果进行 base64 编码后
 * 写入 PostgreSQL。payload 由两个小端 uint32 数量和连续的卡片 ID 组成。
 */
class Srvpro2DeckCodec {
    /**
     * 单副卡组允许的最大卡片数
     * @var int
     */
    private $maxCards;

    /**
     * 构造函数
     *
     * @param int $maxCards 单副卡组允许的最大卡片数
     */
    public function __construct($maxCards = 200) {
        $this->maxCards = max(1, (int)$maxCards);
    }

    /**
     * 解码 srvpro2 的 base64 卡组快照
     *
     * payload 布局：
     * - uint32le: 主卡组与额外卡组的卡片总数
     * - uint32le: 副卡组卡片数
     * - uint32le[]: 主卡组、额外卡组、副卡组的卡片 ID
     *
     * @param string $encodedPayload base64 编码的 payload
     * @param int $mainDeckCount srvpro2 单独保存的主卡组卡片数
     * @return array 解码后的 main、extra、side
     * @throws UnexpectedValueException 数据格式无效时抛出
     */
    public function decodeBase64($encodedPayload, $mainDeckCount) {
        if (!is_string($encodedPayload) || $encodedPayload === '') {
            throw new UnexpectedValueException('卡组快照为空');
        }

        $payload = base64_decode($encodedPayload, true);
        if ($payload === false) {
            throw new UnexpectedValueException('卡组快照不是有效的 base64');
        }

        return $this->decodePayload($payload, $mainDeckCount);
    }

    /**
     * 解码二进制卡组 payload
     *
     * @param string $payload 二进制 payload
     * @param int $mainDeckCount 主卡组卡片数
     * @return array 解码后的 main、extra、side
     * @throws UnexpectedValueException 数据格式无效时抛出
     */
    public function decodePayload($payload, $mainDeckCount) {
        if (!is_string($payload) || strlen($payload) < 8) {
            throw new UnexpectedValueException('卡组 payload 长度不足');
        }

        if (!is_numeric($mainDeckCount)) {
            throw new UnexpectedValueException('主卡组数量无效');
        }

        $mainDeckCount = (int)$mainDeckCount;
        $counts = unpack('Vcombined_count/Vside_count', substr($payload, 0, 8));
        $combinedCount = (int)$counts['combined_count'];
        $sideCount = (int)$counts['side_count'];
        $totalCount = $combinedCount + $sideCount;

        if ($mainDeckCount < 0 || $mainDeckCount > $combinedCount) {
            throw new UnexpectedValueException('主卡组数量超出 payload 范围');
        }

        if ($totalCount < 0 || $totalCount > $this->maxCards) {
            throw new UnexpectedValueException('卡组卡片数量超出安全限制');
        }

        $expectedLength = 8 + ($totalCount * 4);
        if (strlen($payload) !== $expectedLength) {
            throw new UnexpectedValueException('卡组 payload 长度与卡片数量不一致');
        }

        $cards = [];
        if ($totalCount > 0) {
            $unpackedCards = unpack('V*', substr($payload, 8));
            if (!is_array($unpackedCards) || count($unpackedCards) !== $totalCount) {
                throw new UnexpectedValueException('无法读取卡组卡片 ID');
            }
            $cards = array_values($unpackedCards);
        }

        return [
            'main' => array_slice($cards, 0, $mainDeckCount),
            'extra' => array_slice($cards, $mainDeckCount, $combinedCount - $mainDeckCount),
            'side' => array_slice($cards, $combinedCount, $sideCount)
        ];
    }

    /**
     * 解码成排行榜沿用的数据结构
     *
     * 旧 YDK 解析逻辑会把 #main 与 #extra 一并作为非 SIDE 卡统计，
     * 新数据源维持相同口径，避免切换后排行榜含义发生变化。
     *
     * @param string $encodedPayload base64 编码的 payload
     * @param int $mainDeckCount srvpro2 单独保存的主卡组卡片数
     * @return array 包含 main（主卡组+额外卡组）与 side
     */
    public function decodeRankingDeck($encodedPayload, $mainDeckCount) {
        $deck = $this->decodeBase64($encodedPayload, $mainDeckCount);

        return [
            'main' => array_merge($deck['main'], $deck['extra']),
            'side' => $deck['side']
        ];
    }
}
