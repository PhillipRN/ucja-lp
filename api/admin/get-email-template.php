<?php
/**
 * メールテンプレート詳細取得API
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

    // テンプレートID取得
    $templateId = $_GET['id'] ?? '';
    
    if (empty($templateId)) {
        throw new Exception('テンプレートIDが指定されていません');
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

    // テンプレート取得
    $result = $supabase->from('email_templates')
        ->select('*')
        ->eq('id', $templateId)
        ->single();

    if (!$result['success'] || empty($result['data'])) {
        throw new Exception('テンプレートが見つかりません');
    }

    echo json_encode([
        'success' => true,
        'template' => $result['data']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

