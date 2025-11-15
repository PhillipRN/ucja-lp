<?php
/**
 * Authentication Helper
 * セッション管理と認証処理を担当
 */

// config.phpを読み込む
require_once __DIR__ . '/../config/config.php';

class AuthHelper {
    
    /**
     * セッションを開始
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * ログイン処理
     * 
     * @param array $applicationData 申込データ
     * @return bool
     */
    public static function login($applicationData) {
        self::startSession();
        
        $_SESSION['user_id'] = $applicationData['id'];
        $_SESSION['application_number'] = $applicationData['application_number'];
        $_SESSION['participation_type'] = $applicationData['participation_type'];
        $_SESSION['email'] = $applicationData['email'];
        $_SESSION['team_member_id'] = $applicationData['team_member_id'] ?? null;
        $_SESSION['is_guardian'] = $applicationData['is_guardian'] ?? false;
        $_SESSION['is_representative'] = $applicationData['is_representative'] ?? false;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    /**
     * ログアウト処理
     */
    public static function logout() {
        self::startSession();
        
        // セッション変数をすべて削除
        $_SESSION = [];
        
        // セッションクッキーを削除
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // セッションを破棄
        session_destroy();
        
        return true;
    }
    
    /**
     * ログイン状態をチェック
     * 
     * @return bool
     */
    public static function isLoggedIn() {
        self::startSession();
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // セッションタイムアウトのチェック（2時間）
        if (isset($_SESSION['login_time'])) {
            $elapsed = time() - $_SESSION['login_time'];
            if ($elapsed > SESSION_LIFETIME) {
                self::logout();
                return false;
            }
            // アクティビティがあればタイムスタンプを更新
            $_SESSION['login_time'] = time();
        }
        
        return true;
    }
    
    /**
     * ログインが必要なページで使用
     * ログインしていない場合はログインページにリダイレクト
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            // サブディレクトリに対応した相対パスを計算
            $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
            $baseDir = '';
            
            // my-page/ 配下の場合は ../ を追加
            if (strpos($scriptPath, '/my-page') !== false) {
                $baseDir = '../';
            }
            
            header('Location: ' . $baseDir . 'login.php');
            exit;
        }
    }
    
    /**
     * 現在のユーザーIDを取得
     * 
     * @return int|null
     */
    public static function getUserId() {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * 申込番号を取得
     * 
     * @return string|null
     */
    public static function getApplicationNumber() {
        self::startSession();
        return $_SESSION['application_number'] ?? null;
    }
    
    /**
     * 参加形式を取得
     * 
     * @return string|null
     */
    public static function getParticipationType() {
        self::startSession();
        return $_SESSION['participation_type'] ?? null;
    }
    
    /**
     * メールアドレスを取得
     * 
     * @return string|null
     */
    public static function getUserEmail() {
        self::startSession();
        return $_SESSION['email'] ?? null;
    }
    
    /**
     * セッションデータを全て取得
     * 
     * @return array
     */
    public static function getSessionData() {
        self::startSession();
        return [
            'user_id' => self::getUserId(),
            'application_number' => self::getApplicationNumber(),
            'participation_type' => self::getParticipationType(),
            'email' => self::getUserEmail(),
            'team_member_id' => self::getTeamMemberId(),
            'is_guardian' => self::isGuardian(),
            'is_representative' => self::isRepresentative(),
            'logged_in' => self::isLoggedIn()
        ];
    }

    public static function getTeamMemberId() {
        self::startSession();
        return $_SESSION['team_member_id'] ?? null;
    }

    public static function isGuardian() {
        self::startSession();
        return $_SESSION['is_guardian'] ?? false;
    }

    public static function isRepresentative() {
        self::startSession();
        return $_SESSION['is_representative'] ?? false;
    }
    
    /**
     * CSRFトークンを生成
     * 
     * @return string
     */
    public static function generateCsrfToken() {
        self::startSession();
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * CSRFトークンを検証
     * 
     * @param string $token
     * @return bool
     */
    public static function verifyCsrfToken($token) {
        self::startSession();
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

