<?php
/**
 * Get Payment Status API
 * 支払い状況取得API
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
    $participationType = AuthHelper::getParticipationType();
    
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
    
    // 基本の支払い情報
    $paymentData = [
        'success' => true,
        'payment_status' => $application['payment_status'] ?? 'pending',
        'card_registered' => $application['card_registered'] ?? false,
        'card_registered_at' => $application['card_registered_at'] ?? null,
        'card_last4' => $application['card_last4'] ?? null,
        'card_brand' => $application['card_brand'] ?? null,
        'amount' => $application['amount'] ?? 0,
        'scheduled_charge_date' => $application['scheduled_charge_date'] ?? null,
        'charged_at' => $application['charged_at'] ?? null,
        'stripe_customer_id' => $application['stripe_customer_id'] ?? null,
        'stripe_payment_method_id' => $application['stripe_payment_method_id'] ?? null
    ];
    
    // チーム戦の場合はメンバーの支払い状況も取得
    if ($participationType === 'team') {
        // チーム申込情報取得
        $teamResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $userId)
            ->single();
        
        if ($teamResult['success'] && !empty($teamResult['data'])) {
            $team = $teamResult['data'];
            $teamApplicationId = $team['id'];
            
            // チームメンバーの支払い状況取得
            $membersResult = $supabase->from('team_members')
                ->select('id, member_number, member_name, member_email, is_representative, payment_status, card_registered, card_registered_at, card_last4, card_brand')
                ->eq('team_application_id', $teamApplicationId)
                ->order('member_number', 'asc')
                ->execute();
            
            $members = $membersResult['data'] ?? [];
            
            // メンバーの支払い統計
            $totalMembers = count($members);
            $paidMembers = 0;
            $cardRegisteredMembers = 0;
            
            foreach ($members as $member) {
                if ($member['payment_status'] === 'completed') {
                    $paidMembers++;
                }
                if ($member['card_registered'] === true) {
                    $cardRegisteredMembers++;
                }
            }
            
            $paymentData['team'] = [
                'team_name' => $team['team_name'],
                'total_members' => $totalMembers,
                'paid_members' => $paidMembers,
                'card_registered_members' => $cardRegisteredMembers,
                'payment_progress' => $totalMembers > 0 ? round(($paidMembers / $totalMembers) * 100) : 0,
                'card_registration_progress' => $totalMembers > 0 ? round(($cardRegisteredMembers / $totalMembers) * 100) : 0,
                'members' => $members
            ];
        }
    }
    
    // 成功レスポンス
    http_response_code(200);
    echo json_encode($paymentData);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

