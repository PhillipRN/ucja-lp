<?php
/**
 * 一斉メール送信API
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
    
    $templateId = $input['template_id'] ?? '';
    $recipientType = $input['recipient_type'] ?? 'all'; // all, specific, filter
    $applicationIds = $input['application_ids'] ?? []; // specific用
    $filters = $input['filters'] ?? []; // filter用
    $testMode = $input['test_mode'] ?? false; // テストモード

    if (empty($templateId)) {
        throw new Exception('テンプレートを選択してください');
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    $admin = AdminAuthHelper::getAdminInfo();

    // テンプレート取得
    $templateResult = $supabase->from('email_templates')
        ->select('*')
        ->eq('id', $templateId)
        ->single();

    if (!$templateResult['success'] || empty($templateResult['data'])) {
        throw new Exception('テンプレートが見つかりません');
    }

    $template = $templateResult['data'];

    if (!$template['is_active']) {
        throw new Exception('このテンプレートは無効になっています');
    }

    // 送信対象の申込を取得
    $query = $supabase->from('applications')->select('*');

    if ($recipientType === 'specific' && !empty($applicationIds)) {
        // 特定の申込に送信
        $query = $query->in('id', $applicationIds);
    } elseif ($recipientType === 'filter' && !empty($filters)) {
        // フィルター条件で絞り込み
        if (!empty($filters['status'])) {
            $query = $query->eq('application_status', $filters['status']);
        }
        if (!empty($filters['participation_type'])) {
            $query = $query->eq('participation_type', $filters['participation_type']);
        }
        if (!empty($filters['payment_status'])) {
            $query = $query->eq('payment_status', $filters['payment_status']);
        }
    }
    // elseif $recipientType === 'all' の場合は全件

    $applicationsResult = $query->execute();

    if (!$applicationsResult['success'] || empty($applicationsResult['data'])) {
        throw new Exception('送信対象の申込が見つかりません');
    }

    $applications = $applicationsResult['data'];

    // メールログを作成（実際の送信は別のバッチ処理で行う想定）
    $emailLogs = [];
    $createdCount = 0;

    foreach ($applications as $app) {
        // 個人戦/チーム戦で保護者のメールアドレスを取得
        $recipientEmail = '';
        $recipientName = '';

        if ($app['participation_type'] === 'individual') {
            $detailResult = $supabase->from('individual_applications')
                ->select('guardian_email, guardian_name')
                ->eq('application_id', $app['id'])
                ->single();
            
            if ($detailResult['success'] && !empty($detailResult['data'])) {
                $recipientEmail = $detailResult['data']['guardian_email'];
                $recipientName = $detailResult['data']['guardian_name'];
            }
        } else {
            $detailResult = $supabase->from('team_applications')
                ->select('guardian_email, guardian_name')
                ->eq('application_id', $app['id'])
                ->single();
            
            if ($detailResult['success'] && !empty($detailResult['data'])) {
                $recipientEmail = $detailResult['data']['guardian_email'];
                $recipientName = $detailResult['data']['guardian_name'];
            }
        }

        if (empty($recipientEmail)) {
            continue; // メールアドレスがない場合はスキップ
        }

        // メールログ作成
        $emailLogData = [
            'application_id' => $app['id'],
            'email_type' => $template['template_type'],
            'template_id' => $templateId,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $template['subject'],
            'body_text' => $template['body_text'],
            'body_html' => $template['body_html'],
            'status' => $testMode ? 'test' : 'pending',
            'scheduled_at' => date('Y-m-d H:i:s')
        ];

        $logResult = $supabase->insert('email_logs', $emailLogData);

        if ($logResult['success']) {
            $createdCount++;
        }
    }

    // 管理者アクティビティログ記録
    $supabase->insert('admin_activity_logs', [
        'admin_id' => $admin['id'],
        'action' => 'send_bulk_email',
        'details' => json_encode([
            'template_id' => $templateId,
            'template_name' => $template['template_name'],
            'recipient_type' => $recipientType,
            'recipient_count' => $createdCount,
            'test_mode' => $testMode
        ])
    ]);

    echo json_encode([
        'success' => true,
        'message' => $testMode 
            ? "テストモード: {$createdCount}件のメールログを作成しました（実際には送信されません）"
            : "{$createdCount}件のメールを送信キューに追加しました",
        'count' => $createdCount
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

