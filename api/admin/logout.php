<?php
/**
 * Admin Logout API
 * 管理者ログアウト処理
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../lib/AdminAuthHelper.php';

try {
    AdminAuthHelper::logout();
    
    echo json_encode([
        'success' => true,
        'message' => 'ログアウトしました'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

