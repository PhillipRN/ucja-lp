<?php
/**
 * SendGrid Email Test Script
 * メール送信テスト用スクリプト
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/EmailService.php';

echo "==============================================\n";
echo "SendGrid Email Test\n";
echo "==============================================\n\n";

try {
    $emailService = new EmailService();
    
    // テスト用メールアドレス（ここに実際のメールアドレスを入れてください）
    $testEmail = 'phillip.bksp@gmail.com'; // TODO: テスト用メールアドレスに変更
    
    echo "Sending test email to: {$testEmail}\n\n";
    
    // シンプルなメール送信テスト
    echo "Test 1: Simple Email\n";
    echo "--------------------\n";
    
    $result1 = $emailService->sendEmail(
        $testEmail,
        '【テスト】SendGrid 接続テスト',
        '<html>
            <body style="font-family: sans-serif; padding: 20px;">
                <h1 style="color: #007bff;">🎉 SendGrid接続成功！</h1>
                <p>このメールが届いたら、SendGridの設定は正常に動作しています。</p>
                <div style="background-color: #f0f0f0; padding: 15px; margin: 20px 0; border-left: 4px solid #6BBBAE;">
                    <strong>設定情報:</strong><br>
                    From: ' . SENDGRID_FROM_EMAIL . '<br>
                    Name: ' . SENDGRID_FROM_NAME . '
                </div>
                <p style="color: #666;">送信日時: ' . date('Y-m-d H:i:s') . '</p>
            </body>
        </html>',
        'SendGrid接続テスト - このメールが届いたら成功です！'
    );
    
    if ($result1['success']) {
        echo "✅ Success! Status code: " . $result1['status_code'] . "\n";
        echo "   Message ID: " . ($result1['headers']['X-Message-Id'] ?? 'N/A') . "\n\n";
    } else {
        echo "❌ Failed!\n";
        echo "   Error: " . ($result1['error'] ?? 'Unknown error') . "\n\n";
    }
    
    echo "==============================================\n";
    echo "Test 2: HTML Rich Email\n";
    echo "--------------------\n";
    
    $result2 = $emailService->sendEmail(
        $testEmail,
        '【テスト】HTMLメール送信テスト',
        '<!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: -apple-system, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5; }
                .content { background-color: #ffffff; border-radius: 10px; padding: 30px; }
                .button { display: inline-block; background-color: #6BBBAE; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; }
                .info-box { background-color: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="content">
                    <h1 style="color: #007bff;">📧 HTMLメールテスト</h1>
                    <p>このメールはHTMLフォーマットで送信されています。</p>
                    
                    <div class="info-box">
                        <strong>✅ 確認項目:</strong><br>
                        • CSSスタイルが適用されている<br>
                        • 絵文字が表示されている<br>
                        • 日本語が正しく表示されている<br>
                        • ボタンがクリックできる
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        <a href="https://challenge.univ-cambridge-japan.academy" class="button">
                            公式サイトへ
                        </a>
                    </div>
                    
                    <p style="font-size: 12px; color: #666; border-top: 1px solid #e0e0e0; padding-top: 15px; margin-top: 20px;">
                        UCJA事務局<br>
                        お問い合わせ: contact@univ-cambridge-japan.academy
                    </p>
                </div>
            </div>
        </body>
        </html>'
    );
    
    if ($result2['success']) {
        echo "✅ Success! Status code: " . $result2['status_code'] . "\n";
        echo "   Message ID: " . ($result2['headers']['X-Message-Id'] ?? 'N/A') . "\n\n";
    } else {
        echo "❌ Failed!\n";
        echo "   Error: " . ($result2['error'] ?? 'Unknown error') . "\n\n";
    }
    
    echo "==============================================\n";
    echo "✨ All tests completed!\n";
    echo "==============================================\n\n";
    
    echo "次のステップ:\n";
    echo "1. メールボックスを確認してください\n";
    echo "2. 迷惑メールフォルダもチェックしてください\n";
    echo "3. SendGrid Dashboard で Activity を確認できます\n";
    echo "   https://app.sendgrid.com/email_activity\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nスタックトレース:\n";
    echo $e->getTraceAsString() . "\n";
}

