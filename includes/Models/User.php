<?php
/**
 * 用户模型
 * 
 * 处理用户相关的数据操作
 */
class User {
    /**
     * 认证实例
     * @var Auth
     */
    private $auth;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $this->auth = Auth::getInstance();
    }
    
    /**
     * 登录
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @return bool 是否登录成功
     */
    public function login($username, $password) {
        return $this->auth->login($username, $password);
    }
    
    /**
     * 注销
     */
    public function logout() {
        $this->auth->logout();
    }
    
    /**
     * 检查是否已登录
     * 
     * @return bool 是否已登录
     */
    public function isLoggedIn() {
        return $this->auth->isLoggedIn();
    }
    
    /**
     * 获取当前用户
     * 
     * @return array|null 当前用户信息
     */
    public function getCurrentUser() {
        return $this->auth->getCurrentUser();
    }
    
    /**
     * 获取当前用户名
     * 
     * @return string|null 当前用户名
     */
    public function getCurrentUsername() {
        return $this->auth->getCurrentUsername();
    }
    
    /**
     * 获取当前用户组
     * 
     * @return int|null 当前用户组
     */
    public function getCurrentGroup() {
        return $this->auth->getCurrentGroup();
    }
    
    /**
     * 检查是否有权限
     * 
     * @param int $requiredGroup 所需的用户组
     * @return bool 是否有权限
     */
    public function hasPermission($requiredGroup) {
        return $this->auth->hasPermission($requiredGroup);
    }
    
    /**
     * 要求登录
     * 
     * 如果未登录，则重定向到登录页面
     * 
     * @param string $redirectUrl 重定向URL
     */
    public function requireLogin($redirectUrl = null) {
        $this->auth->requireLogin($redirectUrl);
    }
    
    /**
     * 要求权限
     * 
     * 如果没有所需权限，则显示错误信息
     * 
     * @param int $requiredGroup 所需的用户组
     */
    public function requirePermission($requiredGroup) {
        $this->auth->requirePermission($requiredGroup);
    }
    
    /**
     * 获取用户组名称
     * 
     * @param int $group 用户组
     * @return string 用户组名称
     */
    public function getGroupName($group) {
        switch ($group) {
            case 1:
                return '编辑员';
            case 2:
                return '管理员';
            case 3:
                return '高级管理员';
            case 255:
                return '超级管理员';
            default:
                return '未知';
        }
    }
}
