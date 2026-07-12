<?php
/**
 * 主页控制器
 */
class HomeController {
    /**
     * @var Card
     */
    private $cardModel;

    public function __construct() {
        $this->cardModel = new Card();
    }

    /**
     * 主页
     */
    public function index() {
        // 随机抽取一张卡片
        $card = $this->cardModel->getRandomCard();

        $seriesCards = [];
        if ($card && !empty($card['manual_series_name'])) {
            $seriesCards = $this->cardModel->getCardsByManualSeries(
                $card['manual_series_name'],
                $card['id'],
                10
            );
        } elseif ($card && !empty($card['setcode'])) {
            $seriesCards = $this->cardModel->getCardsBySetcode($card['setcode']);
            // 排除当前卡并随机取10张
            $seriesCards = array_filter($seriesCards, function($c) use ($card) { return $c['id'] != $card['id']; });
            shuffle($seriesCards);
            $seriesCards = array_slice($seriesCards, 0, 10);
        }

        $authorCards = [];
        if ($card && !empty($card['author']) && $card['author'] !== '未知作者') {
            $authorCards = $this->cardModel->getCardsByAuthor($card['author'], $card['id'], 10);
        }

        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/home/index.php';
        include __DIR__ . '/../Views/footer.php';
    }
}
