#!/usr/bin/env php
<?php
/**
 * Scheduled Charges Processor
 *
 *  - scheduled_chargesテーブルから課金予定を取得
 *  - Stripe PaymentIntentを作成してオフセッション決済を実行
 *  - 成功 / 失敗に応じて applications / team_members / payment_transactions / email_logs を更新
 *  - cron などから「php scripts/run-scheduled-charges.php」を実行する想定
 *
 *  オプション:
 *    --date=YYYY-MM-DD  : 指定日までのレコードを処理（デフォルト: 本日）
 *    --limit=10         : 1回の実行で処理する件数上限
 *    --dry-run          : Stripe APIを呼び出さず実行内容をログ出力のみ
 *    --help             : 使い方を表示
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/../lib/EmailTemplateService.php';

use Stripe\Stripe;
use Stripe\Exception\CardException;
use Stripe\Exception\ApiErrorException;

date_default_timezone_set('Asia/Tokyo');

$options = getopt('', ['date::', 'limit::', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo <<<USAGE
Usage: php scripts/run-scheduled-charges.php [--date=YYYY-MM-DD] [--limit=20] [--dry-run]

USAGE;
    exit(0);
}

$targetDate = isset($options['date']) ? $options['date'] : date('Y-m-d');
$limit = isset($options['limit']) ? max(1, (int)$options['limit']) : null;
$dryRun = array_key_exists('dry-run', $options);

echo sprintf("[%s] Start scheduled charge processor (target <= %s, limit=%s, dryRun=%s)\n",
    date('Y-m-d H:i:s'),
    $targetDate,
    $limit ?? '∞',
    $dryRun ? 'yes' : 'no'
);

try {
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    $emailTemplateService = new EmailTemplateService($supabase);
    Stripe::setApiKey(STRIPE_SECRET_KEY);
} catch (Exception $e) {
    fwrite(STDERR, '[fatal] 初期化に失敗しました: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

try {
    $chargesQuery = $supabase->from('scheduled_charges')
        ->select('*')
        ->eq('status', 'scheduled')
        ->lte('scheduled_date', $targetDate)
        ->order('scheduled_date', true);

    if ($limit) {
        $chargesQuery = $chargesQuery->limit($limit);
    }

    $chargesResult = $chargesQuery->execute();

    if (!$chargesResult['success']) {
        throw new Exception('scheduled_chargesの取得に失敗しました');
    }

    $charges = $chargesResult['data'] ?? [];

    if (empty($charges)) {
        echo sprintf("[%s] 対象レコードなし\n", date('Y-m-d H:i:s'));
        exit(0);
    }

    $processed = 0;
    $failed = 0;

    foreach ($charges as $charge) {
        $chargeId = $charge['id'] ?? '(unknown)';
        echo sprintf("[%s] Processing charge %s\n", date('Y-m-d H:i:s'), $chargeId);

        try {
            processCharge($charge, $supabase, $emailTemplateService, $dryRun);
            $processed++;
        } catch (Exception $e) {
            $failed++;
            fwrite(STDERR, sprintf("[error] charge %s failed: %s\n", $chargeId, $e->getMessage()));
        }
    }

    echo sprintf("[%s] 完了: success=%d / failed=%d\n", date('Y-m-d H:i:s'), $processed, $failed);
    exit($failed > 0 ? 2 : 0);

} catch (Exception $e) {
    fwrite(STDERR, '[fatal] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

/**
 * scheduled_charges 1件を処理
 */
function processCharge(array $charge, SupabaseClient $supabase, EmailTemplateService $emailTemplateService, bool $dryRun = false): void
{
    $chargeId = $charge['id'] ?? null;

    if (empty($chargeId)) {
        throw new Exception('scheduled_charges.id が取得できません');
    }

    if ($dryRun) {
        echo sprintf("  [dry-run] charge=%s amount=%d currency=%s\n",
            $chargeId,
            $charge['amount'] ?? 0,
            strtoupper($charge['currency'] ?? 'JPY')
        );
        return;
    }

    lockCharge($supabase, $chargeId);

    try {
        $context = buildChargeContext($supabase, $charge);
        $paymentIntent = createPaymentIntent($supabase, $charge, $context);

        if ($paymentIntent->status !== 'succeeded') {
            handleStripeError(
                $supabase,
                $charge,
                $context,
                $paymentIntent->status,
                'PaymentIntentがsucceeded以外のステータスで終了しました'
            );
            throw new Exception('PaymentIntent status: ' . $paymentIntent->status);
        }

        finalizeCharge($supabase, $charge, $context, $paymentIntent);
        sendPaymentConfirmationEmail($emailTemplateService, $charge, $context);
    } catch (Exception $e) {
        markProcessingChargeAsFailed($supabase, $charge, 'internal_error', $e->getMessage());
        throw $e;
    }
}

/**
 * ステータスをprocessingにしてロック
 */
function lockCharge(SupabaseClient $supabase, string $chargeId): void
{
    $result = $supabase->update('scheduled_charges', [
        'status' => 'processing',
        'updated_at' => date('c')
    ], [
        'id' => 'eq.' . $chargeId,
        'status' => 'eq.scheduled'
    ]);

    if (
        !$result['success'] ||
        empty($result['data'])
    ) {
        throw new Exception('scheduled_chargesのロックに失敗しました（他のプロセスが処理済みの可能性）');
    }
}

/**
 * Stripe PaymentIntentを作成
 */
function createPaymentIntent(SupabaseClient $supabase, array $charge, array $context)
{
    if (empty($charge['stripe_customer_id']) || empty($charge['stripe_payment_method_id'])) {
        throw new Exception('Stripe顧客またはPaymentMethodが設定されていません');
    }

    $metadata = array_filter([
        'application_id' => $context['application']['id'] ?? null,
        'application_number' => $context['application']['application_number'] ?? null,
        'team_member_id' => $context['team_member']['id'] ?? null,
        'scheduled_charge_id' => $charge['id'] ?? null,
        'trigger' => 'scheduled_charge'
    ]);

    $description = $context['description'] ?? ('Application charge ' . ($context['application']['application_number'] ?? ''));
    $params = [
        'amount' => (int)($charge['amount'] ?? 0),
        'currency' => strtolower($charge['currency'] ?? 'jpy'),
        'customer' => $charge['stripe_customer_id'],
        'payment_method' => $charge['stripe_payment_method_id'],
        'off_session' => true,
        'confirm' => true,
        'description' => $description,
        'metadata' => $metadata
    ];

    if (!empty($context['receipt_email'])) {
        $params['receipt_email'] = $context['receipt_email'];
    }

    try {
        return \Stripe\PaymentIntent::create($params);
    } catch (CardException $e) {
        handleStripeError($supabase, $charge, $context, $e->getError()->code ?? 'card_error', $e->getMessage());
        throw $e;
    } catch (ApiErrorException $e) {
        handleStripeError($supabase, $charge, $context, $e->getError()->code ?? 'api_error', $e->getMessage());
        throw $e;
    }
}

/**
 * 成功時に各テーブルを更新
 */
function finalizeCharge(SupabaseClient $supabase, array $charge, array $context, $paymentIntent): void
{
    $chargeId = $charge['id'];
    $applicationId = $context['application']['id'] ?? null;
    $teamMemberId = $context['team_member']['id'] ?? null;
    $now = date('c');

    $supabase->update('scheduled_charges', [
        'status' => 'completed',
        'executed_at' => $now,
        'stripe_payment_intent_id' => $paymentIntent->id,
        'error_code' => null,
        'error_message' => null
    ], [
        'id' => 'eq.' . $chargeId
    ]);

    if ($teamMemberId) {
        $supabase->update('team_members', [
            'payment_status' => 'completed',
            'stripe_payment_intent_id' => $paymentIntent->id,
            'charged_at' => $now,
            'updated_at' => $now
        ], [
            'id' => 'eq.' . $teamMemberId
        ]);
    } elseif ($applicationId) {
        $supabase->update('applications', [
            'payment_status' => 'completed',
            'application_status' => 'confirmed',
            'stripe_payment_intent_id' => $paymentIntent->id,
            'charged_at' => $now,
            'paid_at' => $now
        ], [
            'id' => 'eq.' . $applicationId
        ]);
    }

    $supabase->insert('payment_transactions', [
        'application_id' => $applicationId,
        'team_member_id' => $teamMemberId,
        'transaction_type' => 'payment',
        'amount' => (int)($charge['amount'] ?? 0),
        'currency' => strtoupper($charge['currency'] ?? 'JPY'),
        'stripe_customer_id' => $charge['stripe_customer_id'] ?? null,
        'stripe_payment_method_id' => $charge['stripe_payment_method_id'] ?? null,
        'stripe_payment_intent_id' => $paymentIntent->id,
        'status' => 'succeeded',
        'created_at' => $now
    ]);
}

/**
 * Stripeエラー時のscheduled_charges更新
 */
function handleStripeError(SupabaseClient $supabase, array $charge, array $context, string $errorCode, string $message): void
{
    $retryCount = (int)($charge['retry_count'] ?? 0);

    $supabase->update('scheduled_charges', [
        'status' => 'failed',
        'error_code' => $errorCode,
        'error_message' => $message,
        'retry_count' => $retryCount + 1,
        'updated_at' => date('c')
    ], [
        'id' => 'eq.' . ($charge['id'] ?? '')
    ]);

    $supabase->insert('payment_transactions', [
        'application_id' => $context['application']['id'] ?? null,
        'team_member_id' => $context['team_member']['id'] ?? null,
        'transaction_type' => 'payment',
        'amount' => (int)($charge['amount'] ?? 0),
        'currency' => strtoupper($charge['currency'] ?? 'JPY'),
        'stripe_customer_id' => $charge['stripe_customer_id'] ?? null,
        'stripe_payment_method_id' => $charge['stripe_payment_method_id'] ?? null,
        'status' => 'failed',
        'error_code' => $errorCode,
        'error_message' => $message,
        'created_at' => date('c')
    ]);
}

function markProcessingChargeAsFailed(SupabaseClient $supabase, array $charge, string $code, string $message): void
{
    if (empty($charge['id'])) {
        return;
    }

    $supabase->update('scheduled_charges', [
        'status' => 'failed',
        'error_code' => $code,
        'error_message' => $message,
        'updated_at' => date('c')
    ], [
        'id' => 'eq.' . $charge['id'],
        'status' => 'eq.processing'
    ]);
}

/**
 * 送信先やメール変数に必要な情報を集約
 */
function buildChargeContext(SupabaseClient $supabase, array $charge): array
{
    $application = null;
    $individual = null;
    $team = null;
    $teamMember = null;
    $receiptEmail = null;
    $guardianName = '';
    $participantName = '';
    $description = '';

    if (!empty($charge['application_id'])) {
        $application = fetchApplication($supabase, $charge['application_id']);
    }

    if (!$application && !empty($charge['team_member_id'])) {
        $teamMember = fetchTeamMember($supabase, $charge['team_member_id']);
        if ($teamMember) {
            $team = fetchTeamApplicationById($supabase, $teamMember['team_application_id']);
            if ($team) {
                $application = fetchApplication($supabase, $team['application_id']);
            }
        }
    } elseif ($application && $application['participation_type'] === 'team') {
        $team = fetchTeamApplication($supabase, $application['id']);
    }

    if (!$application) {
        throw new Exception('申込情報を取得できません');
    }

    if ($application['participation_type'] === 'individual') {
        $individual = fetchIndividualApplication($supabase, $application['id']);
        $studentEmail = $individual['student_email'] ?? null;
        $guardianEmail = $individual['guardian_email'] ?? null;
        $guardianName = $individual['guardian_name'] ?? '';
        $participantName = $individual['student_name'] ?? '';
        $receiptEmail = !empty($studentEmail) ? $studentEmail : $guardianEmail;
        $description = sprintf(
            'Cambridge Exam 参加費（個人） - 申込番号:%s - 生徒:%s',
            $application['application_number'],
            $participantName
        );
    } else {
        if (!$team) {
            $team = fetchTeamApplication($supabase, $application['id']);
        }

        $guardianName = $team['guardian_name'] ?? '';
        $participantName = $teamMember ? ($teamMember['member_name'] ?? '') : ($team['team_name'] ?? '');
        $receiptEmail = $teamMember['member_email'] ?? ($team['guardian_email'] ?? null);

        if ($teamMember) {
            $description = sprintf(
                'Cambridge Exam 参加費（チーム/メンバー%02d） - 申込番号:%s - メンバー:%s',
                $teamMember['member_number'] ?? 0,
                $application['application_number'],
                $teamMember['member_name'] ?? ''
            );
        } else {
            $description = sprintf(
                'Cambridge Exam 参加費（チーム） - 申込番号:%s - チーム:%s',
                $application['application_number'],
                $team['team_name'] ?? ''
            );
        }
    }

    return [
        'application' => $application,
        'individual' => $individual,
        'team' => $team,
        'team_member' => $teamMember,
        'guardian_name' => $guardianName ?: '保護者様',
        'participant_name' => $participantName ?: ($application['participation_type'] === 'team' ? ($team['team_name'] ?? '参加者') : '参加者'),
        'receipt_email' => $receiptEmail,
        'description' => $description
    ];
}

function fetchApplication(SupabaseClient $supabase, string $applicationId): array
{
    $result = $supabase->from('applications')
        ->select('*')
        ->eq('id', $applicationId)
        ->single();

    if (!$result['success'] || empty($result['data'])) {
        throw new Exception('applicationsテーブルから申込を取得できません');
    }

    return $result['data'];
}

function fetchIndividualApplication(SupabaseClient $supabase, string $applicationId): ?array
{
    $result = $supabase->from('individual_applications')
        ->select('*')
        ->eq('application_id', $applicationId)
        ->single();

    return $result['success'] ? ($result['data'] ?? null) : null;
}

function fetchTeamApplication(SupabaseClient $supabase, string $applicationId): ?array
{
    $result = $supabase->from('team_applications')
        ->select('*')
        ->eq('application_id', $applicationId)
        ->single();

    return $result['success'] ? ($result['data'] ?? null) : null;
}

function fetchTeamApplicationById(SupabaseClient $supabase, string $teamApplicationId): ?array
{
    $result = $supabase->from('team_applications')
        ->select('*')
        ->eq('id', $teamApplicationId)
        ->single();

    return $result['success'] ? ($result['data'] ?? null) : null;
}

function fetchTeamMember(SupabaseClient $supabase, string $teamMemberId): ?array
{
    $result = $supabase->from('team_members')
        ->select('*')
        ->eq('id', $teamMemberId)
        ->single();

    return $result['success'] ? ($result['data'] ?? null) : null;
}

/**
 * 決済完了メール
 */
function sendPaymentConfirmationEmail(EmailTemplateService $service, array $charge, array $context): void
{
    $application = $context['application'];
    $teamMember = $context['team_member'] ?? null;

    $variables = [
        'guardian_name' => $context['guardian_name'],
        'participant_name' => $context['participant_name'],
        'application_number' => $application['application_number'] ?? '',
        'amount' => number_format((int)($charge['amount'] ?? 0)),
        'payment_date' => date('Y-m-d H:i'),
        'exam_date' => '後日ご案内いたします'
    ];

    $options = [
        'recipient_options' => array_filter([
            'team_member_id' => $teamMember['id'] ?? null
        ])
    ];

    try {
        $service->sendTemplateToApplication('payment_confirmation', $application['id'], $variables, $options);
    } catch (Exception $e) {
        error_log('[run-scheduled-charges] payment_confirmation送信に失敗: ' . $e->getMessage());
    }
}

