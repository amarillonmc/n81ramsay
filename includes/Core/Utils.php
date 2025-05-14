<?php
/**
 * 工具函数类
 *
 * 提供各种工具函数
 */
class Utils {
    /**
     * 生成随机字符串
     *
     * @param int $length 字符串长度
     * @return string 随机字符串
     */
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * 生成投票链接
     *
     * @param int $cardId 卡片ID
     * @param int $environmentId 环境ID
     * @param int $voteCycle 投票周期
     * @return string 投票链接
     */
    public static function generateVoteLink($cardId, $environmentId, $voteCycle) {
        $hash = md5($cardId . '-' . $environmentId . '-' . $voteCycle);
        return substr($hash, 0, 8);
    }

    /**
     * 获取客户端IP地址
     *
     * @return string IP地址
     */
    public static function getClientIp() {
        $ipAddress = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipAddress = 'UNKNOWN';
        }

        // 处理多个IP的情况（如通过代理）
        if (strpos($ipAddress, ',') !== false) {
            $ipAddresses = explode(',', $ipAddress);
            $ipAddress = trim($ipAddresses[0]);
        }

        return $ipAddress;
    }

    /**
     * 生成投票者唯一标识符
     *
     * 根据IP地址、用户代理和时间戳生成9位字母数字组合标识符
     *
     * @param string $ipAddress IP地址
     * @param string $userId 用户ID
     * @return string 9位唯一标识符
     */
    public static function generateVoterIdentifier($ipAddress, $userId) {
        // 获取用户代理信息
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        // 组合数据并加盐
        $data = $ipAddress . '|' . $userAgent;

        // 生成哈希
        $hash = md5($data);

        // 取前9位，确保包含字母和数字
        $identifier = substr($hash, 0, 9);

        // 确保至少包含一个字母和一个数字
        $hasLetter = preg_match('/[a-f]/i', $identifier);
        $hasNumber = preg_match('/[0-9]/', $identifier);

        if (!$hasLetter || !$hasNumber) {
            // 如果不满足条件，重新生成
            return self::generateVoterIdentifier($ipAddress, $userId);
        }

        return $identifier;
    }

    /**
     * 格式化日期时间
     *
     * @param string $datetime 日期时间字符串
     * @param string $format 格式
     * @return string 格式化后的日期时间
     */
    public static function formatDatetime($datetime, $format = 'Y-m-d H:i:s') {
        $timestamp = strtotime($datetime);
        return date($format, $timestamp);
    }

    /**
     * 获取相对时间
     *
     * @param string $datetime 日期时间字符串
     * @return string 相对时间
     */
    public static function getRelativeTime($datetime) {
        $timestamp = strtotime($datetime);
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return '刚刚';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . '分钟前';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . '小时前';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . '天前';
        } elseif ($diff < 31536000) {
            return floor($diff / 2592000) . '个月前';
        } else {
            return floor($diff / 31536000) . '年前';
        }
    }

    /**
     * 转义HTML
     *
     * @param string $text 文本
     * @return string 转义后的文本
     */
    public static function escapeHtml($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 截断文本
     *
     * @param string $text 文本
     * @param int $length 长度
     * @param string $suffix 后缀
     * @return string 截断后的文本
     */
    public static function truncateText($text, $length = 100, $suffix = '...') {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
    }

    /**
     * 获取禁限状态文本
     *
     * @param int $status 状态代码
     * @return string 状态文本
     */
    public static function getLimitStatusText($status) {
        switch ($status) {
            case 0:
                return '禁止';
            case 1:
                return '限制';
            case 2:
                return '准限制';
            case 3:
            default:
                return '无限制';
        }
    }

    /**
     * 获取禁限状态CSS类
     *
     * @param int $status 状态代码
     * @return string CSS类
     */
    public static function getLimitStatusClass($status) {
        switch ($status) {
            case 0:
                return 'forbidden';
            case 1:
                return 'limited';
            case 2:
                return 'semi-limited';
            case 3:
            default:
                return 'unlimited';
        }
    }

    /**
     * 重定向
     *
     * @param string $url URL
     */
    public static function redirect($url) {
        header('Location: ' . $url);
        exit;
    }

    /**
     * 获取当前URL
     *
     * @return string 当前URL
     */
    public static function getCurrentUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];

        return $protocol . '://' . $host . $uri;
    }

    /**
     * 获取基础URL
     *
     * @return string 基础URL
     */
    public static function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];

        return $protocol . '://' . $host . BASE_URL;
    }

    /**
     * 获取环境列表
     *
     * @return array 环境列表
     */
    public static function getEnvironments() {
        $environments = json_decode(CARD_ENVIRONMENTS, true);

        if (!is_array($environments)) {
            $environments = [];
        }

        return $environments;
    }

    /**
     * 根据ID获取环境
     *
     * @param int $id 环境ID
     * @return array|null 环境信息
     */
    public static function getEnvironmentById($id) {
        $environments = self::getEnvironments();

        foreach ($environments as $env) {
            if ($env['id'] == $id) {
                return $env;
            }
        }

        return null;
    }

    /**
     * 根据标题获取环境
     *
     * @param string $header 环境标题
     * @return array|null 环境信息
     */
    public static function getEnvironmentByHeader($header) {
        $environments = self::getEnvironments();

        foreach ($environments as $env) {
            if ($env['header'] == $header) {
                return $env;
            }
        }

        return null;
    }

    /**
     * 调试日志
     *
     * @param string $message 日志消息
     * @param mixed $data 附加数据
     */
    public static function debug($message, $data = null) {
        if (!DEBUG_MODE) {
            return;
        }

        $logFile = __DIR__ . '/../../logs/debug.log';
        $logDir = dirname($logFile);

        // 确保日志目录存在
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}";

        if ($data !== null) {
            $logMessage .= " - " . print_r($data, true);
        }

        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
    }
}
