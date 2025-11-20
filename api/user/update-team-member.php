<?php
/**
 * チームメンバー情報更新API
 * 代表者がメンバー情報を更新するためのエンドポイント
 */

// エラー表示を抑制（JSONレスポンスのみ返す）
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../../lib/AuthHelper.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';

// CORS設定（必要に応じて）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // ログインチェック
    AuthHelper::requireLogin();
    
    // POSTメソッドのみ受付
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // ユーザー情報取得
    $userId = AuthHelper::getUserId();
    $participationType = AuthHelper::getParticipationType();
    
    // チーム戦でない場合はエラー
    if ($participationType !== 'team') {
        throw new Exception('この機能はチーム戦のみ利用できます');
    }
    
    // JSONデータを取得
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // 必須フィールドのバリデーション
    $requiredFields = ['member_id', 'member_name', 'member_email', 'member_grade'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception('すべての必須項目を入力してください');
        }
    }
    
    $memberId = $data['member_id'];
    $memberName = $data['member_name'];
    $memberEmail = trim(strtolower($data['member_email'])); // メールアドレスを正規化
    $memberPhone = $data['member_phone'] ?? null;
    $memberGrade = $data['member_grade'];
    $isReplacement = !empty($data['member_replacement']);
    
    // メールアドレスの形式チェック
    if (!filter_var($memberEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('有効なメールアドレスを入力してください');
    }
    
    // Supabaseクライアント初期化
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    
    // team_applicationsから保護者メールとチームIDを取得
    $teamAppResult = $supabase->from('team_applications')
        ->select('id, guardian_email')
        ->eq('application_id', $userId)
        ->single();
    
    if (!$teamAppResult['success'] || empty($teamAppResult['data'])) {
        throw new Exception('チーム情報の取得に失敗しました');
    }
    
    $teamApplicationId = $teamAppResult['data']['id'];
    $guardianEmail = trim(strtolower($teamAppResult['data']['guardian_email']));
    $loggedInEmail = trim(strtolower(AuthHelper::getUserEmail() ?? ''));
    
    // 代表者でない場合はエラー
    if ($loggedInEmail !== $guardianEmail) {
        throw new Exception('メンバー情報の編集は代表者のみ可能です');
    }
    
    // まず、このメンバーが現在のチームに所属しているか確認
    $memberResult = $supabase->from('team_members')
        ->select('*')
        ->eq('id', $memberId)
        ->eq('team_application_id', $teamApplicationId)
        ->single();
    
    if (!$memberResult['success'] || empty($memberResult['data'])) {
        throw new Exception('指定されたメンバーが見つかりません');
    }
    
    // メールアドレスの重複チェック（同じメンバー以外で）
    // 他のメンバーで同じメールアドレスが使われていないか確認
    $duplicateCheckResult = $supabase->from('team_members')
        ->select('id')
        ->eq('team_application_id', $teamApplicationId)
        ->eq('member_email', $memberEmail)
        ->neq('id', $memberId)
        ->execute();
    
    if ($duplicateCheckResult['success'] && !empty($duplicateCheckResult['data'])) {
        throw new Exception('このメールアドレスは既に他のメンバーで使用されています');
    }
    
    // メンバー情報を更新
    $updateData = [
        'member_name' => $memberName,
        'member_email' => $memberEmail,
        'member_phone' => $memberPhone,
        'member_grade' => $memberGrade,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($isReplacement) {
        $updateData = array_merge($updateData, [
            'stripe_customer_id' => null,
            'stripe_setup_intent_id' => null,
            'stripe_payment_method_id' => null,
            'stripe_payment_intent_id' => null,
            'card_registered' => false,
            'card_registered_at' => null,
            'card_last4' => null,
            'card_brand' => null,
            'payment_status' => 'pending',
            'payment_link_sent_at' => null,
            'scheduled_charge_date' => null,
            'charged_at' => null,
            'kyc_status' => 'pending',
            'kyc_verified_at' => null
        ]);
    }
    
    // SupabaseClient::update() は直接使用（クエリビルダーではない）
    $updateResult = $supabase->update('team_members', $updateData, ['id' => 'eq.' . $memberId]);
    
    if (!$updateResult['success']) {
        throw new Exception('メンバー情報の更新に失敗しました: ' . ($updateResult['error'] ?? 'Unknown error'));
    }

    if ($isReplacement) {
        // 未処理の課金スケジュールを削除
        $supabase->delete('scheduled_charges', [
            'team_member_id' => 'eq.' . $memberId,
            'status' => 'in.(scheduled,processing)'
        ]);

        // 過去の試験結果を削除（交代後の誤表示防止）
        $supabase->delete('exam_results', [
            'team_member_id' => 'eq.' . $memberId
        ]);
    }
    
    // 成功レスポンス
    echo json_encode([
        'success' => true,
        'message' => 'メンバー情報を更新しました',
        'replacement' => $isReplacement,
        'data' => [
            'member_id' => $memberId,
            'member_name' => $memberName,
            'member_email' => $memberEmail,
            'member_phone' => $memberPhone,
            'member_grade' => $memberGrade
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

