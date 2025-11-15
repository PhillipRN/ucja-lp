<?php
/**
 * Create Stripe Setup Intent
 * カード情報登録用のSetupIntentを作成（後日課金方式）
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
    
    // 既にSetupIntent IDが存在する場合は再利用
    if (!empty($application['stripe_setup_intent_id'])) {
        $setupIntent = \Stripe\SetupIntent::retrieve($application['stripe_setup_intent_id']);
        
        // 既に成功している場合は新規作成
        if ($setupIntent->status === 'succeeded') {
            // 新しいSetupIntentを作成
        } else {
            // 既存のSetupIntentを返す
            echo json_encode([
                'success' => true,
                'clientSecret' => $setupIntent->client_secret,
                'setupIntentId' => $setupIntent->id,
                'application_number' => $application['application_number']
            ]);
            exit;
        }
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
                'Cambridge Exam カード登録（個人戦） - 申込番号: %s - 生徒: %s',
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
                'Cambridge Exam カード登録（チーム戦） - 申込番号: %s - チーム: %s',
                $application['application_number'],
                $team['team_name']
            );
        }
    }
    
    // Stripe Customerを作成（または既存のものを使用）
    $stripeCustomerId = $application['stripe_customer_id'];
    
    if (empty($stripeCustomerId)) {
        // 新規Customer作成
        $customer = \Stripe\Customer::create([
            'email' => $customerEmail,
            'name' => $customerName,
            'description' => $description,
            'metadata' => [
                'application_id' => $applicationId,
                'application_number' => $application['application_number'],
                'participation_type' => $application['participation_type']
            ]
        ]);
        $stripeCustomerId = $customer->id;
        
        // Customer IDをDBに保存
        $supabase->update('applications', [
            'stripe_customer_id' => $stripeCustomerId
        ], [
            'id' => 'eq.' . $applicationId
        ]);
    }
    
    // Stripe Setup Intent を作成（Customerに紐付け）
    $setupIntent = \Stripe\SetupIntent::create([
        'payment_method_types' => ['card'],
        'usage' => 'off_session', // 後日課金用
        'customer' => $stripeCustomerId, // 重要！Customerに紐付け
        'description' => $description,
        'metadata' => [
            'application_id' => $applicationId,
            'application_number' => $application['application_number'],
            'participation_type' => $application['participation_type'],
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'amount' => $application['amount'] // 参考情報として保存
        ]
    ]);
    
    // Setup Intent IDをデータベースに保存
    $supabase->update('applications', [
        'stripe_setup_intent_id' => $setupIntent->id,
        'application_status' => 'card_pending'
    ], [
        'id' => 'eq.' . $applicationId
    ]);
    
    // クライアントシークレットを返す
    echo json_encode([
        'success' => true,
        'clientSecret' => $setupIntent->client_secret,
        'setupIntentId' => $setupIntent->id,
        'application_number' => $application['application_number']
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Stripeエラー: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

