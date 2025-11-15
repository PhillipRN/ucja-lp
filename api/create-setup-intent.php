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
    $teamMemberId = $input['team_member_id'] ?? null;
    $teamMember = null;
    $teamApplication = null;
    
    if (!empty($teamMemberId)) {
        if ($application['participation_type'] !== 'team') {
            throw new Exception('メンバー情報はチーム戦でのみ指定できます');
        }

        $memberResult = $supabase->from('team_members')
            ->select('*')
            ->eq('id', $teamMemberId)
            ->single();

        if (!$memberResult['success'] || empty($memberResult['data'])) {
            throw new Exception('チームメンバーが見つかりません');
        }
        $teamMember = $memberResult['data'];

        $teamResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('id', $teamMember['team_application_id'])
            ->single();

        if (!$teamResult['success'] || empty($teamResult['data'])) {
            throw new Exception('チーム情報が見つかりません');
        }
        $teamApplication = $teamResult['data'];

        if ($teamApplication['application_id'] !== $applicationId) {
            throw new Exception('申込に紐づくメンバーではありません');
        }
    }

    $existingSetupIntentId = $teamMember ? ($teamMember['stripe_setup_intent_id'] ?? null) : ($application['stripe_setup_intent_id'] ?? null);

    if (!empty($existingSetupIntentId)) {
        $existingSetupIntent = \Stripe\SetupIntent::retrieve($existingSetupIntentId);
        if ($existingSetupIntent->status !== 'succeeded') {
            echo json_encode([
                'success' => true,
                'clientSecret' => $existingSetupIntent->client_secret,
                'setupIntentId' => $existingSetupIntent->id,
                'application_number' => $application['application_number']
            ]);
            exit;
        }
    }
    
    // 個人戦 or チーム戦の詳細情報を取得
    $customerEmail = '';
    $customerName = '';
    $description = '';
    
    if ($teamMember) {
        $customerEmail = $teamMember['member_email'];
        $customerName = $teamMember['member_name'];
        $description = sprintf(
            'Cambridge Exam カード登録（チーム戦メンバー%d） - 申込番号: %s - メンバー: %s',
            $teamMember['member_number'],
            $application['application_number'],
            $teamMember['member_name']
        );
    } elseif ($application['participation_type'] === 'individual') {
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
    $stripeCustomerId = $teamMember ? ($teamMember['stripe_customer_id'] ?? null) : ($application['stripe_customer_id'] ?? null);
    
    if (empty($stripeCustomerId)) {
        $customer = \Stripe\Customer::create([
            'email' => $customerEmail,
            'name' => $customerName,
            'description' => $description,
            'metadata' => [
                'application_id' => $applicationId,
                'application_number' => $application['application_number'],
                'participation_type' => $application['participation_type'],
                'team_member_id' => $teamMemberId
            ]
        ]);
        $stripeCustomerId = $customer->id;

        if ($teamMember) {
            $supabase->update('team_members', [
                'stripe_customer_id' => $stripeCustomerId
            ], [
                'id' => 'eq.' . $teamMemberId
            ]);
        } else {
            $supabase->update('applications', [
                'stripe_customer_id' => $stripeCustomerId
            ], [
                'id' => 'eq.' . $applicationId
            ]);
        }
    }
    
    $setupIntent = \Stripe\SetupIntent::create([
        'payment_method_types' => ['card'],
        'usage' => 'off_session', // 後日課金用
        'customer' => $stripeCustomerId,
        'description' => $description,
        'metadata' => [
            'application_id' => $applicationId,
            'application_number' => $application['application_number'],
            'participation_type' => $application['participation_type'],
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'amount' => $application['amount'],
            'team_member_id' => $teamMemberId
        ]
    ]);
    
    if ($teamMember) {
        $supabase->update('team_members', [
            'stripe_setup_intent_id' => $setupIntent->id,
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => 'eq.' . $teamMemberId
        ]);
    } else {
        $supabase->update('applications', [
            'stripe_setup_intent_id' => $setupIntent->id,
            'application_status' => 'card_pending'
        ], [
            'id' => 'eq.' . $applicationId
        ]);
    }
    
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

