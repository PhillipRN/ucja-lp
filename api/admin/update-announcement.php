<?php
/**
 * お知らせ更新API
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
    if (empty($input['id'])) {
        throw new Exception('IDは必須です');
    }
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

    // お知らせを更新
    $data = [
        'title' => $input['title'],
        'content' => $input['content'],
        'announcement_date' => $input['announcement_date'],
        'external_url' => $input['external_url'] ?? null,
        'pdf_file_path' => $input['pdf_file_path'] ?? null,
        'display_order' => $input['display_order'] ?? 0,
        'is_published' => isset($input['is_published']) ? (bool)$input['is_published'] : true,
        'updated_by' => $admin['id'],
        'updated_at' => date('c')
    ];

    $result = $supabase->update('announcements', $data, ['id' => 'eq.' . $input['id']]);

    if (!$result['success']) {
        throw new Exception('お知らせの更新に失敗しました');
    }

    // 更新後のデータを取得
    $getResult = $supabase->from('announcements')
        ->select('*')
        ->eq('id', $input['id'])
        ->single();

    echo json_encode([
        'success' => true,
        'announcement' => $getResult['data'] ?? null
    ]);

} catch (Exception $e) {
    error_log('Update announcement error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => APP_ENV === 'development' ? $e->getTraceAsString() : null
    ]);
}

