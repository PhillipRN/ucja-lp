<?php
/**
 * Cambridge Exam Application System
 * Configuration File (Example)
 * 
 * コピーして config.php として使用してください
 * cp config.example.php config.php
 */

// =============================================
// Database Configuration (Supabase)
// =============================================
define('SUPABASE_URL', 'https://your-project.supabase.co');
define('SUPABASE_ANON_KEY', 'your-anon-key-here');
define('SUPABASE_SERVICE_KEY', 'your-service-role-key-here'); // バックエンド処理用

// =============================================
// Stripe Configuration
// =============================================
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_publishable_key');
define('STRIPE_SECRET_KEY', 'sk_test_your_secret_key');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_webhook_secret');

// =============================================
// Application Settings
// =============================================
define('APP_NAME', 'Cambridge Exam Application');
define('APP_URL', 'http://localhost:8000'); // 本番環境では変更
define('APP_ENV', 'development'); // development, production

// =============================================
// Email Settings (SendGrid)
// =============================================
define('SENDGRID_API_KEY', 'SG.your-sendgrid-api-key-here');
define('SENDGRID_FROM_EMAIL', 'noreply@univ-cambridge-japan.academy');
define('SENDGRID_FROM_NAME', 'UCJA事務局');
define('EMAIL_SANDBOX_MODE', true); // 開発・検証時は true、本番は false
define('EMAIL_SANDBOX_RECIPIENT', 'dev-team@example.com'); // サンドボックス送信先
define('EMAIL_AUTO_GENERATE_HTML_FROM_TEXT', true); // テキスト版からHTMLを自動生成

// SendGrid Dynamic Templates ID
define('TEMPLATE_APPLICATION_COMPLETE', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // 申込完了
define('TEMPLATE_KYC_COMPLETE', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // 本人確認完了
define('TEMPLATE_PAYMENT_COMPLETE', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // 決済完了
define('TEMPLATE_EXAM_NOTIFICATION', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // 試験案内
define('TEMPLATE_TEAM_PAYMENT_REQUEST', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // チームメンバー支払いリンク
define('TEMPLATE_GENERAL_NOTICE', 'd-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // 一般お知らせ

// =============================================
// Security Settings
// =============================================
define('SESSION_LIFETIME', 7200); // 2時間（秒）
define('PASSWORD_MIN_LENGTH', 8);
define('JWT_SECRET', 'your-jwt-secret-key-change-this'); // JWTトークン用

// =============================================
// Pricing Configuration
// =============================================
define('EARLY_BIRD_PRICE', 8800); // 早割価格（円）
define('REGULAR_PRICE', 22000); // 通常価格（円）
define('EARLY_BIRD_DEADLINE', '2025-12-15 23:59:59'); // 早割締切
define('REGULAR_DEADLINE', '2026-01-01 23:59:59'); // 通常締切

// =============================================
// KYC Settings
// =============================================
define('KYC_PROVIDER', 'liquid'); // 使用するKYCプロバイダー
define('KYC_API_KEY', 'your-kyc-api-key');
define('KYC_API_SECRET', 'your-kyc-api-secret');

// =============================================
// File Upload Settings
// =============================================
define('UPLOAD_MAX_SIZE', 10485760); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// =============================================
// Error Reporting
// =============================================
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
}

// =============================================
// Timezone
// =============================================
date_default_timezone_set('Asia/Tokyo');

// =============================================
// CORS Settings (必要に応じて)
// =============================================
define('CORS_ALLOWED_ORIGINS', [
    'http://localhost:8000',
    'https://your-production-domain.com'
]);

// =============================================
// Rate Limiting
// =============================================
define('RATE_LIMIT_MAX_REQUESTS', 100); // 1時間あたりの最大リクエスト数
define('RATE_LIMIT_WINDOW', 3600); // 1時間（秒）

