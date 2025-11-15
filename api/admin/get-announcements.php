<?php
/**
 * お知らせ一覧取得API
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

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

    // お知らせ一覧を取得（日付降順、表示順序順）
    $result = $supabase->from('announcements')
        ->select('*')
        ->order('announcement_date', false) // DESC
        ->order('display_order', true) // ASC
        ->execute();

    if (!$result['success']) {
        throw new Exception('お知らせの取得に失敗しました');
    }

    echo json_encode([
        'success' => true,
        'announcements' => $result['data'] ?? []
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

