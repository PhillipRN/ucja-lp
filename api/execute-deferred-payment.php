<?php
/**
 * Execute Deferred Payment
 * 本人確認完了後、保存されたPaymentMethod IDを使って後日課金を実行
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/../vendor/autoload.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // POSTデータの取得
    $input = json_decode(file_get_contents('php://input'), true);
    $applicationId = $input['application_id'] ?? '';
    
    if (empty($applicationId)) {
        throw new Exception('申込IDが指定されていません');
    }
    
    // Supabaseから申込情報を取得
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    
    $applicationResult = $supabase->from('applications')
        ->select('*')
        ->eq('id', $applicationId)
        ->single();
    
    if (!$applicationResult['success'] || empty($applicationResult['data'])) {
        throw new Exception('申込情報が見つかりません');
    }
    
    $application = $applicationResult['data'];
    
    // 検証: 本人確認が完了しているか
    if ($application['kyc_status'] !== 'approved') {
        throw new Exception('本人確認が完了していません');
    }
    
    // 検証: カード情報が登録されているか
    if (empty($application['stripe_payment_method_id'])) {
        throw new Exception('カード情報が登録されていません');
    }
    
    // 検証: 既に支払い済みでないか
    if ($application['payment_status'] === 'completed') {
        throw new Exception('この申込は既に支払い済みです');
    }
    
    // 個人戦 or チーム戦の詳細情報を取得
    $customerEmail = '';
    $customerName = '';
    $description = '';
    
    if ($application['participation_type'] === 'individual') {
        $individualResult = $supabase->from('individual_applications')
            ->select('*')
            ->eq('application_id', $applicationId)
            ->single();
        
        if ($individualResult['success'] && !empty($individualResult['data'])) {
            $individual = $individualResult['data'];
            $customerEmail = $individual['guardian_email'];
            $customerName = $individual['guardian_name'];
            $description = sprintf(
                'Cambridge Exam 参加費（個人戦） - 申込番号: %s - 生徒: %s',
                $application['application_number'],
                $individual['student_name']
            );
        }
    } else {
        $teamResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('application_id', $applicationId)
            ->single();
        
        if ($teamResult['success'] && !empty($teamResult['data'])) {
            $team = $teamResult['data'];
            $customerEmail = $team['guardian_email'];
            $customerName = $team['guardian_name'];
            $description = sprintf(
                'Cambridge Exam 参加費（チーム戦） - 申込番号: %s - チーム: %s',
                $application['application_number'],
                $team['team_name']
            );
        }
    }
    
    // Stripe PaymentIntent を作成（保存されたPaymentMethodを使用）
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $application['amount'],
        'currency' => 'jpy',
        'description' => $description,
        'receipt_email' => $customerEmail,
        'payment_method' => $application['stripe_payment_method_id'],
        'confirm' => true, // 即座に決済を確定
        'off_session' => true, // オフセッション決済
        'metadata' => [
            'application_id' => $applicationId,
            'application_number' => $application['application_number'],
            'participation_type' => $application['participation_type'],
            'customer_name' => $customerName,
            'trigger' => 'kyc_approved'
        ]
    ]);
    
    // Payment Intent IDをデータベースに保存
    $updateData = [
        'stripe_payment_intent_id' => $paymentIntent->id,
        'payment_status' => 'processing'
    ];
    
    // 決済が成功した場合
    if ($paymentIntent->status === 'succeeded') {
        $updateData['payment_status'] = 'completed';
        $updateData['payment_completed_at'] = date('Y-m-d H:i:s');
    }
    
    $supabase->update('applications', $updateData, [
        'id' => 'eq.' . $applicationId
    ]);
    
    // Payment Transaction レコードを作成
    $transactionData = [
        'application_id' => $applicationId,
        'transaction_type' => 'payment',
        'amount' => $application['amount'],
        'currency' => 'JPY',
        'stripe_payment_intent_id' => $paymentIntent->id,
        'status' => $paymentIntent->status === 'succeeded' ? 'completed' : 'processing',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $supabase->insert('payment_transactions', $transactionData);
    
    // 結果を返す
    echo json_encode([
        'success' => true,
        'paymentIntentId' => $paymentIntent->id,
        'status' => $paymentIntent->status,
        'amount' => $application['amount'],
        'application_number' => $application['application_number']
    ]);
    
} catch (\Stripe\Exception\CardException $e) {
    // カードエラー（残高不足など）
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'カード決済エラー: ' . $e->getError()->message,
        'error_type' => 'card_error'
    ]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Stripe APIエラー
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Stripe APIエラー: ' . $e->getMessage(),
        'error_type' => 'api_error'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => 'general_error'
    ]);
}

