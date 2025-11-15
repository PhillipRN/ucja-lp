<?php
/**
 * Save Payment Method ID
 * SetupIntent成功後、PaymentMethod IDをデータベースに保存
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';

try {
    // POSTデータの取得
    $input = json_decode(file_get_contents('php://input'), true);
    $applicationId = $input['application_id'] ?? '';
    $paymentMethodId = $input['payment_method_id'] ?? '';
    $setupIntentId = $input['setup_intent_id'] ?? '';
    
    if (empty($applicationId) || empty($paymentMethodId)) {
        throw new Exception('必須パラメータが不足しています');
    }
    
    // Supabaseに保存
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    
    $updateData = [
        'stripe_payment_method_id' => $paymentMethodId,
        'stripe_setup_intent_id' => $setupIntentId,
        'card_registered' => true,
        'card_registered_at' => date('Y-m-d H:i:s'),
        'payment_status' => 'card_registered',
        'application_status' => 'kyc_pending'
    ];
    
    $result = $supabase->update('applications', $updateData, [
        'id' => 'eq.' . $applicationId
    ]);
    
    if (!$result['success']) {
        throw new Exception('データベース更新に失敗しました');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'PaymentMethod IDが正常に保存されました'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

