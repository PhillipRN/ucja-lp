<?php
/**
 * メール送信履歴取得API
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

    // クエリパラメータ取得
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $status = $_GET['status'] ?? '';
    $emailType = $_GET['email_type'] ?? '';
    $search = $_GET['search'] ?? '';

    $offset = ($page - 1) * $limit;

    // クエリビルド
    $query = $supabase->from('email_logs')->select('*');

    // ステータスフィルター
    if (!empty($status)) {
        $query = $query->eq('status', $status);
    }

    // メールタイプフィルター
    if (!empty($emailType)) {
        $query = $query->eq('email_type', $emailType);
    }

    // 検索フィルター（メールアドレス）
    if (!empty($search)) {
        $query = $query->like('recipient_email', '%' . $search . '%');
    }

    // ソート・ページネーション
    $query = $query->order('created_at', false) // DESC
                   ->limit($limit)
                   ->offset($offset);

    $result = $query->execute();

    if (!$result['success']) {
        throw new Exception('メール履歴の取得に失敗しました');
    }

    $emailLogs = $result['data'] ?? [];

    // 各メールログに申込情報を追加
    foreach ($emailLogs as &$log) {
        if (!empty($log['application_id'])) {
            $appResult = $supabase->from('applications')
                ->select('application_number, participation_type')
                ->eq('id', $log['application_id'])
                ->single();
            
            if ($appResult['success'] && !empty($appResult['data'])) {
                $log['application'] = $appResult['data'];
            }
        }
    }

    // 総件数を取得（フィルター適用後）
    $countQuery = $supabase->from('email_logs')->select('id', true);
    
    if (!empty($status)) {
        $countQuery = $countQuery->eq('status', $status);
    }
    if (!empty($emailType)) {
        $countQuery = $countQuery->eq('email_type', $emailType);
    }
    if (!empty($search)) {
        $countQuery = $countQuery->like('recipient_email', '%' . $search . '%');
    }

    $countResult = $countQuery->execute();
    $totalCount = $countResult['count'] ?? 0;

    echo json_encode([
        'success' => true,
        'email_logs' => $emailLogs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalCount / $limit),
            'total_count' => $totalCount,
            'per_page' => $limit
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

