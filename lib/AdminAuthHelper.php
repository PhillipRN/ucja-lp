<?php
/**
 * Admin Authentication Helper
 * 管理者認証・セッション管理
 */

class AdminAuthHelper
{
    /**
     * セッション開始
     */
    public static function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 管理者としてログインしているか確認
     */
    public static function isLoggedIn()
    {
        self::startSession();
        
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            return false;
        }
        
        // セッションタイムアウトチェック（2時間）
        if (isset($_SESSION['admin_last_activity'])) {
            $timeout = 7200; // 2時間（秒）
            if (time() - $_SESSION['admin_last_activity'] > $timeout) {
                self::logout();
                return false;
            }
        }
        
        $_SESSION['admin_last_activity'] = time();
        return true;
    }

    /**
     * ログイン処理
     */
    public static function login($admin)
    {
        self::startSession();
        
        // セッション固定攻撃対策
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['admin_login_time'] = time();
    }

    /**
     * ログアウト処理
     */
    public static function logout()
    {
        self::startSession();
        
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        
        session_destroy();
    }

    /**
     * ログイン中の管理者情報を取得
     */
    public static function getAdmin()
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'] ?? null,
            'email' => $_SESSION['admin_email'] ?? null,
            'username' => $_SESSION['admin_username'] ?? null,
            'role' => $_SESSION['admin_role'] ?? null,
            'login_time' => $_SESSION['admin_login_time'] ?? null
        ];
    }

    /**
     * ログイン中の管理者情報を取得（エイリアス）
     */
    public static function getAdminInfo()
    {
        return self::getAdmin();
    }

    /**
     * ログインを要求（未ログインの場合はログインページにリダイレクト）
     */
    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            // サブディレクトリに対応した相対パスを計算
            $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
            $baseDir = '';
            
            // admin/ 配下の場合
            if (strpos($scriptPath, '/admin') !== false) {
                $baseDir = '';  // 同じディレクトリなので相対パスのまま
            } else {
                // admin/ 外から呼ばれた場合（通常はない）
                $baseDir = 'admin/';
            }
            
            header('Location: ' . $baseDir . 'login.php');
            exit;
        }
    }

    /**
     * CSRFトークンを生成
     */
    public static function generateCsrfToken()
    {
        self::startSession();
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * CSRFトークンを検証
     */
    public static function verifyCsrfToken($token)
    {
        self::startSession();
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

