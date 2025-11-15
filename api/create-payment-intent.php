<?php
/**
 * Create Stripe Payment Intent
 * Stripe決済用のPayment Intentを作成
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';

// Stripe PHPライブラリの読み込み
// Composerを使用する場合: composer require stripe/stripe-php
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
    
    // 既に支払い済みの場合はエラー
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
                'Cambridge Exam 申込（個人戦） - 申込番号: %s - 生徒: %s',
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
                'Cambridge Exam 申込（チーム戦） - 申込番号: %s - チーム: %s',
                $application['application_number'],
                $team['team_name']
            );
        }
    }
    
    // Stripe Payment Intent を作成
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $application['amount'],
        'currency' => 'jpy',
        'description' => $description,
        'receipt_email' => $customerEmail,
        'metadata' => [
            'application_id' => $applicationId,
            'application_number' => $application['application_number'],
            'participation_type' => $application['participation_type'],
            'customer_name' => $customerName
        ],
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
    ]);
    
    // Payment Intent IDをデータベースに保存
    $supabase->update('applications', [
        'stripe_payment_intent_id' => $paymentIntent->id
    ], [
        'id' => 'eq.' . $applicationId
    ]);
    
    // Payment Transaction レコードを作成
    $transactionData = [
        'application_id' => $applicationId,
        'transaction_type' => 'payment',
        'amount' => $application['amount'],
        'currency' => 'JPY',
        'stripe_payment_intent_id' => $paymentIntent->id,
        'status' => 'pending'
    ];
    
    $supabase->insert('payment_transactions', $transactionData);
    
    // クライアントシークレットを返す
    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent->client_secret,
        'amount' => $application['amount'],
        'application_number' => $application['application_number']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

