<?php
/**
 * Save Payment Method ID
 * SetupIntent成功後、PaymentMethod IDをデータベースに保存
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/../lib/EmailTemplateService.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $applicationId = $input['application_id'] ?? '';
    $paymentMethodId = $input['payment_method_id'] ?? '';
    $setupIntentId = $input['setup_intent_id'] ?? '';
    $teamMemberId = $input['team_member_id'] ?? null;
    
    if (empty($applicationId) || empty($paymentMethodId)) {
        throw new Exception('必須パラメータが不足しています');
    }
    
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    $emailTemplateService = new EmailTemplateService($supabase);

    $applicationResult = $supabase->from('applications')
        ->select('*')
        ->eq('id', $applicationId)
        ->single();

    if (!$applicationResult['success'] || empty($applicationResult['data'])) {
        throw new Exception('申込情報が取得できませんでした');
    }

    $application = $applicationResult['data'];
    $recipientOptions = [];
    $guardianName = '';
    $participantName = '';
    $teamApplication = null;

    if (!empty($teamMemberId)) {
        if ($application['participation_type'] !== 'team') {
            throw new Exception('メンバー情報はチーム戦のみ指定できます');
        }

        $memberResult = $supabase->from('team_members')
            ->select('*')
            ->eq('id', $teamMemberId)
            ->single();

        if (!$memberResult['success'] || empty($memberResult['data'])) {
            throw new Exception('チームメンバーが見つかりません');
        }
        $teamMember = $memberResult['data'];

        $teamAppResult = $supabase->from('team_applications')
            ->select('*')
            ->eq('id', $teamMember['team_application_id'])
            ->single();

        if (!$teamAppResult['success'] || empty($teamAppResult['data'])) {
            throw new Exception('チーム情報が見つかりません');
        }

        $teamApplication = $teamAppResult['data'];

        if ($teamApplication['application_id'] !== $applicationId) {
            throw new Exception('申込に紐づくメンバーではありません');
        }

        $recipientOptions['team_member_id'] = $teamMemberId;
        $guardianName = $teamApplication['guardian_name'] ?? '';
        $participantName = $teamMember['member_name'] ?? '';

        $memberUpdate = [
            'stripe_payment_method_id' => $paymentMethodId,
            'stripe_setup_intent_id' => $setupIntentId ?: ($teamMember['stripe_setup_intent_id'] ?? null),
            'card_registered' => true,
            'card_registered_at' => date('Y-m-d H:i:s'),
            'payment_status' => 'card_registered',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $updateResult = $supabase->update('team_members', $memberUpdate, [
            'id' => 'eq.' . $teamMemberId
        ]);

        if (!$updateResult['success']) {
            throw new Exception('メンバー情報の更新に失敗しました');
        }
    } else {
        $updateData = [
            'stripe_payment_method_id' => $paymentMethodId,
            'stripe_setup_intent_id' => $setupIntentId ?: ($application['stripe_setup_intent_id'] ?? null),
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
    }

    if ($application['participation_type'] === 'individual') {
        $individualResult = $supabase->from('individual_applications')
            ->select('guardian_name, student_name')
            ->eq('application_id', $applicationId)
            ->single();

        if ($individualResult['success'] && !empty($individualResult['data'])) {
            $guardianName = $individualResult['data']['guardian_name'] ?? $guardianName;
            $participantName = $individualResult['data']['student_name'] ?? $participantName;
        }
    } else {
        if (!$teamApplication) {
            $teamResult = $supabase->from('team_applications')
                ->select('guardian_name, team_name')
                ->eq('application_id', $applicationId)
                ->single();

            if ($teamResult['success'] && !empty($teamResult['data'])) {
                $teamApplication = $teamResult['data'];
            }
        }

        if ($teamApplication) {
            $guardianName = $teamApplication['guardian_name'] ?? $guardianName;
            if (!$participantName) {
                $participantName = $teamApplication['team_name'] ?? $participantName;
            }
        }
    }
    
    try {
        $emailTemplateService->sendTemplateToApplication(
            'card_registration_completed',
            $applicationId,
            [
                'guardian_name' => $guardianName,
                'participant_name' => $participantName,
                'application_number' => $application['application_number']
            ],
            ['recipient_options' => $recipientOptions]
        );

        $emailTemplateService->sendTemplateToApplication(
            'kyc_required',
            $applicationId,
            [
                'guardian_name' => $guardianName,
                'participant_name' => $participantName,
                'application_number' => $application['application_number']
            ],
            ['recipient_options' => $recipientOptions]
        );
    } catch (Exception $mailException) {
        error_log('[save-payment-method] KYC依頼メール送信に失敗: ' . $mailException->getMessage());
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

?>

