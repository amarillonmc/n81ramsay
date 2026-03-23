<?php
/**
 * 清理临时文件
 *
 * 删除根目录下的URL编码文件名的临时文件
 */
function cleanupTempFiles() {
    // 查找根目录下的URL编码文件名的临时文件
    $files = glob(__DIR__ . '/../../C%3A*');
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }

    // 确保临时目录存在
    if (!file_exists(TMP_DIR)) {
        mkdir(TMP_DIR, 0777, true);
    }

    // 清理临时目录中的0KB文件
    $tmpFiles = glob(TMP_DIR . '/*.cdb');
    foreach ($tmpFiles as $file) {
        if (is_file($file) && filesize($file) === 0) {
            @unlink($file);
        }
    }
}

// 在脚本结束时执行清理
register_shutdown_function('cleanupTempFiles');
/**
 * 工具函数类
 *
 * 提供各种工具函数
 */
class Utils {

    /**
     * 输出HTTP错误并结束请求
     *
     * @param int $statusCode 状态码
     * @param string $message 错误消息
     */
    public static function abort($statusCode, $message = '') {
        http_response_code($statusCode);
        if ($message !== '') {
            echo self::escapeHtml($message);
        }
        exit;
    }

    /**
     * 获取请求方法
     *
     * @return string
     */
    public static function getRequestMethod() {
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    }

    /**
     * 检查参数是否为允许的标量值
     *
     * @param mixed $value 输入值
     * @param int $maxLength 最大长度
     * @return bool
     */
    public static function isAllowedScalar($value, $maxLength = 4096) {
        if (is_array($value) || is_object($value)) {
            return false;
        }
        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        return strlen($value) <= $maxLength;
    }

    /**
     * 获取安全的标量参数
     */
    public static function getSafeParam($source, $key, $type = 'string', $default = null, $maxLength = 4096) {
        if (!isset($source[$key])) {
            return $default;
        }
        $value = $source[$key];
        if (!self::isAllowedScalar($value, $maxLength)) {
            return null;
        }
        if (is_string($value)) {
            $value = trim($value);
        }
        switch ($type) {
            case 'int':
                if ($value === '' || filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return null;
                }
                return (int) $value;
            case 'slug':
                if (!is_string($value) || !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', $value)) {
                    return null;
                }
                return $value;
            case 'hex8':
                if (!is_string($value) || !preg_match('/^[a-f0-9]{8}$/', $value)) {
                    return null;
                }
                return $value;
            case 'string':
            default:
                return is_string($value) ? $value : (string) $value;
        }
    }

    /**
     * 拒绝包含数组污染的请求参数
     */
    public static function rejectInvalidRequestData() {
        foreach (array($_GET, $_POST) as $bag) {
            foreach ($bag as $key => $value) {
                if (!self::isAllowedScalar($key, ROUTE_PARAM_MAX_LENGTH) || is_array($value)) {
                    self::abort(400, 'Bad Request');
                }
            }
        }
    }

    /**
     * 生成CSRF Token
     */
    public static function generateCsrfToken($context) {
        if (!isset($_SESSION['_csrf_tokens']) || !is_array($_SESSION['_csrf_tokens'])) {
            $_SESSION['_csrf_tokens'] = array();
        }
        if (isset($_SESSION['_csrf_tokens'][$context])) {
            $stored = $_SESSION['_csrf_tokens'][$context];
            if ((time() - (int) $stored['created_at']) <= CSRF_TOKEN_TTL) {
                return $stored['token'];
            }
        }
        $token = bin2hex(random_bytes(16));
        $_SESSION['_csrf_tokens'][$context] = array(
            'token' => $token,
            'created_at' => time()
        );
        return $token;
    }

    /**
     * 验证CSRF Token
     */
    public static function validateCsrfToken($context, $token, $consume = false) {
        if (!isset($_SESSION['_csrf_tokens'][$context])) {
            return false;
        }
        $stored = $_SESSION['_csrf_tokens'][$context];
        if ((time() - (int) $stored['created_at']) > CSRF_TOKEN_TTL) {
            unset($_SESSION['_csrf_tokens'][$context]);
            return false;
        }
        $valid = is_string($token) && hash_equals($stored['token'], $token);
        if ($valid && $consume) {
            unset($_SESSION['_csrf_tokens'][$context]);
        }
        return $valid;
    }

    /**
     * 生成公开表单防机器人元数据
     */
    public static function issuePublicFormToken($context) {
        if (!isset($_SESSION['_public_form_tokens']) || !is_array($_SESSION['_public_form_tokens'])) {
            $_SESSION['_public_form_tokens'] = array();
        }
        if (isset($_SESSION['_public_form_tokens'][$context])) {
            $stored = $_SESSION['_public_form_tokens'][$context];
            if (empty($stored['used']) && (time() - (int) $stored['issued_at']) <= PUBLIC_FORM_NONCE_TTL) {
                return array('nonce' => $stored['nonce'], 'issued_at' => $stored['issued_at']);
            }
        }
        $issuedAt = time();
        $nonce = bin2hex(random_bytes(16));
        $_SESSION['_public_form_tokens'][$context] = array(
            'nonce' => $nonce,
            'issued_at' => $issuedAt,
            'used' => false
        );
        return array('nonce' => $nonce, 'issued_at' => $issuedAt);
    }

    /**
     * 验证公开表单请求
     */
    public static function validatePublicFormRequest($context, $data) {
        $csrfToken = self::getSafeParam($data, 'csrf_token', 'string', '', 128);
        if (empty($csrfToken) || !self::validateCsrfToken($context, $csrfToken, true)) {
            return 'CSRF 校验失败';
        }
        if (!isset($_SESSION['_public_form_tokens'][$context])) {
            return '表单令牌无效';
        }
        $stored = $_SESSION['_public_form_tokens'][$context];
        $nonce = self::getSafeParam($data, 'form_nonce', 'string', '', 128);
        $issuedAt = self::getSafeParam($data, 'form_issued_at', 'int', null);
        $honeypot = self::getSafeParam($data, 'website', 'string', '', 255);
        if ($honeypot !== '') {
            return '请求被拒绝';
        }
        if (!$nonce || !hash_equals($stored['nonce'], $nonce)) {
            return '表单令牌无效';
        }
        if (!empty($stored['used'])) {
            return '请勿重复提交';
        }
        if ($issuedAt === null || abs((int) $issuedAt - (int) $stored['issued_at']) > 5) {
            return '表单时间戳无效';
        }
        $age = time() - (int) $stored['issued_at'];
        if ($age < PUBLIC_FORM_MIN_SECONDS) {
            return '提交过快，请稍后重试';
        }
        if ($age > PUBLIC_FORM_NONCE_TTL || $age > PUBLIC_FORM_MAX_SECONDS) {
            return '表单已过期，请刷新后重试';
        }
        if (self::getTrustedOrigin() === false) {
            return '来源校验失败';
        }
        $_SESSION['_public_form_tokens'][$context]['used'] = true;
        return null;
    }

    /**
     * 获取并验证来源
     */
    public static function getTrustedOrigin() {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim($_SERVER['HTTP_ORIGIN']) : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? trim($_SERVER['HTTP_REFERER']) : '';
        $host = isset($_SERVER['HTTP_HOST']) ? trim($_SERVER['HTTP_HOST']) : '';
        if ($host === '') {
            return false;
        }
        $allowedHosts = array($host);
        if (PUBLIC_WRITE_ALLOWED_ORIGINS !== '') {
            $extraOrigins = array_filter(array_map('trim', explode(',', PUBLIC_WRITE_ALLOWED_ORIGINS)));
            foreach ($extraOrigins as $extraOrigin) {
                $parsed = parse_url($extraOrigin);
                if (!empty($parsed['host'])) {
                    $allowedHosts[] = $parsed['host'];
                }
            }
        }
        if ($origin !== '') {
            $parsedOrigin = parse_url($origin);
            if (empty($parsedOrigin['host']) || !in_array($parsedOrigin['host'], $allowedHosts, true)) {
                return false;
            }
            return $origin;
        }
        if ($referer !== '') {
            $parsedReferer = parse_url($referer);
            if (empty($parsedReferer['host']) || !in_array($parsedReferer['host'], $allowedHosts, true)) {
                return false;
            }
        }
        return $host;
    }

    /**
     * 渲染 CSRF / nonce 隐藏字段
     */
    public static function renderCsrfFields($context) {
        $csrf = self::generateCsrfToken($context);
        $meta = self::issuePublicFormToken($context);
        echo '<input type="hidden" name="csrf_token" value="' . self::escapeHtml($csrf) . '">';
        echo '<input type="hidden" name="form_nonce" value="' . self::escapeHtml($meta['nonce']) . '">';
        echo '<input type="hidden" name="form_issued_at" value="' . (int) $meta['issued_at'] . '">';
        echo '<input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hp-field" aria-hidden="true">';
    }

    /**
     * 对公开写请求进行节流
     */
    public static function throttlePublicWrite($bucket, $payloadHash) {
        if (!isset($_SESSION['_rate_limits']) || !is_array($_SESSION['_rate_limits'])) {
            $_SESSION['_rate_limits'] = array();
        }
        $now = time();
        $ip = self::getClientIp();
        $sessionKey = session_id() !== '' ? session_id() : 'anonymous';
        $rateDir = dirname(DB_PATH) . '/rate_limits';
        if (!is_dir($rateDir)) {
            @mkdir($rateDir, 0755, true);
        }
        $ipFile = $rateDir . '/' . md5($bucket . '|ip|' . $ip) . '.json';
        $payloadFile = $rateDir . '/' . md5($bucket . '|payload|' . $ip . '|' . $payloadHash) . '.json';

        $ipData = self::readRateLimitFile($ipFile);
        $ipData = array_values(array_filter($ipData, function ($ts) use ($now) {
            return ($now - (int) $ts) < PUBLIC_WRITE_RATE_LIMIT_WINDOW;
        }));
        if (count($ipData) >= PUBLIC_WRITE_RATE_LIMIT_PER_IP) {
            return '提交过于频繁，请稍后再试';
        }
        $ipData[] = $now;
        self::writeRateLimitFile($ipFile, $ipData);

        if (!isset($_SESSION['_rate_limits'][$bucket][$sessionKey])) {
            $_SESSION['_rate_limits'][$bucket][$sessionKey] = array();
        }
        $_SESSION['_rate_limits'][$bucket][$sessionKey] = array_values(array_filter($_SESSION['_rate_limits'][$bucket][$sessionKey], function ($ts) use ($now) {
            return ($now - (int) $ts) < PUBLIC_WRITE_RATE_LIMIT_WINDOW;
        }));
        if (count($_SESSION['_rate_limits'][$bucket][$sessionKey]) >= PUBLIC_WRITE_RATE_LIMIT_PER_SESSION) {
            return '会话提交过于频繁，请稍后再试';
        }
        $_SESSION['_rate_limits'][$bucket][$sessionKey][] = $now;

        $payloadData = self::readRateLimitFile($payloadFile);
        $payloadData = array_values(array_filter($payloadData, function ($ts) use ($now) {
            return ($now - (int) $ts) < PUBLIC_WRITE_DUPLICATE_WINDOW;
        }));
        if (!empty($payloadData)) {
            return '检测到重复提交，请勿重复创建';
        }
        $payloadData[] = $now;
        self::writeRateLimitFile($payloadFile, $payloadData);
        return null;
    }

    private static function readRateLimitFile($path) {
        if (!file_exists($path)) {
            return array();
        }
        $content = @file_get_contents($path);
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : array();
    }

    private static function writeRateLimitFile($path, $data) {
        @file_put_contents($path, json_encode(array_values($data)));
    }

    /**
     * 生成归一化载荷哈希
     */
    public static function buildPayloadHash($data) {
        ksort($data);
        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 验证公开标识符
     */
    public static function isValidPublicIdentifier($value) {
        return is_string($value)
            && $value !== ''
            && strlen($value) <= PUBLIC_IDENTIFIER_MAX_LENGTH
            && preg_match('/^[A-Za-z0-9_\-\x{4e00}-\x{9fa5}\s]+$/u', $value);
    }

    /**
     * 验证公开文本
     */
    public static function isValidPublicText($value, $maxLength) {
        return is_string($value)
            && trim($value) !== ''
            && mb_strlen($value, 'UTF-8') <= $maxLength;
    }
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
     * @param bool $isSeriesVote 是否为系列投票
     * @param int $setcode 系列代码（仅系列投票时使用）
     * @param bool $isAdvancedVote 是否为高级投票
     * @return string 投票链接
     */
    public static function generateVoteLink($cardId, $environmentId, $voteCycle, $isSeriesVote = false, $setcode = 0, $isAdvancedVote = false) {
        if ($isSeriesVote) {
            $hash = md5('series-' . $setcode . '-' . $environmentId . '-' . $voteCycle);
        } elseif ($isAdvancedVote) {
            $hash = md5('advanced-' . $cardId . '-' . $environmentId . '-' . $voteCycle);
        } else {
            $hash = md5($cardId . '-' . $environmentId . '-' . $voteCycle);
        }
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

        // 尝试多次生成，避免无限递归
        $maxAttempts = 10;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            // 每次尝试使用不同的偏移量
            $offset = $attempt * 3;
            $identifier = substr($hash, $offset, 9);

            // 如果超出哈希长度，重新生成哈希
            if (strlen($identifier) < 9) {
                $hash = md5($hash . $attempt);
                $identifier = substr($hash, 0, 9);
            }

            // 确保至少包含一个字母和一个数字
            $hasLetter = preg_match('/[a-f]/i', $identifier);
            $hasNumber = preg_match('/[0-9]/', $identifier);

            if ($hasLetter && $hasNumber) {
                return $identifier;
            }
        }

        // 如果所有尝试都失败，强制生成一个符合条件的标识符
        $baseId = substr($hash, 0, 7);
        return $baseId . 'a1'; // 确保包含字母和数字
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
     * 检查内存使用情况
     *
     * @param string $context 上下文信息
     * @param int $warningThreshold 警告阈值（MB）
     * @return bool 是否超过警告阈值
     */
    public static function checkMemoryUsage($context = '', $warningThreshold = 3072) {
        $memoryUsage = memory_get_usage(true);
        $memoryUsageMB = round($memoryUsage / 1024 / 1024, 2);
        $memoryLimit = ini_get('memory_limit');

        // 转换内存限制为字节
        $memoryLimitBytes = self::convertToBytes($memoryLimit);
        $memoryLimitMB = round($memoryLimitBytes / 1024 / 1024, 2);

        $usagePercentage = round(($memoryUsage / $memoryLimitBytes) * 100, 2);

        if (DEBUG_MODE) {
            self::debug("内存使用情况 - {$context}", [
                '当前使用' => $memoryUsageMB . 'MB',
                '内存限制' => $memoryLimitMB . 'MB',
                '使用百分比' => $usagePercentage . '%'
            ]);
        }

        // 如果超过警告阈值，记录警告
        if ($memoryUsageMB > $warningThreshold) {
            self::debug("内存使用警告 - {$context}", [
                '当前使用' => $memoryUsageMB . 'MB',
                '警告阈值' => $warningThreshold . 'MB',
                '使用百分比' => $usagePercentage . '%'
            ]);
            return true;
        }

        return false;
    }

    /**
     * 转换内存限制字符串为字节数
     *
     * @param string $val 内存限制字符串（如 "128M", "1G"）
     * @return int 字节数
     */
    private static function convertToBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;

        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * 强制垃圾回收
     *
     * @param string $context 上下文信息
     */
    public static function forceGarbageCollection($context = '') {
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
            if (DEBUG_MODE && $collected > 0) {
                self::debug("垃圾回收 - {$context}", [
                    '回收对象数' => $collected
                ]);
            }
        }
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
