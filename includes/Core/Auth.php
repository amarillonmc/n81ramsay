<?php
/**
 * 认证管理类
 * 
 * 负责用户认证和权限管理
 */
class Auth {
    /**
     * 单例实例
     * @var Auth
     */
    private static $instance;
    
    /**
     * 管理员配置
     * @var array
     */
    private $adminConfig;
    
    /**
     * 当前用户
     * @var array|null
     */
    private $currentUser = null;
    
    /**
     * 构造函数
     */
    private function __construct() {
        // 初始化会话
        $this->initSession();
        
        // 加载管理员配置
        $this->loadAdminConfig();
        
        // 检查当前用户
        $this->checkCurrentUser();
    }
    
    /**
     * 获取单例实例
     * 
     * @return Auth 认证实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * 初始化会话
     */
    private function initSession() {
        // 设置会话名称
        session_name(SESSION_NAME);
        
        // 设置会话cookie参数
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        // 启动会话
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * 加载管理员配置
     */
    private function loadAdminConfig() {
        $this->adminConfig = json_decode(ADMIN_CONFIG, true);
        
        if (!is_array($this->adminConfig)) {
            $this->adminConfig = [];
        }
    }
    
    /**
     * 检查当前用户
     */
    private function checkCurrentUser() {
        if (isset($_SESSION['user'])) {
            $this->currentUser = $_SESSION['user'];
        }
    }
    
    /**
     * 登录
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @return bool 是否登录成功
     */
    public function login($username, $password) {
        foreach ($this->adminConfig as $admin) {
            if ($admin['username'] === $username && password_verify($password, $admin['password'])) {
                // 设置当前用户
                $this->currentUser = [
                    'username' => $admin['username'],
                    'group' => $admin['group']
                ];
                
                // 保存到会话
                $_SESSION['user'] = $this->currentUser;
                
                // 更新会话ID以防止会话固定攻击
                session_regenerate_id(true);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 注销
     */
    public function logout() {
        // 清除当前用户
        $this->currentUser = null;
        
        // 清除会话
        $_SESSION = [];
        
        // 清除会话cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // 销毁会话
        session_destroy();
    }
    
    /**
     * 检查是否已登录
     * 
     * @return bool 是否已登录
     */
    public function isLoggedIn() {
        return $this->currentUser !== null;
    }
    
    /**
     * 获取当前用户
     * 
     * @return array|null 当前用户信息
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    /**
     * 获取当前用户名
     * 
     * @return string|null 当前用户名
     */
    public function getCurrentUsername() {
        return $this->currentUser ? $this->currentUser['username'] : null;
    }
    
    /**
     * 获取当前用户组
     * 
     * @return int|null 当前用户组
     */
    public function getCurrentGroup() {
        return $this->currentUser ? $this->currentUser['group'] : null;
    }
    
    /**
     * 检查是否有权限
     * 
     * @param int $requiredGroup 所需的用户组
     * @return bool 是否有权限
     */
    public function hasPermission($requiredGroup) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $this->currentUser['group'] >= $requiredGroup;
    }
    
    /**
     * 要求登录
     * 
     * 如果未登录，则重定向到登录页面
     * 
     * @param string $redirectUrl 重定向URL
     */
    public function requireLogin($redirectUrl = null) {
        if (!$this->isLoggedIn()) {
            $loginUrl = BASE_URL . 'admin/login';
            
            if ($redirectUrl) {
                $loginUrl .= '?redirect=' . urlencode($redirectUrl);
            }
            
            header('Location: ' . $loginUrl);
            exit;
        }
    }
    
    /**
     * 要求权限
     * 
     * 如果没有所需权限，则显示错误信息
     * 
     * @param int $requiredGroup 所需的用户组
     */
    public function requirePermission($requiredGroup) {
        $this->requireLogin();
        
        if (!$this->hasPermission($requiredGroup)) {
            http_response_code(403);
            echo '您没有权限访问此页面';
            exit;
        }
    }
}
