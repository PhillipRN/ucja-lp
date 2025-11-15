<?php
/**
 * Get Application Detail API
 * 申込詳細情報の取得
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/AuthHelper.php';

try {
    // ログインチェック
    AuthHelper::startSession();
    if (!AuthHelper::isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'ログインが必要です']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        exit;
    }
    
    // ユーザー情報取得
    $userId = AuthHelper::getUserId();
    
    // Supabaseクライアント初期化
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    
    // 申込情報取得
    $applicationResult = $supabase->from('applications')
        ->select('*')
        ->eq('id', $userId)
        ->single();
    
    if (!$applicationResult['success'] || empty($applicationResult['data'])) {
        throw new Exception('申込情報が見つかりません');
    }
    
    $application = $applicationResult['data'];
    $participationType = $application['participation_type'];
    
    $responseData = [
        'success' => true,
        'application' => $application,
        'participation_type' => $participationType
    ];
    
    // 個人戦またはチーム戦の詳細情報取得
    if ($participationType === 'individual') {
        $detailResult = $supabase->from('individual_applications')
            ->select('*')
            ->eq('application_id', $userId)
            ->single();
        
        if ($detailResult['success'] && !empty($detailResult['data'])) {
            $responseData['detail'] = $detailResult['data'];
        }
    } else {
        $detailResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $userId)
            ->single();
        
        if ($detailResult['success'] && !empty($detailResult['data'])) {
            $responseData['detail'] = $detailResult['data'];
            
            // チームメンバー情報も取得
            $teamId = $detailResult['data']['id'];
            $membersResult = $supabase->from('team_members')
                ->select('*')
                ->eq('team_application_id', $teamId)
                ->order('member_number', 'asc')
                ->execute();
            
            if ($membersResult['success'] && !empty($membersResult['data'])) {
                $responseData['team_members'] = $membersResult['data'];
            }
        }
    }
    
    // 成功レスポンス
    http_response_code(200);
    echo json_encode($responseData);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

