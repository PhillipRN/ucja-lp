<?php
/**
 * Get KYC Status API
 * 本人確認状況取得API
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
    
    // 基本のKYC情報
    $kycData = [
        'success' => true,
        'kyc_status' => $application['kyc_status'] ?? 'pending',
        'kyc_verified_at' => $application['kyc_verified_at'] ?? null,
        'kyc_provider' => KYC_PROVIDER,
        'kyc_enabled' => KYC_ENABLED
    ];
    
    // KYC詳細情報（kyc_verificationsテーブルから取得）
    try {
        $kycVerificationResult = $supabase->from('kyc_verifications')
            ->select('*')
            ->eq('application_id', $userId)
            ->order('created_at', 'desc')
            ->limit(1)
            ->execute();
        
        if ($kycVerificationResult['success'] && !empty($kycVerificationResult['data'])) {
            $kycVerification = $kycVerificationResult['data'][0];
            $kycData['verification'] = [
                'id' => $kycVerification['id'],
                'status' => $kycVerification['verification_status'],
                'provider_verification_id' => $kycVerification['provider_verification_id'],
                'document_type' => $kycVerification['document_type'],
                'verified_name' => $kycVerification['verified_name'],
                'verified_date_of_birth' => $kycVerification['verified_date_of_birth'],
                'rejection_reason' => $kycVerification['rejection_reason'],
                'started_at' => $kycVerification['started_at'],
                'completed_at' => $kycVerification['completed_at'],
                'created_at' => $kycVerification['created_at']
            ];
        } else {
            $kycData['verification'] = null;
        }
    } catch (Exception $e) {
        // kyc_verificationsテーブルにデータがない場合
        $kycData['verification'] = null;
    }
    
    // チーム戦の場合はメンバーのKYC状況も取得
    if ($participationType === 'team') {
        // チーム申込情報取得
        $teamResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $userId)
            ->single();
        
        if ($teamResult['success'] && !empty($teamResult['data'])) {
            $team = $teamResult['data'];
            $teamApplicationId = $team['id'];
            
            // チームメンバーのKYC状況取得
            $membersResult = $supabase->from('team_members')
                ->select('id, member_number, member_name, member_email, is_representative, kyc_status, kyc_verified_at')
                ->eq('team_application_id', $teamApplicationId)
                ->order('member_number', 'asc')
                ->execute();
            
            $members = $membersResult['data'] ?? [];
            
            // メンバーのKYC統計
            $totalMembers = count($members);
            $verifiedMembers = 0;
            $inProgressMembers = 0;
            $pendingMembers = 0;
            
            foreach ($members as $member) {
                switch ($member['kyc_status']) {
                    case 'completed':
                        $verifiedMembers++;
                        break;
                    case 'in_progress':
                        $inProgressMembers++;
                        break;
                    case 'pending':
                    default:
                        $pendingMembers++;
                        break;
                }
            }
            
            $kycData['team'] = [
                'team_name' => $team['team_name'],
                'total_members' => $totalMembers,
                'verified_members' => $verifiedMembers,
                'in_progress_members' => $inProgressMembers,
                'pending_members' => $pendingMembers,
                'kyc_progress' => $totalMembers > 0 ? round(($verifiedMembers / $totalMembers) * 100) : 0,
                'members' => $members
            ];
        }
    }
    
    // 成功レスポンス
    http_response_code(200);
    echo json_encode($kycData);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

