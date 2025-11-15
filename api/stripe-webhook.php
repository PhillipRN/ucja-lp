<?php
/**
 * Stripe Webhook Handler
 * Stripeからのイベントを受信して処理
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/SupabaseClient.php';
require_once __DIR__ . '/../vendor/autoload.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Webhookのペイロードを取得
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    // Webhookの署名を検証
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        STRIPE_WEBHOOK_SECRET
    );
    
    $supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_SERVICE_KEY);
    
    // イベントタイプによって処理を分岐
    switch ($event->type) {
        case 'payment_intent.succeeded':
            handlePaymentSuccess($event->data->object, $supabase);
            break;
            
        case 'payment_intent.payment_failed':
            handlePaymentFailed($event->data->object, $supabase);
            break;
            
        case 'payment_intent.canceled':
            handlePaymentCanceled($event->data->object, $supabase);
            break;
            
        case 'setup_intent.succeeded':
            handleSetupIntentSuccess($event->data->object, $supabase);
            break;
            
        case 'setup_intent.setup_failed':
            handleSetupIntentFailed($event->data->object, $supabase);
            break;
            
        case 'charge.refunded':
            handleRefund($event->data->object, $supabase);
            break;
            
        default:
            // 未処理のイベントタイプ
            error_log('Unhandled Stripe event type: ' . $event->type);
    }
    
    http_response_code(200);
    echo json_encode(['received' => true]);
    
} catch (\UnexpectedValueException $e) {
    // 署名検証失敗
    http_response_code(400);
    error_log('Stripe Webhook signature verification failed: ' . $e->getMessage());
    exit;
    
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // 署名検証失敗
    http_response_code(400);
    error_log('Stripe Webhook signature verification failed: ' . $e->getMessage());
    exit;
    
} catch (Exception $e) {
    // その他のエラー
    http_response_code(500);
    error_log('Stripe Webhook error: ' . $e->getMessage());
    exit;
}

/**
 * 決済成功時の処理
 */
function handlePaymentSuccess($paymentIntent, $supabase) {
    $applicationId = $paymentIntent->metadata->application_id ?? null;
    
    if (!$applicationId) {
        error_log('Payment succeeded but no application_id in metadata');
        return;
    }
    
    try {
        // Applicationステータスを更新
        $supabase->update('applications', [
            'payment_status' => 'completed',
            'application_status' => 'confirmed',
            'paid_at' => date('c')
        ], [
            'id' => 'eq.' . $applicationId
        ]);
        
        // Payment Transaction を更新
        $supabase->update('payment_transactions', [
            'status' => 'succeeded',
            'stripe_charge_id' => $paymentIntent->latest_charge ?? null
        ], [
            'stripe_payment_intent_id' => 'eq.' . $paymentIntent->id
        ]);
        
        // 決済完了メールの送信ログを記録
        $applicationResult = $supabase->from('applications')
            ->select('participation_type')
            ->eq('id', $applicationId)
            ->single();
        
        if ($applicationResult['success']) {
            $application = $applicationResult['data'];
            $participationType = $application['participation_type'];
            
            // メールアドレスを取得
            $email = '';
            if ($participationType === 'individual') {
                $individualResult = $supabase->from('individual_applications')
                    ->select('guardian_email')
                    ->eq('application_id', $applicationId)
                    ->single();
                
                if ($individualResult['success']) {
                    $email = $individualResult['data']['guardian_email'];
                }
            } else {
                $teamResult = $supabase->from('team_applications')
                    ->select('guardian_email')
                    ->eq('application_id', $applicationId)
                    ->single();
                
                if ($teamResult['success']) {
                    $email = $teamResult['data']['guardian_email'];
                }
            }
            
            if ($email) {
                $emailLogData = [
                    'application_id' => $applicationId,
                    'email_type' => 'payment_confirmation',
                    'recipient_email' => $email,
                    'subject' => '【Cambridge Exam】決済完了のお知らせ',
                    'status' => 'pending'
                ];
                
                $supabase->insert('email_logs', $emailLogData);
            }
        }
        
        error_log('Payment succeeded for application: ' . $applicationId);
        
    } catch (Exception $e) {
        error_log('Error updating payment status: ' . $e->getMessage());
    }
}

/**
 * 決済失敗時の処理
 */
function handlePaymentFailed($paymentIntent, $supabase) {
    $applicationId = $paymentIntent->metadata->application_id ?? null;
    
    if (!$applicationId) {
        return;
    }
    
    try {
        $supabase->update('applications', [
            'payment_status' => 'failed'
        ], [
            'id' => 'eq.' . $applicationId
        ]);
        
        $supabase->update('payment_transactions', [
            'status' => 'failed',
            'error_message' => $paymentIntent->last_payment_error->message ?? 'Payment failed'
        ], [
            'stripe_payment_intent_id' => 'eq.' . $paymentIntent->id
        ]);
        
        error_log('Payment failed for application: ' . $applicationId);
        
    } catch (Exception $e) {
        error_log('Error updating failed payment status: ' . $e->getMessage());
    }
}

/**
 * 決済キャンセル時の処理
 */
function handlePaymentCanceled($paymentIntent, $supabase) {
    $applicationId = $paymentIntent->metadata->application_id ?? null;
    
    if (!$applicationId) {
        return;
    }
    
    try {
        $supabase->update('payment_transactions', [
            'status' => 'cancelled'
        ], [
            'stripe_payment_intent_id' => 'eq.' . $paymentIntent->id
        ]);
        
        error_log('Payment canceled for application: ' . $applicationId);
        
    } catch (Exception $e) {
        error_log('Error updating canceled payment status: ' . $e->getMessage());
    }
}

/**
 * SetupIntent成功時の処理
 */
function handleSetupIntentSuccess($setupIntent, $supabase) {
    $applicationId = $setupIntent->metadata->application_id ?? null;
    
    if (!$applicationId) {
        error_log('SetupIntent succeeded but no application_id in metadata');
        return;
    }
    
    try {
        // Applicationステータスを更新
        $supabase->update('applications', [
            'stripe_setup_intent_id' => $setupIntent->id,
            'stripe_payment_method_id' => $setupIntent->payment_method,
            'card_registered' => true,
            'card_registered_at' => date('Y-m-d H:i:s'),
            'payment_status' => 'card_registered',
            'application_status' => 'kyc_pending'
        ], [
            'id' => 'eq.' . $applicationId
        ]);
        
        error_log('SetupIntent succeeded for application: ' . $applicationId);
        
    } catch (Exception $e) {
        error_log('Error updating SetupIntent status: ' . $e->getMessage());
    }
}

/**
 * SetupIntent失敗時の処理
 */
function handleSetupIntentFailed($setupIntent, $supabase) {
    $applicationId = $setupIntent->metadata->application_id ?? null;
    
    if (!$applicationId) {
        return;
    }
    
    try {
        $supabase->update('applications', [
            'card_registered' => false,
            'application_status' => 'card_pending'
        ], [
            'id' => 'eq.' . $applicationId
        ]);
        
        error_log('SetupIntent failed for application: ' . $applicationId);
        
    } catch (Exception $e) {
        error_log('Error updating failed SetupIntent status: ' . $e->getMessage());
    }
}

/**
 * 返金処理
 */
function handleRefund($charge, $supabase) {
    try {
        // Payment Intent IDからApplicationを特定
        $paymentIntentId = $charge->payment_intent;
        
        $applicationResult = $supabase->from('applications')
            ->select('id')
            ->eq('stripe_payment_intent_id', $paymentIntentId)
            ->single();
        
        if (!$applicationResult['success']) {
            return;
        }
        
        $applicationId = $applicationResult['data']['id'];
        
        // 返金額を計算
        $refundAmount = 0;
        foreach ($charge->refunds->data as $refund) {
            $refundAmount += $refund->amount;
        }
        
        // 全額返金の場合はステータスを更新
        if ($refundAmount >= $charge->amount) {
            $supabase->update('applications', [
                'payment_status' => 'refunded'
            ], [
                'id' => 'eq.' . $applicationId
            ]);
        }
        
        // 返金トランザクションを記録
        $refundData = [
            'application_id' => $applicationId,
            'transaction_type' => 'refund',
            'amount' => $refundAmount,
            'currency' => 'JPY',
            'stripe_payment_intent_id' => $paymentIntentId,
            'stripe_charge_id' => $charge->id,
            'status' => 'succeeded'
        ];
        
        $supabase->insert('payment_transactions', $refundData);
        
        error_log('Refund processed for application: ' . $applicationId);
        
    } catch (Exception $e) {
        error_log('Error processing refund: ' . $e->getMessage());
    }
}

