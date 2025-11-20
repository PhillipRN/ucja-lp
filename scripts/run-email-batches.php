#!/usr/bin/env php
<?php
/**
 * Email Batch Processor
 *
 * - email_batchesテーブルのpendingレコードを取得し、scheduled_atが現在時刻を過ぎているものを処理
 * - 現状は team_member_payment（保護者宛リマインダー）のみ対応
 * - cron などから「php scripts/run-email-batches.php」を実行する想定
 *
 * オプション:
 *   --limit=10    : 一度に処理するバッチ件数を制限
 *   --dry-run     : 送信せずに内容のみログ出力
 *   --help        : ヘルプを表示
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/../lib/EmailTemplateService.php';

date_default_timezone_set('Asia/Tokyo');

$options = getopt('', ['limit::', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo <<<USAGE
Usage: php scripts/run-email-batches.php [--limit=5] [--dry-run]

USAGE;
    exit(0);
}

$limit = isset($options['limit']) ? max(1, (int)$options['limit']) : null;
$dryRun = array_key_exists('dry-run', $options);
$nowIso = date(DATE_ATOM);

echo sprintf("[%s] Email batch processor start (limit=%s, dryRun=%s)\n",
    date('Y-m-d H:i:s'),
    $limit ?? '∞',
    $dryRun ? 'yes' : 'no'
);

try {
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    $emailTemplateService = new EmailTemplateService($supabase);
} catch (Exception $e) {
    fwrite(STDERR, '[fatal] 初期化に失敗しました: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

try {
    $query = $supabase->from('email_batches')
        ->select('*')
        ->eq('status', 'pending')
        ->lte('scheduled_at', $nowIso)
        ->order('scheduled_at', true);

    if ($limit) {
        $query = $query->limit($limit);
    }

    $batchResult = $query->execute();

    if (!$batchResult['success']) {
        throw new Exception('email_batches の取得に失敗しました');
    }

    $batches = $batchResult['data'] ?? [];

    if (empty($batches)) {
        echo sprintf("[%s] 処理対象のバッチはありません\n", date('Y-m-d H:i:s'));
        exit(0);
    }

    foreach ($batches as $batch) {
        $batchId = $batch['id'] ?? null;
        if (!$batchId) {
            continue;
        }

        echo sprintf("[%s] Processing batch %s (%s)\n",
            date('Y-m-d H:i:s'),
            $batchId,
            $batch['batch_name'] ?? '(no name)'
        );

        try {
            $locked = lockBatch($supabase, $batchId);
            processBatch($supabase, $emailTemplateService, $locked, $dryRun);
        } catch (Exception $e) {
            fwrite(STDERR, sprintf("[error] batch %s failed: %s\n", $batchId, $e->getMessage()));
            markBatchFailed($supabase, $batchId, $e->getMessage());
        }
    }

    echo sprintf("[%s] すべてのバッチ処理が完了しました\n", date('Y-m-d H:i:s'));
    exit(0);
} catch (Exception $e) {
    fwrite(STDERR, '[fatal] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

/**
 * email_batches をロック
 */
function lockBatch(SupabaseClient $supabase, string $batchId): array
{
    $result = $supabase->update('email_batches', [
        'status' => 'processing',
        'started_at' => date(DATE_ATOM),
        'updated_at' => date(DATE_ATOM)
    ], [
        'id' => 'eq.' . $batchId,
        'status' => 'eq.pending'
    ]);

    if (!$result['success'] || empty($result['data'])) {
        throw new Exception('バッチのロックに失敗しました（他のプロセスが処理中の可能性）');
    }

    return $result['data'][0];
}

/**
 * バッチを処理
 */
function processBatch(
    SupabaseClient $supabase,
    EmailTemplateService $emailTemplateService,
    array $batch,
    bool $dryRun = false
): void {
    $templateRecord = fetchTemplateById($supabase, $batch['template_id'] ?? null);
    $templateType = $templateRecord['template_type'] ?? null;
    $filterPayload = json_decode($batch['recipient_filter'] ?? '{}', true) ?? [];
    $mode = $filterPayload['mode'] ?? 'all';
    $filters = $filterPayload['filters'] ?? [];
    if (!empty($filterPayload['application_ids'])) {
        $filters['application_ids'] = $filterPayload['application_ids'];
    }
    $deadline = $filterPayload['deadline'] ?? null;
    $testMode = (bool)($filterPayload['test_mode'] ?? false);

    if (empty($templateType)) {
        throw new Exception('テンプレート情報が取得できません');
    }

    switch ($templateType) {
        case 'team_member_payment':
            $stats = processTeamMemberPaymentBatch(
                $supabase,
                $emailTemplateService,
                $batch,
                $mode,
                $filters,
                $deadline,
                $dryRun || $testMode
            );
            break;
        default:
            throw new Exception('現在のスクリプトではこのテンプレートは処理できません: ' . $templateType);
    }

    $updateData = [
        'status' => $stats['failed'] > 0 ? 'failed' : 'completed',
        'sent_count' => $stats['sent'],
        'failed_count' => $stats['failed'],
        'total_recipients' => $stats['total'],
        'completed_at' => date(DATE_ATOM),
        'updated_at' => date(DATE_ATOM)
    ];

    $supabase->update('email_batches', $updateData, [
        'id' => 'eq.' . $batch['id']
    ]);
}

/**
 * チームメンバー支払い依頼のバッチを処理
 */
function processTeamMemberPaymentBatch(
    SupabaseClient $supabase,
    EmailTemplateService $emailTemplateService,
    array $batch,
    string $mode,
    array $filters,
    ?string $deadline,
    bool $dryRun = false
): array {
    $applications = fetchApplications($supabase, $mode, $filters);
    $deadlineLabel = formatDeadline($deadline);

    $total = 0;
    $sent = 0;
    $failed = 0;

    foreach ($applications as $app) {
        if (($app['participation_type'] ?? '') !== 'team') {
            continue;
        }

        $team = fetchTeamApplication($supabase, $app['id']);
        if (!$team) {
            continue;
        }

        $members = fetchPendingTeamMembers($supabase, $team['id']);
        if (empty($members)) {
            continue;
        }

        foreach ($members as $member) {
            $total++;
            $variables = buildGuardianReminderVariables($app, $team, $member, $deadlineLabel);

            if ($dryRun) {
                echo sprintf(
                    "  [dry-run] guardian=%s member=%s amount=%s deadline=%s\n",
                    $team['guardian_email'] ?? 'N/A',
                    $member['member_name'] ?? 'N/A',
                    $variables['amount'] ?? '0',
                    $variables['deadline'] ?? '未設定'
                );
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
                        'batch_id' => $batch['id'],
                        'metadata' => [
                            'team_member_id' => $member['id'],
                            'reminder_type' => 'guardian_payment_request'
                        ]
                    ]
                );
                $sent++;
            } catch (Exception $e) {
                $failed++;
                fwrite(STDERR, sprintf(
                    "[error] guardian reminder send failed (app=%s member=%s): %s\n",
                    $app['application_number'] ?? '-',
                    $member['member_name'] ?? '-',
                    $e->getMessage()
                ));
            }
        }
    }

    return [
        'total' => $total,
        'sent' => $sent,
        'failed' => $failed
    ];
}

/**
 * 送信対象の申込を取得
 */
function fetchApplications(SupabaseClient $supabase, string $mode, array $filters): array
{
    $query = $supabase->from('applications')->select('*');

    if ($mode === 'filter') {
        if (!empty($filters['status'])) {
            $query = $query->eq('application_status', $filters['status']);
        }
        if (!empty($filters['participation_type'])) {
            $query = $query->eq('participation_type', $filters['participation_type']);
        }
        if (!empty($filters['payment_status'])) {
            $query = $query->eq('payment_status', $filters['payment_status']);
        }
    } elseif ($mode === 'specific' && !empty($filters['application_ids']) && is_array($filters['application_ids'])) {
        $query = $query->in('id', $filters['application_ids']);
    }

    $result = $query->execute();

    if (!$result['success']) {
        throw new Exception('applications の取得に失敗しました');
    }

    return $result['data'] ?? [];
}

/**
 * テンプレート情報を取得
 */
function fetchTemplateById(SupabaseClient $supabase, ?string $templateId): array
{
    if (empty($templateId)) {
        return [];
    }

    $result = $supabase->from('email_templates')
        ->select('id, template_type, template_name')
        ->eq('id', $templateId)
        ->single();

    if ($result['success'] && !empty($result['data'])) {
        return $result['data'];
    }

    return [];
}

/**
 * チーム申込詳細を取得
 */
function fetchTeamApplication(SupabaseClient $supabase, string $applicationId): ?array
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

/**
 * 支払い未完了のチームメンバー一覧を取得
 */
function fetchPendingTeamMembers(SupabaseClient $supabase, string $teamApplicationId): array
{
    $result = $supabase->from('team_members')
        ->select('id, member_name, member_email, payment_status, member_number')
        ->eq('team_application_id', $teamApplicationId)
        ->neq('payment_status', 'completed')
        ->order('member_number', true)
        ->execute();

    if (!$result['success']) {
        throw new Exception('team_members の取得に失敗しました');
    }

    return $result['data'] ?? [];
}

/**
 * guardian向けテンプレート変数を構築
 */
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
        'team_name' => $team['team_name'] ?? '',
        'representative_name' => $team['guardian_name'] ?? '',
        'application_number' => $application['application_number'] ?? '',
        'amount' => number_format($amount),
        'payment_link' => $paymentLink,
        'deadline' => $deadlineLabel ?? '未設定',
        'member_email' => $member['member_email'] ?? ''
    ];
}

/**
 * 期限ラベルを整形
 */
function formatDeadline(?string $deadline): ?string
{
    if (empty($deadline)) {
        return null;
    }

    try {
        $dt = new DateTime($deadline, new DateTimeZone('Asia/Tokyo'));
        return $dt->format('Y年n月j日');
    } catch (Exception $e) {
        return $deadline;
    }
}

/**
 * バッチを失敗扱いに更新
 */
function markBatchFailed(SupabaseClient $supabase, string $batchId, string $reason): void
{
    $supabase->update('email_batches', [
        'status' => 'failed',
        'completed_at' => date(DATE_ATOM),
        'updated_at' => date(DATE_ATOM),
        'failed_count' => 0
    ], [
        'id' => 'eq.' . $batchId
    ]);
}

