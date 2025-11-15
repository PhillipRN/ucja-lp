<?php
/**
 * 本人確認完了処理API（テスト用）
 * 
 * 注意: これはLiquid eKYC統合前のテスト用実装です
 * Phase 5で本格実装に置き換える必要があります
 */

// エラーを全てキャッチしてJSONで返す
error_reporting(E_ALL);
ini_set('display_errors', 0); // 画面にエラーを表示しない
ini_set('log_errors', 1);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../lib/SupabaseClient.php';
    require_once __DIR__ . '/../../vendor/autoload.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'ファイルの読み込みエラー: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

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

    // 既に本人確認完了済みかチェック
    if ($application['kyc_status'] === 'completed') {
        // 既に完了している場合は、決済状況を返す
        echo json_encode([
            'success' => true,
            'already_completed' => true,
            'payment_status' => $application['payment_status'],
            'message' => '既に本人確認は完了しています'
        ]);
        exit;
    }

    // 課金方式によって処理を分岐
    if (USE_SCHEDULED_CHARGES) {
        // ===== 方式B: scheduled_charges経由（本番推奨） =====
        // kyc_statusを'completed'に更新すると、トリガーが発動し、
        // scheduled_chargesにレコードが自動挿入される
        
        $updateResult = $supabase->update('applications', [
            'kyc_status' => 'completed',
            'kyc_verified_at' => date('Y-m-d H:i:s'),
            'application_status' => 'charge_scheduled', // トリガーでこうなる
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => 'eq.' . $applicationId
        ]);

        if (!$updateResult['success']) {
            throw new Exception('本人確認ステータスの更新に失敗しました');
        }

        echo json_encode([
            'success' => true,
            'payment_status' => 'scheduled',
            'message' => '本人確認が完了しました。バッチ処理で課金されます。'
        ]);
        exit;
        
    } else {
        // ===== 方式A: 即座に課金（テスト用） =====
        // トリガー発動を防ぐため、まずapplication_statusのみを更新
        
        $updateResult = $supabase->update('applications', [
            'application_status' => 'payment_processing',
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => 'eq.' . $applicationId
        ]);

        if (!$updateResult['success']) {
            throw new Exception('申込ステータスの更新に失敗しました');
        }
    }
    
    // 以下、方式Aの処理（即座に課金）
    if (empty($application['stripe_payment_method_id'])) {
        throw new Exception('保存されたカード情報が見つかりません');
    }

    // Stripe API初期化
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // PaymentIntentを作成（保存されたPaymentMethodで課金）
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $application['amount'],
        'currency' => 'jpy',
        'customer' => $application['stripe_customer_id'],
        'payment_method' => $application['stripe_payment_method_id'],
        'off_session' => true, // オフセッション決済（ユーザー不在での決済）
        'confirm' => true, // 即座に決済確定
        'metadata' => [
            'application_id' => $applicationId,
            'application_number' => $application['application_number'],
            'kyc_auto_charge' => 'true'
        ]
    ]);

    // PaymentIntent IDを保存
    $supabase->update('applications', [
        'stripe_payment_intent_id' => $paymentIntent->id,
        'payment_status' => 'processing',
        'updated_at' => date('Y-m-d H:i:s')
    ], [
        'id' => 'eq.' . $applicationId
    ]);

    // 決済トランザクション記録
    $supabase->insert('payment_transactions', [
        'application_id' => $applicationId,
        'transaction_type' => 'payment',
        'amount' => $application['amount'],
        'currency' => 'JPY',
        'stripe_customer_id' => $application['stripe_customer_id'],
        'stripe_payment_method_id' => $application['stripe_payment_method_id'],
        'stripe_payment_intent_id' => $paymentIntent->id,
        'status' => $paymentIntent->status === 'succeeded' ? 'succeeded' : 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // 決済が成功した場合
    if ($paymentIntent->status === 'succeeded') {
        // 申込ステータスと本人確認ステータスを「確定」に更新
        // 注: kyc_statusとkyc_verified_atも含めて一度に更新
        $supabase->update('applications', [
            'kyc_status' => 'completed',
            'kyc_verified_at' => date('Y-m-d H:i:s'),
            'payment_status' => 'completed',
            'application_status' => 'confirmed',
            'charged_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => 'eq.' . $applicationId
        ]);

        echo json_encode([
            'success' => true,
            'payment_status' => 'completed',
            'payment_intent_id' => $paymentIntent->id,
            'message' => '本人確認と決済が完了しました'
        ]);
    } else {
        // 決済が保留中の場合（通常はWebhookで後処理）
        echo json_encode([
            'success' => true,
            'payment_status' => 'processing',
            'payment_intent_id' => $paymentIntent->id,
            'message' => '本人確認が完了し、決済処理中です'
        ]);
    }

} catch (\Stripe\Exception\CardException $e) {
    // カードエラー
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'カード決済に失敗しました: ' . $e->getMessage(),
        'error_type' => 'card_error'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'general_error',
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
