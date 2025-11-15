<?php
/**
 * Application Submission API
 * 申込フォームの送信を処理
 */

header('Content-Type: application/json');

// CORS設定（必要に応じて）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 設定とライブラリの読み込み
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/../lib/EmailTemplateService.php';

try {
    // Supabaseクライアントの初期化
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    $emailTemplateService = new EmailTemplateService($supabase);
    
    // POSTデータの取得
    $participationType = $_POST['participationType'] ?? '';
    $pricingType = $_POST['pricingType'] ?? '';
    $specialRequests = $_POST['specialRequests'] ?? '';
    
    // 参加形式のバリデーション
    if (empty($participationType)) {
        throw new Exception('参加形式が選択されていません');
    }
    
    // 料金プランのバリデーション
    if (empty($pricingType)) {
        throw new Exception('料金プランが選択されていません');
    }
    
    // 料金の決定
    $amount = 0;
    if (strpos($pricingType, '早割') !== false) {
        $amount = EARLY_BIRD_PRICE;
    } else {
        $amount = REGULAR_PRICE;
    }
    
    // トランザクション開始（複数テーブルへの挿入）
    
    // 1. Applicationレコードの作成
    $applicationData = [
        'participation_type' => $participationType === '個人戦' ? 'individual' : 'team',
        'pricing_type' => $pricingType,
        'amount' => $amount,
        'payment_status' => 'pending',
        'kyc_status' => 'pending',
        'application_status' => 'submitted',
        'special_requests' => $specialRequests,
        'submitted_at' => date('c')
    ];
    
    $applicationResult = $supabase->insert('applications', $applicationData);
    
    if (!$applicationResult['success'] || empty($applicationResult['data'])) {
        throw new Exception('申込の登録に失敗しました');
    }
    
    $applicationId = $applicationResult['data'][0]['id'];
    $applicationNumber = $applicationResult['data'][0]['application_number'];
    
    // デバッグ: application_idを確認
    error_log('=== Application Created ===');
    error_log('application_id: ' . $applicationId);
    error_log('application_number: ' . $applicationNumber);
    
    // 2. 個人戦 or チーム戦の詳細データを保存
    if ($participationType === '個人戦') {
        // 個人戦の処理
        $individualData = [
            'application_id' => $applicationId,
            'student_name' => $_POST['studentName'] ?? '',
            'school' => $_POST['school'] ?? '',
            'grade' => $_POST['grade'] ?? '',
            'student_email' => $_POST['email'] ?? '',
            'student_phone' => $_POST['phone'] ?? '',
            'guardian_name' => $_POST['guardianName'] ?? '',
            'guardian_email' => $_POST['guardianEmail'] ?? '',
            'guardian_phone' => $_POST['guardianPhone'] ?? ''
        ];
        
        // バリデーション
        $requiredFields = ['student_name', 'school', 'grade', 'student_email', 'guardian_name', 'guardian_email', 'guardian_phone'];
        foreach ($requiredFields as $field) {
            if (empty($individualData[$field])) {
                throw new Exception('必須項目が入力されていません: ' . $field);
            }
        }
        
        $individualResult = $supabase->insert('individual_applications', $individualData);
        
        if (!$individualResult['success']) {
            throw new Exception('生徒情報の登録に失敗しました');
        }
        
        $guardianEmail = $individualData['guardian_email'];
        $responseData = [
            'success' => true,
            'application_id' => $applicationId,
            'application_number' => $applicationNumber,
            'participation_type' => 'individual',
            'amount' => $amount,
            'student_email' => $individualData['student_email'],
            'guardian_email' => $individualData['guardian_email']
        ];
        $participantName = $individualData['student_name'];
        $guardianName = $individualData['guardian_name'];
        $teamName = '';
        
    } else {
        // チーム戦の処理
        $teamData = [
            'application_id' => $applicationId,
            'team_name' => $_POST['teamName'] ?? '',
            'school' => $_POST['school-team'] ?? '',
            'guardian_name' => $_POST['guardianName-team'] ?? '',
            'guardian_email' => $_POST['guardianEmail-team'] ?? '',
            'guardian_phone' => $_POST['guardianPhone-team'] ?? ''
        ];
        
        // バリデーション
        $requiredFields = ['team_name', 'school', 'guardian_name', 'guardian_email', 'guardian_phone'];
        foreach ($requiredFields as $field) {
            if (empty($teamData[$field])) {
                throw new Exception('必須項目が入力されていません: ' . $field);
            }
        }
        
        $teamResult = $supabase->insert('team_applications', $teamData);
        
        if (!$teamResult['success'] || empty($teamResult['data'])) {
            throw new Exception('チーム情報の登録に失敗しました');
        }
        
        $teamApplicationId = $teamResult['data'][0]['id'];
        
        // チームメンバーの登録
        $memberEmails = [];
        for ($i = 1; $i <= 5; $i++) {
            $memberName = $_POST["member{$i}Name"] ?? '';
            $memberEmail = $_POST["member{$i}Email"] ?? '';
            
            if (empty($memberName) || empty($memberEmail)) {
                throw new Exception("メンバー{$i}の情報が入力されていません");
            }
            
            $memberData = [
                'team_application_id' => $teamApplicationId,
                'member_number' => $i,
                'member_name' => $memberName,
                'member_email' => $memberEmail,
                'is_representative' => ($i === 1)
            ];
            
            $memberResult = $supabase->insert('team_members', $memberData);
            
            if (!$memberResult['success']) {
                throw new Exception("メンバー{$i}の登録に失敗しました");
            }
            
            $memberEmails[] = $memberEmail;
        }
        
        $guardianEmail = $teamData['guardian_email'];
        $responseData = [
            'success' => true,
            'application_id' => $applicationId,
            'application_number' => $applicationNumber,
            'participation_type' => 'team',
            'amount' => $amount,
            'team_name' => $teamData['team_name'],
            'member_emails' => $memberEmails,
            'guardian_email' => $teamData['guardian_email']
        ];
        $participantName = $teamData['team_name'];
        $guardianName = $teamData['guardian_name'];
        $teamName = $teamData['team_name'];
    }
    
    // 3. メール送信
    try {
        $emailVariables = [
            'guardian_name' => $guardianName,
            'participant_name' => $participantName,
            'team_name' => $teamName,
            'application_number' => $applicationNumber,
            'participation_type' => $participationType,
            'amount' => number_format($amount),
            'card_registration_url' => rtrim(APP_URL, '/') . '/stripe-checkout-setup.php',
            'email' => $guardianEmail
        ];
        
        $emailTemplateService->sendTemplateToApplication(
            'application_confirmation',
            $applicationId,
            $emailVariables,
            []
        );
    } catch (Exception $mailException) {
        error_log('[submit-application] メール送信に失敗しました: ' . $mailException->getMessage());
    }
    
    // 成功レスポンス
    http_response_code(201);
    
    // デバッグ: レスポンスデータをログに出力
    error_log('=== Submit Application Response ===');
    error_log('application_id: ' . $responseData['application_id']);
    error_log('application_number: ' . $responseData['application_number']);
    error_log('Response JSON: ' . json_encode($responseData));
    
    echo json_encode($responseData);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

