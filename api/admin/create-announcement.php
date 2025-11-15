<?php
/**
 * お知らせ作成API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/AdminAuthHelper.php';

// エラー出力を抑制してJSON出力を守る（config.php読み込み後に再設定）
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/api-errors.log');

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

    $admin = AdminAuthHelper::getAdminInfo();
    $input = json_decode(file_get_contents('php://input'), true);

    // バリデーション
    if (empty($input['title'])) {
        throw new Exception('タイトルは必須です');
    }
    if (empty($input['content'])) {
        throw new Exception('内容は必須です');
    }
    if (empty($input['announcement_date'])) {
        throw new Exception('日付は必須です');
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

    // お知らせを作成
    $data = [
        'title' => $input['title'],
        'content' => $input['content'],
        'announcement_date' => $input['announcement_date'],
        'external_url' => $input['external_url'] ?? null,
        'pdf_file_path' => $input['pdf_file_path'] ?? null,
        'display_order' => $input['display_order'] ?? 0,
        'is_published' => isset($input['is_published']) ? (bool)$input['is_published'] : true,
        'created_by' => $admin['id'],
        'updated_by' => $admin['id']
    ];

    $result = $supabase->insert('announcements', $data);

    if (!$result['success']) {
        throw new Exception('お知らせの作成に失敗しました');
    }

    echo json_encode([
        'success' => true,
        'announcement' => $result['data'][0] ?? null
    ]);

} catch (Exception $e) {
    error_log('Create announcement error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => APP_ENV === 'development' ? $e->getTraceAsString() : null
    ]);
}

