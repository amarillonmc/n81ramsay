<?php
/**
 * srvpro2 Legacy HTTP API 客户端
 *
 * API 凭据只在 RAMSAY 服务端使用，不会下发给浏览器。
 */
class Srvpro2ApiClient {
    /**
     * API 基础地址
     * @var string
     */
    private $baseUrl;

    /**
     * API 用户名
     * @var string
     */
    private $username;

    /**
     * API 密码
     * @var string
     */
    private $password;

    /**
     * 请求超时
     * @var int
     */
    private $timeout;

    /**
     * 是否校验 TLS
     * @var bool
     */
    private $verifyTls;

    /**
     * 构造函数
     *
     * @param string|null $baseUrl API 基础地址
     * @param string|null $username API 用户名
     * @param string|null $password API 密码
     */
    public function __construct($baseUrl = null, $username = null, $password = null) {
        $this->baseUrl = rtrim(
            $baseUrl !== null
                ? (string)$baseUrl
                : (defined('SRVPRO2_API_BASE_URL') ? (string)SRVPRO2_API_BASE_URL : ''),
            '/'
        );
        $this->username = $username !== null
            ? (string)$username
            : (defined('SRVPRO2_API_USERNAME') ? (string)SRVPRO2_API_USERNAME : '');
        $this->password = $password !== null
            ? (string)$password
            : (defined('SRVPRO2_API_PASSWORD') ? (string)SRVPRO2_API_PASSWORD : '');
        $this->timeout = defined('SRVPRO2_API_TIMEOUT')
            ? max(1, (int)SRVPRO2_API_TIMEOUT)
            : 30;
        $this->verifyTls = !defined('SRVPRO2_API_VERIFY_TLS') || SRVPRO2_API_VERIFY_TLS;

        $this->validateConfiguration();
    }

    /**
     * 下载 srvpro2 动态生成的 YRP
     *
     * @param string $filename 录像文件名（{id}.yrp）
     * @return string YRP 二进制内容
     */
    public function downloadReplay($filename) {
        if (!$this->isValidReplayFilename($filename)) {
            throw new InvalidArgumentException('srvpro2 录像文件名无效');
        }

        $maxBytes = defined('SRVPRO2_REPLAY_MAX_BYTES')
            ? max(1, (int)SRVPRO2_REPLAY_MAX_BYTES)
            : 67108864;
        return $this->request('/api/replay/' . rawurlencode($filename), [], $maxBytes);
    }

    /**
     * 获取指定房间名下录像的实时公开状态
     *
     * srvpro2 仅在内存中保存活动 roomIdentifier；Legacy `/api/duellog`
     * 会将活动房间记录的 cloud_replay_id 置空，因此可作为精确状态来源。
     *
     * @param string $roomName 精确房间名
     * @return array 以录像 ID 为键、是否可公开为值
     */
    public function getReplayVisibilityByRoomName($roomName) {
        $roomName = trim((string)$roomName);
        if ($roomName === '' || strlen($roomName) > 80) {
            throw new InvalidArgumentException('srvpro2 房间名无效');
        }

        $maxBytes = defined('SRVPRO2_DUEL_LOG_MAX_BYTES')
            ? max(1024, (int)SRVPRO2_DUEL_LOG_MAX_BYTES)
            : 8388608;
        $timeout = defined('SRVPRO2_DUEL_LOG_TIMEOUT')
            ? min(30, max(1, (int)SRVPRO2_DUEL_LOG_TIMEOUT))
            : 5;
        $content = $this->request(
            '/api/duellog',
            ['roomname' => $roomName],
            $maxBytes,
            $timeout
        );
        $entries = json_decode($content, true);
        if (!is_array($entries)) {
            throw new RuntimeException('srvpro2 /api/duellog 返回无效 JSON');
        }
        $maxEntries = defined('SRVPRO2_DUEL_LOG_MAX_ENTRIES')
            ? min(100000, max(1, (int)SRVPRO2_DUEL_LOG_MAX_ENTRIES))
            : 10000;
        if (count($entries) > $maxEntries) {
            throw new RuntimeException('srvpro2 /api/duellog 返回条目过多');
        }

        $visibility = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (!isset($entry['id'])) {
                if (
                    isset($entry['name']) &&
                    is_string($entry['name']) &&
                    strpos($entry['name'], '密码错误') !== false
                ) {
                    throw new RuntimeException('srvpro2 API 用户名、密码或 duel_log 权限无效');
                }
                continue;
            }
            if (
                !isset($entry['originalName']) ||
                !is_string($entry['originalName']) ||
                $entry['originalName'] !== $roomName ||
                (!is_int($entry['id']) && !is_string($entry['id']))
            ) {
                continue;
            }

            $id = (string)$entry['id'];
            if (
                !preg_match('/^[1-9][0-9]{0,15}$/', $id) ||
                (strlen($id) === 16 && strcmp($id, '9007199254740991') > 0)
            ) {
                continue;
            }
            if (!isset($entry['cloud_replay_id']) || !is_string($entry['cloud_replay_id'])) {
                $visibility[$id] = false;
                continue;
            }
            $visibility[$id] = $entry['cloud_replay_id'] === 'R#' . $id;
        }

        return $visibility;
    }

    /**
     * 发起 API GET 请求
     *
     * @param string $path API 路径
     * @param array $query 额外查询参数
     * @param int|null $maxBytes 最大响应大小
     * @param int|null $timeoutOverride 本次请求超时
     * @return string 响应体
     */
    private function request($path, $query = [], $maxBytes = null, $timeoutOverride = null) {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('缺少 PHP curl 扩展，无法访问 srvpro2 动态录像 API');
        }

        $query['username'] = $this->username;
        $query['pass'] = $this->password;
        $url = $this->baseUrl . $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('无法初始化 srvpro2 API 请求');
        }

        $content = '';
        $receivedBytes = 0;
        $tooLarge = false;
        $requestTimeout = $timeoutOverride !== null
            ? max(1, (int)$timeoutOverride)
            : $this->timeout;
        $options = [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => min(10, $requestTimeout),
            CURLOPT_TIMEOUT => $requestTimeout,
            CURLOPT_HTTPHEADER => ['Accept: application/octet-stream, application/json'],
            CURLOPT_USERAGENT => 'RAMSAY/srvpro2-integration',
            CURLOPT_SSL_VERIFYPEER => $this->verifyTls,
            CURLOPT_SSL_VERIFYHOST => $this->verifyTls ? 2 : 0,
            CURLOPT_WRITEFUNCTION => function($handle, $chunk) use (
                &$content,
                &$receivedBytes,
                &$tooLarge,
                $maxBytes
            ) {
                $chunkLength = strlen($chunk);
                if ($maxBytes !== null && $receivedBytes + $chunkLength > $maxBytes) {
                    $tooLarge = true;
                    return 0;
                }

                $content .= $chunk;
                $receivedBytes += $chunkLength;
                return $chunkLength;
            }
        ];
        if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        curl_setopt_array($curl, $options);

        $success = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($tooLarge) {
            throw new RuntimeException('srvpro2 返回的录像超过大小限制');
        }
        if ($success === false) {
            throw new RuntimeException('访问 srvpro2 API 失败：' . $error);
        }
        if ($status === 401 || $status === 403) {
            throw new RuntimeException('srvpro2 API 用户名、密码或权限无效');
        }
        if ($status === 404) {
            throw new OutOfBoundsException('srvpro2 录像不存在');
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('srvpro2 API 返回 HTTP ' . $status);
        }

        return $content;
    }

    /**
     * 校验 API 配置
     *
     * @return void
     */
    private function validateConfiguration() {
        $parts = parse_url($this->baseUrl);
        if (
            $this->baseUrl === '' ||
            !is_array($parts) ||
            !isset($parts['scheme']) ||
            !isset($parts['host']) ||
            !in_array(strtolower($parts['scheme']), ['http', 'https'], true) ||
            isset($parts['user']) ||
            isset($parts['pass']) ||
            isset($parts['query']) ||
            isset($parts['fragment'])
        ) {
            throw new RuntimeException('SRVPRO2_API_BASE_URL 配置无效');
        }

        if ($this->username === '' || $this->password === '') {
            throw new RuntimeException('尚未配置 SRVPRO2_API_USERNAME / SRVPRO2_API_PASSWORD');
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(trim($parts['host'], '[]'));
        if (
            $scheme === 'http' &&
            !in_array($host, ['127.0.0.1', 'localhost', '::1'], true)
        ) {
            throw new RuntimeException('远程 SRVPRO2_API_BASE_URL 必须使用 HTTPS');
        }
    }

    /**
     * 校验动态录像文件名与 JavaScript 安全整数范围
     *
     * @param string $filename 录像文件名
     * @return bool 是否有效
     */
    private function isValidReplayFilename($filename) {
        if (!preg_match('/^([1-9][0-9]{0,15})\.yrp$/', (string)$filename, $matches)) {
            return false;
        }

        return strlen($matches[1]) < 16 || strcmp($matches[1], '9007199254740991') <= 0;
    }
}
