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
        if ($card && !empty($card['setcode'])) {
            $seriesCards = $this->cardModel->getCardsBySetcode($card['setcode']);
            // 排除当前卡并随机取10张
            $seriesCards = array_filter($seriesCards, function($c) use ($card) { return $c['id'] != $card['id']; });
            shuffle($seriesCards);
            $seriesCards = array_slice($seriesCards, 0, 10);
        }

        $authorCards = [];
        if ($card) {
            // 作者前缀：卡号前3位；若为7位卡号则取前2位
            $prefix = intval($card['id'] / 100000);
            $authorCards = $this->cardModel->getCardsByPrefix($prefix, $card['id']);
            shuffle($authorCards);
            $authorCards = array_slice($authorCards, 0, 10);
        }

        include __DIR__ . '/../Views/layout.php';
        include __DIR__ . '/../Views/home/index.php';
        include __DIR__ . '/../Views/footer.php';
    }
}
