-- =============================================
-- Create Default Admin Account
-- デフォルト管理者アカウントの作成
-- =============================================

-- デフォルト管理者アカウントを作成
-- メールアドレス: admin@example.com
-- ユーザー名: admin
-- パスワード: admin123
-- ※本番環境では必ずパスワードを変更してください！

INSERT INTO admin_users (username, email, password_hash, role, is_active)
VALUES (
    'admin',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'super_admin',
    TRUE
)
ON CONFLICT (username) DO UPDATE SET
    email = EXCLUDED.email,
    password_hash = EXCLUDED.password_hash,
    role = EXCLUDED.role,
    is_active = EXCLUDED.is_active,
    updated_at = CURRENT_TIMESTAMP;

-- 確認
SELECT id, username, email, role, is_active, created_at
FROM admin_users
WHERE email = 'admin@example.com';

-- =============================================
-- パスワード変更方法
-- =============================================
-- 
-- 1. PHPでパスワードハッシュを生成:
--    php -r "echo password_hash('your_new_password', PASSWORD_BCRYPT);"
-- 
-- 2. 生成されたハッシュでUPDATE:
--    UPDATE admin_users 
--    SET password_hash = '$2y$10$...' 
--    WHERE email = 'admin@example.com';
-- 
-- =============================================

