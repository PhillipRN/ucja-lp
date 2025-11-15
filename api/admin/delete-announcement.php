<?php
/**
 * お知らせ削除API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/AdminAuthHelper.php';

// エラー出力を抑制してJSON出力を守る（config.php読み込み後に再設定）
error_reporting(0);
ini_set('display_errors', '0');

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

    $input = json_decode(file_get_contents('php://input'), true);

    // バリデーション
    if (empty($input['id'])) {
        throw new Exception('IDは必須です');
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

    // お知らせを削除
    $result = $supabase->delete('announcements', ['id' => 'eq.' . $input['id']]);

    if (!$result['success']) {
        throw new Exception('お知らせの削除に失敗しました');
    }

    echo json_encode([
        'success' => true,
        'message' => 'お知らせを削除しました'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

