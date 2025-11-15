<?php
/**
 * メールテンプレート更新API
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

    // POSTデータ取得
    $input = json_decode(file_get_contents('php://input'), true);
    
    $templateId = $input['id'] ?? '';
    $subject = $input['subject'] ?? '';
    $bodyText = $input['body_text'] ?? '';
    $bodyHtml = $input['body_html'] ?? '';
    $isActive = $input['is_active'] ?? true;
    $recipientType = $input['recipient_type'] ?? 'guardian';

    if (empty($templateId)) {
        throw new Exception('テンプレートIDが指定されていません');
    }

    if (empty($subject)) {
        throw new Exception('件名は必須です');
    }

    if (empty($bodyText) && empty($bodyHtml)) {
        throw new Exception('本文（テキストまたはHTML）は必須です');
    }

    $recipientType = trim($recipientType);
    if ($recipientType === '') {
        $recipientType = 'guardian';
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

    // 管理者情報取得
    $admin = AdminAuthHelper::getAdminInfo();

    // 更新データ
    $updateData = [
        'subject' => $subject,
        'body_text' => $bodyText,
        'body_html' => $bodyHtml,
        'is_active' => $isActive,
        'recipient_type' => $recipientType,
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $admin['id']
    ];

    // 更新実行
    $result = $supabase->update('email_templates', $updateData, [
        'id' => 'eq.' . $templateId
    ]);

    if (!$result['success']) {
        throw new Exception('テンプレートの更新に失敗しました');
    }

    // 管理者アクティビティログ記録
    $supabase->insert('admin_activity_logs', [
        'admin_id' => $admin['id'],
        'action' => 'update_email_template',
        'details' => json_encode([
            'template_id' => $templateId,
            'subject' => $subject,
            'updated_fields' => array_keys($updateData)
        ])
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'テンプレートを更新しました'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

