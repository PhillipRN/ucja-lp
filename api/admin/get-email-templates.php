<?php
/**
 * メールテンプレート一覧取得API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/AdminAuthHelper.php';

try {
    // 管理者認証チェック
    AdminAuthHelper::startSession();
    if (!AdminAuthHelper::isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => '認証が必要です'
        ]);
        exit;
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

    // テンプレート一覧を取得（sort_order順）
    $result = $supabase->from('email_templates')
        ->select('*')
        ->order('sort_order', true) // ASC
        ->order('created_at', true) // ASC
        ->execute();

    if (!$result['success']) {
        throw new Exception('テンプレートの取得に失敗しました');
    }

    echo json_encode([
        'success' => true,
        'templates' => $result['data'] ?? []
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

