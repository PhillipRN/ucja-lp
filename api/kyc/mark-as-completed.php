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
require_once __DIR__ . '/../../lib/EmailTemplateService.php';

try {
    // POSTデータ取得
    $input = json_decode(file_get_contents('php://input'), true);
    
    $applicationId = $input['application_id'] ?? '';
    $teamMemberId = $input['team_member_id'] ?? null;

    if (empty($applicationId)) {
        throw new Exception('申込IDが指定されていません');
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    $emailTemplateService = new EmailTemplateService($supabase);

    // 申込情報を取得
    $appResult = $supabase->from('applications')
        ->select('*')
        ->eq('id', $applicationId)
        ->execute();

    if (!$appResult['success'] || empty($appResult['data']) || count($appResult['data']) === 0) {
        throw new Exception('申込情報が見つかりません');
    }

    $application = $appResult['data'][0];

    $recipientOptions = [];
    $guardianName = '';
    $participantName = '';

    if (!empty($teamMemberId)) {
        if ($application['participation_type'] !== 'team') {
            throw new Exception('メンバー本人確認はチーム戦のみ利用できます');
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
            ->select('application_id, guardian_name, team_name')
            ->eq('id', $teamMember['team_application_id'])
            ->single();

        if (!$teamAppResult['success'] || empty($teamAppResult['data']) || $teamAppResult['data']['application_id'] !== $applicationId) {
            throw new Exception('申込に紐づくメンバーではありません');
        }

        if (!$teamMember['card_registered']) {
            throw new Exception('このメンバーのカード情報が登録されていません');
        }

        if ($teamMember['kyc_status'] === 'completed') {
            echo json_encode([
                'success' => true,
                'already_completed' => true,
                'message' => 'このメンバーの本人確認は完了済みです'
            ]);
            exit;
        }

        $recipientOptions['team_member_id'] = $teamMemberId;
        $guardianName = $teamAppResult['data']['guardian_name'] ?? '';
        $participantName = $teamMember['member_name'] ?? '';

        $memberUpdate = $supabase->update('team_members', [
            'kyc_status' => 'completed',
            'kyc_verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => 'eq.' . $teamMemberId
        ]);

        if (!$memberUpdate['success']) {
            throw new Exception('メンバーの本人確認更新に失敗しました');
        }
    } else {
        if (!$application['card_registered']) {
            throw new Exception('カード情報が登録されていません');
        }

        if (empty($application['stripe_customer_id']) || empty($application['stripe_payment_method_id'])) {
            throw new Exception('Stripe情報が不足しています。カード情報を再登録してください。');
        }

        if ($application['kyc_status'] === 'completed') {
            echo json_encode([
                'success' => true,
                'already_completed' => true,
                'message' => '既に本人確認は完了しています'
            ]);
            exit;
        }

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

        if ($application['participation_type'] === 'individual') {
            $individualResult = $supabase->from('individual_applications')
                ->select('guardian_name, student_name')
                ->eq('application_id', $applicationId)
                ->single();

            if ($individualResult['success'] && !empty($individualResult['data'])) {
                $guardianName = $individualResult['data']['guardian_name'] ?? '';
                $participantName = $individualResult['data']['student_name'] ?? '';
            }
        } else {
            $teamResult = $supabase->from('team_applications')
                ->select('guardian_name, team_name')
                ->eq('application_id', $applicationId)
                ->single();

            if ($teamResult['success'] && !empty($teamResult['data'])) {
                $guardianName = $teamResult['data']['guardian_name'] ?? '';
                $participantName = $teamResult['data']['team_name'] ?? '';
            }
        }
    }

    try {
        $emailTemplateService->sendTemplateToApplication(
            'kyc_completed',
            $applicationId,
            [
                'guardian_name' => $guardianName,
                'participant_name' => $participantName,
                'application_number' => $application['application_number'],
                'amount' => number_format($application['amount'])
            ],
            ['recipient_options' => $recipientOptions]
        );
    } catch (Exception $mailException) {
        error_log('[kyc/mark-as-completed] メール送信に失敗しました: ' . $mailException->getMessage());
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

