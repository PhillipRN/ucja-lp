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

function sanitize_text(?string $value): string
{
    return trim((string)($value ?? ''));
}

function normalize_email_value(?string $value): string
{
    $normalized = strtolower(trim((string)($value ?? '')));
    return $normalized;
}

function ensure_unique_participant_identity(
    SupabaseClient $supabase,
    string $name,
    string $email,
    string $label
): void {
    if ($email !== '') {
        if (has_production_individual_match($supabase, 'student_email', $email) ||
            has_production_team_member_match($supabase, 'member_email', $email)) {
            throw new Exception("{$label}のメールアドレスは既に本番環境で使用されています。別のメールアドレスをご利用ください。");
        }
    }

    if ($name !== '') {
        if (has_production_individual_match($supabase, 'student_name', $name) ||
            has_production_team_member_match($supabase, 'member_name', $name)) {
            throw new Exception("{$label}の氏名は既に本番環境で使用されています。別の氏名をご確認ください。");
        }
    }
}

function has_production_individual_match(SupabaseClient $supabase, string $column, string $value): bool
{
    if ($value === '') {
        return false;
    }

    $result = $supabase->from('individual_applications')
        ->select('application_id')
        ->eq($column, $value)
        ->execute();

    if (!$result['success']) {
        throw new Exception('既存の個人申込データの確認に失敗しました。');
    }

    foreach ($result['data'] ?? [] as $row) {
        if (is_production_application($supabase, $row['application_id'] ?? null)) {
            return true;
        }
    }

    return false;
}

function has_production_team_member_match(SupabaseClient $supabase, string $column, string $value): bool
{
    if ($value === '') {
        return false;
    }

    $result = $supabase->from('team_members')
        ->select('team_application_id')
        ->eq($column, $value)
        ->execute();

    if (!$result['success']) {
        throw new Exception('既存のチームメンバーデータの確認に失敗しました。');
    }

    foreach ($result['data'] ?? [] as $row) {
        if (is_production_team_application($supabase, $row['team_application_id'] ?? null)) {
            return true;
        }
    }

    return false;
}

function is_production_team_application(SupabaseClient $supabase, ?string $teamApplicationId): bool
{
    static $cache = [];

    if (empty($teamApplicationId)) {
        return false;
    }

    if (array_key_exists($teamApplicationId, $cache)) {
        return $cache[$teamApplicationId];
    }

    $result = $supabase->from('team_applications')
        ->select('application_id')
        ->eq('id', $teamApplicationId)
        ->single();

    if (!$result['success'] || empty($result['data'])) {
        $cache[$teamApplicationId] = false;
        return false;
    }

    $cache[$teamApplicationId] = is_production_application($supabase, $result['data']['application_id'] ?? null);
    return $cache[$teamApplicationId];
}

function is_production_application(SupabaseClient $supabase, ?string $applicationId): bool
{
    static $cache = [];

    if (empty($applicationId)) {
        return false;
    }

    if (array_key_exists($applicationId, $cache)) {
        return $cache[$applicationId];
    }

    $result = $supabase->from('applications')
        ->select('environment, application_status')
        ->eq('id', $applicationId)
        ->single();

    if (!$result['success'] || empty($result['data'])) {
        $cache[$applicationId] = false;
        return false;
    }

    $record = $result['data'];
    $cache[$applicationId] = (
        ($record['environment'] ?? 'development') === 'production' &&
        ($record['application_status'] ?? '') !== 'cancelled'
    );

    return $cache[$applicationId];
}

try {
    // Supabaseクライアントの初期化
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    $emailTemplateService = new EmailTemplateService($supabase);
    
    // POSTデータの取得
    $participationType = sanitize_text($_POST['participationType'] ?? '');
    $pricingType = sanitize_text($_POST['pricingType'] ?? '');
    $specialRequests = sanitize_text($_POST['specialRequests'] ?? '');
    
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
    
    $isIndividual = ($participationType === '個人戦');
    $participantName = '';
    $guardianName = '';
    $guardianEmail = '';
    $teamName = '';
    $individualPayload = null;
    $teamPayload = null;
    $teamMembersPayload = [];
    $memberEmails = [];
    $loginRecipients = [];
    $addRecipient = function (?string $email, ?string $name = null) use (&$loginRecipients) {
        $email = trim(strtolower($email ?? ''));
        if (empty($email)) {
            return;
        }
        $loginRecipients[$email] = [
            'email' => $email,
            'name' => $name ?: ''
        ];
    };
    
    if ($isIndividual) {
        $studentName = sanitize_text($_POST['studentName'] ?? '');
        $schoolName = sanitize_text($_POST['school'] ?? '');
        $grade = sanitize_text($_POST['grade'] ?? '');
        $studentEmail = normalize_email_value($_POST['email'] ?? '');
        $studentPhone = sanitize_text($_POST['phone'] ?? '');
        $guardianNameValue = sanitize_text($_POST['guardianName'] ?? '');
        $guardianEmailValue = normalize_email_value($_POST['guardianEmail'] ?? '');
        $guardianPhone = sanitize_text($_POST['guardianPhone'] ?? '');
        
        $individualPayload = [
            'student_name' => $studentName,
            'school' => $schoolName,
            'grade' => $grade,
            'student_email' => $studentEmail,
            'student_phone' => $studentPhone,
            'guardian_name' => $guardianNameValue,
            'guardian_email' => $guardianEmailValue,
            'guardian_phone' => $guardianPhone
        ];
        
        $requiredFields = ['student_name', 'school', 'grade', 'student_email', 'student_phone', 'guardian_name', 'guardian_email', 'guardian_phone'];
        foreach ($requiredFields as $field) {
            if (empty($individualPayload[$field])) {
                throw new Exception('必須項目が入力されていません: ' . $field);
            }
        }
        
        ensure_unique_participant_identity($supabase, $studentName, $studentEmail, '個人戦の参加者');
        
        $participantName = $studentName;
        $guardianName = $guardianNameValue;
        $guardianEmail = $guardianEmailValue;
        $teamName = '';
        
        $addRecipient($studentEmail, $studentName);
        $addRecipient($guardianEmailValue, $guardianNameValue);
    } else {
        $teamNameValue = sanitize_text($_POST['teamName'] ?? '');
        $teamSchool = sanitize_text($_POST['school-team'] ?? '');
        $guardianNameTeam = sanitize_text($_POST['guardianName-team'] ?? '');
        $guardianEmailTeam = normalize_email_value($_POST['guardianEmail-team'] ?? '');
        $guardianPhoneTeam = sanitize_text($_POST['guardianPhone-team'] ?? '');
        
        $teamPayload = [
            'team_name' => $teamNameValue,
            'school' => $teamSchool,
            'guardian_name' => $guardianNameTeam,
            'guardian_email' => $guardianEmailTeam,
            'guardian_phone' => $guardianPhoneTeam
        ];
        
        $requiredFields = ['team_name', 'school', 'guardian_name', 'guardian_email', 'guardian_phone'];
        foreach ($requiredFields as $field) {
            if (empty($teamPayload[$field])) {
                throw new Exception('必須項目が入力されていません: ' . $field);
            }
        }
        
        $seenMemberEmails = [];
        $seenMemberNames = [];
        for ($i = 1; $i <= 5; $i++) {
            $memberName = sanitize_text($_POST["member{$i}Name"] ?? '');
            $memberEmail = normalize_email_value($_POST["member{$i}Email"] ?? '');
            
            if (empty($memberName) || empty($memberEmail)) {
                throw new Exception("メンバー{$i}の情報が入力されていません");
            }
            
            if (isset($seenMemberEmails[$memberEmail])) {
                throw new Exception("メンバー間で同じメールアドレスは使用できません: {$memberEmail}");
            }
            if (isset($seenMemberNames[$memberName])) {
                throw new Exception("メンバー間で同じ氏名は使用できません: {$memberName}");
            }
            
            $seenMemberEmails[$memberEmail] = true;
            $seenMemberNames[$memberName] = true;
            
            ensure_unique_participant_identity($supabase, $memberName, $memberEmail, "チームメンバー{$i}");
            
            $teamMembersPayload[] = [
                'member_number' => $i,
                'member_name' => $memberName,
                'member_email' => $memberEmail,
                'is_representative' => ($i === 1)
            ];
            
            $memberEmails[] = $memberEmail;
            $addRecipient($memberEmail, $memberName);
        }
        
        $participantName = $teamNameValue;
        $guardianName = $guardianNameTeam;
        $guardianEmail = $guardianEmailTeam;
        $teamName = $teamNameValue;
        
        $addRecipient($guardianEmailTeam, $guardianNameTeam);
    }
    
    // トランザクション開始（複数テーブルへの挿入）
    
    // 1. Applicationレコードの作成
    $applicationData = [
        'participation_type' => $isIndividual ? 'individual' : 'team',
        'pricing_type' => $pricingType,
        'amount' => $amount,
        'payment_status' => 'pending',
        'kyc_status' => 'pending',
        'application_status' => 'submitted',
        'environment' => APP_ENV,
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
    if ($isIndividual) {
        $individualData = array_merge(['application_id' => $applicationId], $individualPayload);
        
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
    } else {
        $teamData = array_merge(['application_id' => $applicationId], $teamPayload);
        
        $teamResult = $supabase->insert('team_applications', $teamData);
        
        if (!$teamResult['success'] || empty($teamResult['data'])) {
            throw new Exception('チーム情報の登録に失敗しました');
        }
        
        $teamApplicationId = $teamResult['data'][0]['id'];
        
        foreach ($teamMembersPayload as $memberData) {
            $memberInsertData = array_merge($memberData, [
                'team_application_id' => $teamApplicationId
            ]);
            
            $memberResult = $supabase->insert('team_members', $memberInsertData);
            
            if (!$memberResult['success']) {
                throw new Exception("メンバー{$memberData['member_number']}の登録に失敗しました");
            }
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
    
    // 追加: カード登録案内をログイン可能な全メール宛に送付
    try {
        if (!empty($loginRecipients)) {
            $cardVariablesBase = [
                'application_number' => $applicationNumber,
                'participation_type' => $participationType,
                'team_name' => $teamName,
                'participant_name' => $participantName,
                'card_registration_url' => rtrim(APP_URL, '/') . '/stripe-checkout-setup.php',
            ];
            foreach ($loginRecipients as $recipient) {
                $cardVariables = $cardVariablesBase;
                $cardVariables['guardian_name'] = $recipient['name'] ?: $guardianName;
                
                $emailTemplateService->sendTemplate(
                    'card_registration',
                    $recipient,
                    $cardVariables,
                    ['application_id' => $applicationId]
                );
            }
        }
    } catch (Exception $cardMailException) {
        error_log('[submit-application] カード登録案内メール送信に失敗しました: ' . $cardMailException->getMessage());
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

