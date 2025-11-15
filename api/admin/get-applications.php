<?php
/**
 * 申込一覧取得API
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
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $participationType = $_GET['participation_type'] ?? '';
    $paymentStatus = $_GET['payment_status'] ?? '';

    $offset = ($page - 1) * $limit;

    // クエリビルド
    $query = $supabase->from('applications')->select('*');

    // 検索フィルター（申込番号）
    if (!empty($search)) {
        $query = $query->like('application_number', '%' . $search . '%');
    }

    // ステータスフィルター
    if (!empty($status)) {
        $query = $query->eq('application_status', $status);
    }

    // 参加形式フィルター
    if (!empty($participationType)) {
        $query = $query->eq('participation_type', $participationType);
    }

    // 決済ステータスフィルター
    if (!empty($paymentStatus)) {
        $query = $query->eq('payment_status', $paymentStatus);
    }

    // ソート・ページネーション
    $query = $query->order('created_at', false) // DESC
                   ->limit($limit)
                   ->offset($offset);

    $result = $query->execute();

    if (!$result['success']) {
        throw new Exception('申込の取得に失敗しました');
    }

    $applications = $result['data'] ?? [];

    // 各申込に詳細情報を追加
    foreach ($applications as &$app) {
        // 個人戦またはチーム戦の詳細を取得
        if ($app['participation_type'] === 'individual') {
            $detailResult = $supabase->from('individual_applications')
                ->select('*')
                ->eq('application_id', $app['id'])
                ->single();
            
            if ($detailResult['success'] && !empty($detailResult['data'])) {
                $app['participant_name'] = $detailResult['data']['student_name'];
                $app['guardian_email'] = $detailResult['data']['guardian_email'];
                $app['guardian_name'] = $detailResult['data']['guardian_name'];
            }
        } else {
            $detailResult = $supabase->from('team_applications')
                ->select('*')
                ->eq('application_id', $app['id'])
                ->single();
            
            if ($detailResult['success'] && !empty($detailResult['data'])) {
                $app['participant_name'] = $detailResult['data']['team_name'];
                $app['guardian_email'] = $detailResult['data']['guardian_email'];
                $app['guardian_name'] = $detailResult['data']['guardian_name'];
            }
        }
    }

    // 総件数を取得（フィルター適用後）
    $countQuery = $supabase->from('applications')->select('id', true);
    
    if (!empty($search)) {
        $countQuery = $countQuery->like('application_number', '%' . $search . '%');
    }
    if (!empty($status)) {
        $countQuery = $countQuery->eq('application_status', $status);
    }
    if (!empty($participationType)) {
        $countQuery = $countQuery->eq('participation_type', $participationType);
    }
    if (!empty($paymentStatus)) {
        $countQuery = $countQuery->eq('payment_status', $paymentStatus);
    }

    $countResult = $countQuery->execute();
    $totalCount = $countResult['count'] ?? 0;

    echo json_encode([
        'success' => true,
        'applications' => $applications,
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

