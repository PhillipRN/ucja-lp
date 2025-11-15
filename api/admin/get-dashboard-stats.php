<?php
/**
 * ダッシュボード統計情報取得API
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

    // 1. 全申込数
    $totalApplications = $supabase->from('applications')
        ->select('id', true) // count only
        ->execute();

    // 2. ステータス別件数
    $statusCounts = [];
    $statuses = [
        'draft' => '下書き',
        'submitted' => '申込完了',
        'card_pending' => 'カード登録待ち',
        'kyc_pending' => '本人確認待ち',
        'payment_pending' => '決済待ち',
        'payment_completed' => '決済完了',
        'cancelled' => 'キャンセル'
    ];

    foreach ($statuses as $status => $label) {
        $result = $supabase->from('applications')
            ->select('id', true)
            ->eq('application_status', $status)
            ->execute();
        
        $statusCounts[] = [
            'status' => $status,
            'label' => $label,
            'count' => $result['count'] ?? 0
        ];
    }

    // 3. 参加形式別件数
    $individualCount = $supabase->from('applications')
        ->select('id', true)
        ->eq('participation_type', 'individual')
        ->execute();

    $teamCount = $supabase->from('applications')
        ->select('id', true)
        ->eq('participation_type', 'team')
        ->execute();

    // 4. 決済状況
    $paymentCompleted = $supabase->from('applications')
        ->select('id', true)
        ->eq('payment_status', 'completed')
        ->execute();

    $cardRegistered = $supabase->from('applications')
        ->select('id', true)
        ->eq('card_registered', true)
        ->execute();

    // 5. 今日の申込数
    $todayApplications = $supabase->from('applications')
        ->select('id', true)
        ->gte('created_at', date('Y-m-d 00:00:00'))
        ->execute();

    // 6. 今週の申込数
    $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
    $weekApplications = $supabase->from('applications')
        ->select('id', true)
        ->gte('created_at', $weekStart)
        ->execute();

    // 7. 総売上（決済完了分）
    $revenueResult = $supabase->from('applications')
        ->select('amount')
        ->eq('payment_status', 'completed')
        ->execute();

    $totalRevenue = 0;
    if ($revenueResult['success'] && !empty($revenueResult['data'])) {
        foreach ($revenueResult['data'] as $app) {
            $totalRevenue += $app['amount'] ?? 0;
        }
    }

    // 8. 最近の申込（5件）
    $recentApplications = $supabase->from('applications')
        ->select('id, application_number, participation_type, amount, application_status, created_at')
        ->order('created_at', false) // DESC
        ->limit(5)
        ->execute();

    // 9. メール送信状況
    $emailPending = $supabase->from('email_logs')
        ->select('id', true)
        ->eq('status', 'pending')
        ->execute();

    $emailSent = $supabase->from('email_logs')
        ->select('id', true)
        ->eq('status', 'sent')
        ->execute();

    $emailFailed = $supabase->from('email_logs')
        ->select('id', true)
        ->eq('status', 'failed')
        ->execute();

    // レスポンス
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_applications' => $totalApplications['count'] ?? 0,
            'today_applications' => $todayApplications['count'] ?? 0,
            'week_applications' => $weekApplications['count'] ?? 0,
            'individual_count' => $individualCount['count'] ?? 0,
            'team_count' => $teamCount['count'] ?? 0,
            'payment_completed' => $paymentCompleted['count'] ?? 0,
            'card_registered' => $cardRegistered['count'] ?? 0,
            'total_revenue' => $totalRevenue,
            'status_counts' => $statusCounts,
            'recent_applications' => $recentApplications['data'] ?? [],
            'email_stats' => [
                'pending' => $emailPending['count'] ?? 0,
                'sent' => $emailSent['count'] ?? 0,
                'failed' => $emailFailed['count'] ?? 0
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

