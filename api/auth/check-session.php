<?php
/**
 * Check Session API
 * セッション確認API - 現在のログイン状態を返す
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../lib/AuthHelper.php';

try {
    // セッション開始
    AuthHelper::startSession();
    
    // ログイン状態をチェック
    if (!AuthHelper::isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'logged_in' => false,
            'error' => 'ログインしていません'
        ]);
        exit;
    }
    
    // セッション情報を取得
    $sessionData = [
        'success' => true,
        'logged_in' => true,
        'user' => [
            'user_id' => AuthHelper::getUserId(),
            'application_number' => AuthHelper::getApplicationNumber(),
            'participation_type' => AuthHelper::getParticipationType(),
            'email' => AuthHelper::getEmail()
        ],
        'session' => [
            'login_time' => $_SESSION['login_time'] ?? null,
            'elapsed_time' => isset($_SESSION['login_time']) ? (time() - $_SESSION['login_time']) : 0,
            'remaining_time' => SESSION_LIFETIME - (isset($_SESSION['login_time']) ? (time() - $_SESSION['login_time']) : 0)
        ]
    ];
    
    // 成功レスポンス
    http_response_code(200);
    echo json_encode($sessionData);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'logged_in' => false,
        'error' => 'セッションチェックに失敗しました: ' . $e->getMessage()
    ]);
}

