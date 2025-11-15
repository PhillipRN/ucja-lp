<?php
/**
 * 本人確認完了マーク API（テスト用）
 * 
 * kyc_statusを'completed'に更新するだけ
 * → DBトリガーが発動し、scheduled_chargesにレコード挿入
 * → 実際の課金はバッチ処理で行う
 * 
 * 注意: Phase 5でLiquid eKYC統合時も同じAPIを使用可能
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';

try {
    // POSTデータ取得
    $input = json_decode(file_get_contents('php://input'), true);
    
    $applicationId = $input['application_id'] ?? '';

    if (empty($applicationId)) {
        throw new Exception('申込IDが指定されていません');
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);

    // 申込情報を取得
    $appResult = $supabase->from('applications')
        ->select('*')
        ->eq('id', $applicationId)
        ->execute();

    if (!$appResult['success'] || empty($appResult['data']) || count($appResult['data']) === 0) {
        throw new Exception('申込情報が見つかりません');
    }

    $application = $appResult['data'][0];

    // カード登録済みかチェック
    if (!$application['card_registered']) {
        throw new Exception('カード情報が登録されていません');
    }

    // stripe_customer_idが保存されているかチェック
    if (empty($application['stripe_customer_id'])) {
        throw new Exception('Stripe Customer IDが登録されていません。カード情報を再登録してください。');
    }

    // stripe_payment_method_idが保存されているかチェック
    if (empty($application['stripe_payment_method_id'])) {
        throw new Exception('Stripe Payment Method IDが登録されていません。カード情報を再登録してください。');
    }

    // 既に本人確認完了済みかチェック
    if ($application['kyc_status'] === 'completed') {
        echo json_encode([
            'success' => true,
            'already_completed' => true,
            'message' => '既に本人確認は完了しています'
        ]);
        exit;
    }

    // 本人確認ステータスを「完了」に更新
    // → トリガーが発動し、scheduled_chargesにレコードが自動挿入される
    $updateResult = $supabase->update('applications', [
        'kyc_status' => 'completed',
        'kyc_verified_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ], [
        'id' => 'eq.' . $applicationId
    ]);

    if (!$updateResult['success']) {
        throw new Exception('本人確認ステータスの更新に失敗しました: ' . json_encode($updateResult));
    }

    echo json_encode([
        'success' => true,
        'message' => '本人確認が完了しました。決済処理はバッチ処理で実行されます。',
        'kyc_status' => 'completed'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

