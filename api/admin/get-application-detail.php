<?php
/**
 * 申込詳細取得API
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

    // 申込ID取得
    $applicationId = $_GET['id'] ?? '';
    
    if (empty($applicationId)) {
        throw new Exception('申込IDが指定されていません');
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

    // 基本情報取得
    $applicationResult = $supabase->from('applications')
        ->select('*')
        ->eq('id', $applicationId)
        ->single();

    if (!$applicationResult['success'] || empty($applicationResult['data'])) {
        throw new Exception('申込が見つかりません');
    }

    $application = $applicationResult['data'];
    $participationType = $application['participation_type'];

    // 個人戦/チーム戦の詳細取得
    $details = null;
    if ($participationType === 'individual') {
        $detailResult = $supabase->from('individual_applications')
            ->select('*')
            ->eq('application_id', $applicationId)
            ->single();
        
        if ($detailResult['success']) {
            $details = $detailResult['data'];
        }
    } else {
        $detailResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $applicationId)
            ->single();
        
        if ($detailResult['success']) {
            $details = $detailResult['data'];
            
            // チームメンバー取得
            $membersResult = $supabase->from('team_members')
                ->select('*')
                ->eq('team_application_id', $details['id'])
                ->order('member_number', true) // ASC
                ->execute();
            
            if ($membersResult['success']) {
                $details['members'] = $membersResult['data'];
            }
        }
    }

    // メール送信履歴取得
    $emailLogsResult = $supabase->from('email_logs')
        ->select('*')
        ->eq('application_id', $applicationId)
        ->order('created_at', false) // DESC
        ->execute();

    $emailLogs = $emailLogsResult['success'] ? $emailLogsResult['data'] : [];

    // 決済履歴取得（Stripe関連）
    $transactions = [];
    if (!empty($application['stripe_payment_intent_id']) || !empty($application['stripe_setup_intent_id'])) {
        // 実際の決済トランザクション情報があればここで取得
        // 現状はapplicationsテーブルの情報のみ
        $transactions[] = [
            'type' => 'stripe_setup',
            'intent_id' => $application['stripe_setup_intent_id'],
            'payment_method_id' => $application['stripe_payment_method_id'],
            'card_registered' => $application['card_registered'],
            'card_registered_at' => $application['card_registered_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'application' => $application,
        'details' => $details,
        'email_logs' => $emailLogs,
        'transactions' => $transactions
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

