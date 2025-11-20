<?php
/**
 * 一斉メール送信API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/SupabaseClient.php';
require_once __DIR__ . '/../../lib/AdminAuthHelper.php';
require_once __DIR__ . '/../../lib/EmailTemplateService.php';

try {
    // 管理者認証チェック
    AdminAuthHelper::startSession();
    if (!AdminAuthHelper::isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => '認証が必要です'
        ]);
        exit;
    }

    // POSTデータ取得
    $input = json_decode(file_get_contents('php://input'), true);
    
    $templateId = $input['template_id'] ?? '';
    $recipientType = $input['recipient_type'] ?? 'all'; // all, specific, filter
    $applicationIds = $input['application_ids'] ?? []; // specific用
    $filters = $input['filters'] ?? []; // filter用
    $testMode = $input['test_mode'] ?? false; // テストモード
    $scheduleAtInput = $input['schedule_at'] ?? null;
    $deadlineInput = $input['deadline'] ?? null;

    if (empty($templateId)) {
        throw new Exception('テンプレートを選択してください');
    }

    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    $admin = AdminAuthHelper::getAdminInfo();

    // テンプレート取得
    $templateResult = $supabase->from('email_templates')
        ->select('*')
        ->eq('id', $templateId)
        ->single();

    if (!$templateResult['success'] || empty($templateResult['data'])) {
        throw new Exception('テンプレートが見つかりません');
    }

    $template = $templateResult['data'];

    if (!$template['is_active']) {
        throw new Exception('このテンプレートは無効になっています');
    }

    // 送信予約のバリデーション
    $scheduledAtIso = null;
    $scheduleTimezone = new DateTimeZone('Asia/Tokyo');

    if (!empty($scheduleAtInput)) {
        try {
            $scheduleDateTime = new DateTime($scheduleAtInput, $scheduleTimezone);
        } catch (Exception $dtException) {
            throw new Exception('送信予定日時の形式が正しくありません');
        }

        $now = new DateTime('now', $scheduleTimezone);
        if ($scheduleDateTime <= $now) {
            throw new Exception('送信予定日時は現在より後の時間を指定してください');
        }

        $scheduledAtIso = $scheduleDateTime->format(DateTime::ATOM);
    }

    if ($scheduledAtIso !== null) {
        if ($template['template_type'] !== 'team_member_payment') {
            throw new Exception('このテンプレートでは送信予約を利用できません');
        }

        $filterPayload = [
            'mode' => $recipientType,
            'filters' => $filters,
            'application_ids' => $applicationIds,
            'deadline' => $deadlineInput,
            'test_mode' => (bool)$testMode
        ];

        $batchData = [
            'batch_name' => $template['template_name'] . '（予約送信）',
            'subject' => $template['subject'],
            'template_id' => $templateId,
            'recipient_type' => 'guardian',
            'recipient_filter' => json_encode($filterPayload),
            'status' => 'pending',
            'created_by' => $admin['id'],
            'scheduled_at' => $scheduledAtIso
        ];

        $batchResult = $supabase->insert('email_batches', $batchData);

        if (!$batchResult['success']) {
            throw new Exception('送信予約の作成に失敗しました');
        }

        $supabase->insert('admin_activity_logs', [
            'admin_id' => $admin['id'],
            'action' => 'schedule_bulk_email',
            'details' => json_encode([
                'template_id' => $templateId,
                'template_name' => $template['template_name'],
                'scheduled_at' => $scheduledAtIso,
                'filters' => $filters
            ])
        ]);

        echo json_encode([
            'success' => true,
            'message' => '送信予約を登録しました（' . (new DateTime($scheduledAtIso))->setTimezone($scheduleTimezone)->format('Y-m-d H:i') . ' 送信予定）'
        ]);
        exit;
    }

    // 送信対象の申込を取得
    $query = $supabase->from('applications')->select('*');

    if ($recipientType === 'specific' && !empty($applicationIds)) {
        // 特定の申込に送信
        $query = $query->in('id', $applicationIds);
    } elseif ($recipientType === 'filter' && !empty($filters)) {
        // フィルター条件で絞り込み
        if (!empty($filters['status'])) {
            $query = $query->eq('application_status', $filters['status']);
        }
        if (!empty($filters['participation_type'])) {
            $query = $query->eq('participation_type', $filters['participation_type']);
        }
        if (!empty($filters['payment_status'])) {
            $query = $query->eq('payment_status', $filters['payment_status']);
        }
    }
    // elseif $recipientType === 'all' の場合は全件

    $applicationsResult = $query->execute();

    if (!$applicationsResult['success'] || empty($applicationsResult['data'])) {
        throw new Exception('送信対象の申込が見つかりません');
    }

    $applications = $applicationsResult['data'];

    // guardian向けチームメンバー支払い依頼（即時送信）
    if ($template['template_type'] === 'team_member_payment') {
        $emailTemplateService = new EmailTemplateService($supabase);
        $deadlineLabel = formatDeadlineLabel($deadlineInput);

        $stats = sendGuardianPaymentReminders(
            $applications,
            $supabase,
            $emailTemplateService,
            $deadlineLabel,
            $testMode
        );

        $supabase->insert('admin_activity_logs', [
            'admin_id' => $admin['id'],
            'action' => 'send_team_member_payment_guardian',
            'details' => json_encode([
                'template_id' => $templateId,
                'template_name' => $template['template_name'],
                'recipient_type' => $recipientType,
                'recipient_count' => $stats['total'],
                'sent' => $stats['sent'],
                'failed' => $stats['failed'],
                'test_mode' => $testMode,
                'deadline' => $deadlineLabel
            ])
        ]);

        $message = $testMode
            ? sprintf('テストモード: %d件のリマインダーをシミュレーションしました（実送信なし）', $stats['total'])
            : sprintf('%d件の保護者宛リマインダーを送信しました（失敗 %d）', $stats['sent'], $stats['failed']);

        echo json_encode([
            'success' => true,
            'message' => $message,
            'count' => $stats['sent'],
            'failed' => $stats['failed']
        ]);
        exit;
    }

    // それ以外のテンプレート: メールログを作成
    $emailLogs = [];
    $createdCount = 0;

    foreach ($applications as $app) {
        // 個人戦/チーム戦で保護者のメールアドレスを取得
        $recipientEmail = '';
        $recipientName = '';

        if ($app['participation_type'] === 'individual') {
            $detailResult = $supabase->from('individual_applications')
                ->select('guardian_email, guardian_name')
                ->eq('application_id', $app['id'])
                ->single();
            
            if ($detailResult['success'] && !empty($detailResult['data'])) {
                $recipientEmail = $detailResult['data']['guardian_email'];
                $recipientName = $detailResult['data']['guardian_name'];
            }
        } else {
            $detailResult = $supabase->from('team_applications')
                ->select('guardian_email, guardian_name')
                ->eq('application_id', $app['id'])
                ->single();
            
            if ($detailResult['success'] && !empty($detailResult['data'])) {
                $recipientEmail = $detailResult['data']['guardian_email'];
                $recipientName = $detailResult['data']['guardian_name'];
            }
        }

        if (empty($recipientEmail)) {
            continue; // メールアドレスがない場合はスキップ
        }

        // メールログ作成
        $emailLogData = [
            'application_id' => $app['id'],
            'email_type' => $template['template_type'],
            'template_id' => $templateId,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $template['subject'],
            'body_text' => $template['body_text'],
            'body_html' => $template['body_html'],
            'status' => $testMode ? 'test' : 'pending',
            'scheduled_at' => date('Y-m-d H:i:s')
        ];

        $logResult = $supabase->insert('email_logs', $emailLogData);

        if ($logResult['success']) {
            $createdCount++;
        }
    }

    // 管理者アクティビティログ記録
    $supabase->insert('admin_activity_logs', [
        'admin_id' => $admin['id'],
        'action' => 'send_bulk_email',
        'details' => json_encode([
            'template_id' => $templateId,
            'template_name' => $template['template_name'],
            'recipient_type' => $recipientType,
            'recipient_count' => $createdCount,
            'test_mode' => $testMode
        ])
    ]);

    echo json_encode([
        'success' => true,
        'message' => $testMode 
            ? "テストモード: {$createdCount}件のメールログを作成しました（実際には送信されません）"
            : "{$createdCount}件のメールを送信キューに追加しました",
        'count' => $createdCount
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function sendGuardianPaymentReminders(
    array $applications,
    SupabaseClient $supabase,
    EmailTemplateService $emailTemplateService,
    ?string $deadlineLabel,
    bool $testMode = false
): array {
    $total = 0;
    $sent = 0;
    $failed = 0;

    foreach ($applications as $app) {
        if (($app['participation_type'] ?? '') !== 'team') {
            continue;
        }

        $team = fetchTeamApplicationForBatch($supabase, $app['id']);
        if (!$team) {
            continue;
        }

        $members = fetchPendingMembersForBatch($supabase, $team['id']);
        if (empty($members)) {
            continue;
        }

        foreach ($members as $member) {
            $total++;
            $variables = buildGuardianReminderVariables($app, $team, $member, $deadlineLabel);

            if ($testMode) {
                $sent++;
                continue;
            }

            try {
                $emailTemplateService->sendTemplate(
                    'team_member_payment',
                    [
                        'email' => $team['guardian_email'],
                        'name' => $team['guardian_name']
                    ],
                    $variables,
                    [
                        'application_id' => $app['id'],
                        'metadata' => [
                            'team_member_id' => $member['id'],
                            'reminder_type' => 'guardian_payment_request'
                        ]
                    ]
                );
                $sent++;

                $supabase->update('team_members', [
                    'payment_link_sent_at' => date(DATE_ATOM)
                ], [
                    'id' => 'eq.' . $member['id']
                ]);
            } catch (Exception $e) {
                $failed++;
                error_log(sprintf('[send-bulk-email] guardian reminder failed: %s', $e->getMessage()));
            }
        }
    }

    return [
        'total' => $total,
        'sent' => $sent,
        'failed' => $failed
    ];
}

function fetchTeamApplicationForBatch(SupabaseClient $supabase, string $applicationId): ?array
{
    $result = $supabase->from('team_applications')
        ->select('id, team_name, guardian_name, guardian_email')
        ->eq('application_id', $applicationId)
        ->single();

    if ($result['success'] && !empty($result['data'])) {
        return $result['data'];
    }

    return null;
}

function fetchPendingMembersForBatch(SupabaseClient $supabase, string $teamApplicationId): array
{
    $result = $supabase->from('team_members')
        ->select('id, member_name, member_email, member_number, payment_status')
        ->eq('team_application_id', $teamApplicationId)
        ->neq('payment_status', 'completed')
        ->order('member_number', true)
        ->execute();

    if ($result['success']) {
        return $result['data'] ?? [];
    }

    return [];
}

function buildGuardianReminderVariables(
    array $application,
    array $team,
    array $member,
    ?string $deadlineLabel
): array {
    $amount = defined('REGULAR_PRICE') ? REGULAR_PRICE : 0;
    $paymentLink = rtrim(APP_URL, '/') . '/my-page/team-status.php';

    return [
        'guardian_name' => $team['guardian_name'] ?? '',
        'member_name' => $member['member_name'] ?? '',
        'member_email' => $member['member_email'] ?? '',
        'team_name' => $team['team_name'] ?? '',
        'representative_name' => $team['guardian_name'] ?? '',
        'application_number' => $application['application_number'] ?? '',
        'amount' => number_format($amount),
        'payment_link' => $paymentLink,
        'deadline' => $deadlineLabel ?? '未設定'
    ];
}

function formatDeadlineLabel(?string $deadline): ?string
{
    if (!$deadline) {
        return null;
    }

    try {
        $dt = new DateTime($deadline, new DateTimeZone('Asia/Tokyo'));
        return $dt->format('Y年n月j日');
    } catch (Exception $e) {
        return $deadline;
    }
}

