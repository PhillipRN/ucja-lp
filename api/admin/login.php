<?php
/**
 * Admin Login API
 * 管理者ログイン処理
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/AdminAuthHelper.php';

try {
    // POSTリクエストのみ許可
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        throw new Exception('メールアドレスとパスワードを入力してください');
    }
    
    // Supabaseから管理者情報を取得
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    
    $result = $supabase->from('admin_users')
        ->select('*')
        ->eq('email', $email)
        ->eq('is_active', true)
        ->single();
    
    if (!$result['success'] || empty($result['data'])) {
        throw new Exception('メールアドレスまたはパスワードが正しくありません');
    }
    
    $admin = $result['data'];
    
    // パスワード検証
    if (!password_verify($password, $admin['password_hash'])) {
        throw new Exception('メールアドレスまたはパスワードが正しくありません');
    }
    
    // ログイン処理
    AdminAuthHelper::login($admin);
    
    // 最終ログイン時刻を更新
    $supabase->update('admin_users', [
        'last_login_at' => date('Y-m-d H:i:s')
    ], [
        'id' => 'eq.' . $admin['id']
    ]);
    
    // 成功レスポンス
    echo json_encode([
        'success' => true,
        'admin' => [
            'id' => $admin['id'],
            'email' => $admin['email'],
            'username' => $admin['username'],
            'role' => $admin['role']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

