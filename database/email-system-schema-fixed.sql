-- =============================================
-- Email System Tables
-- メール送信システム用テーブル
-- 既存テーブルがある場合は削除してから作成
-- =============================================

-- 既存のテーブルを削除（依存関係の順序に注意）
DROP TABLE IF EXISTS admin_activity_logs CASCADE;
DROP TABLE IF EXISTS email_logs CASCADE;
DROP TABLE IF EXISTS email_batches CASCADE;
DROP TABLE IF EXISTS email_templates CASCADE;
DROP TABLE IF EXISTS admin_users CASCADE;

-- =============================================
-- メール一斉送信バッチ管理テーブル（先に作成）
-- =============================================
CREATE TABLE email_batches (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- バッチ情報
    batch_name VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    template_id VARCHAR(100),
    
    -- 送信設定
    recipient_type VARCHAR(50), -- all, individual, team, specific_status
    recipient_filter JSONB, -- フィルター条件（JSON形式）
    
    -- 統計情報
    total_recipients INTEGER DEFAULT 0,
    sent_count INTEGER DEFAULT 0,
    failed_count INTEGER DEFAULT 0,
    delivered_count INTEGER DEFAULT 0,
    opened_count INTEGER DEFAULT 0,
    clicked_count INTEGER DEFAULT 0,
    
    -- ステータス
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN (
        'pending',    -- 送信待ち
        'processing', -- 送信中
        'completed',  -- 完了
        'failed'      -- 失敗
    )),
    
    -- 作成者情報
    created_by VARCHAR(255),
    
    -- タイムスタンプ
    scheduled_at TIMESTAMP WITH TIME ZONE, -- 送信予定日時
    started_at TIMESTAMP WITH TIME ZONE,
    completed_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- インデックス
CREATE INDEX idx_email_batches_status ON email_batches(status);
CREATE INDEX idx_email_batches_created_at ON email_batches(created_at DESC);

-- =============================================
-- メール送信ログテーブル
-- =============================================
CREATE TABLE email_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- 送信情報
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(100),
    subject VARCHAR(255),
    template_id VARCHAR(100), -- SendGrid Template ID
    
    -- ステータス
    status VARCHAR(20) DEFAULT 'sent' CHECK (status IN (
        'sent',      -- 送信完了
        'failed',    -- 送信失敗
        'delivered', -- 配信完了
        'opened',    -- 開封
        'clicked',   -- リンククリック
        'bounced',   -- バウンス（届かなかった）
        'spam'       -- スパム報告
    )),
    
    -- SendGrid情報
    sendgrid_message_id VARCHAR(255),
    sendgrid_response TEXT,
    
    -- エラー情報
    error_message TEXT,
    
    -- 関連情報
    application_id UUID REFERENCES applications(id) ON DELETE SET NULL,
    batch_id UUID REFERENCES email_batches(id) ON DELETE SET NULL, -- バッチ送信の場合のバッチID
    
    -- タイムスタンプ
    sent_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP WITH TIME ZONE,
    opened_at TIMESTAMP WITH TIME ZONE,
    clicked_at TIMESTAMP WITH TIME ZONE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- インデックス
CREATE INDEX idx_email_logs_recipient ON email_logs(recipient_email);
CREATE INDEX idx_email_logs_status ON email_logs(status);
CREATE INDEX idx_email_logs_batch ON email_logs(batch_id);
CREATE INDEX idx_email_logs_application ON email_logs(application_id);
CREATE INDEX idx_email_logs_sent_at ON email_logs(sent_at DESC);

-- =============================================
-- メールテンプレート管理テーブル（ローカル管理用）
-- =============================================
CREATE TABLE email_templates (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- テンプレート情報
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sendgrid_template_id VARCHAR(100) UNIQUE, -- SendGrid Dynamic Template ID
    
    -- 送信設定
    recipient_type VARCHAR(30) DEFAULT 'guardian',

    -- テンプレート内容（プレビュー用）
    subject VARCHAR(255),
    preview_html TEXT,
    
    -- 変数定義（JSON形式）
    variables JSONB,
    
    -- カテゴリー
    category VARCHAR(50), -- automatic, manual, notification
    
    -- ステータス
    is_active BOOLEAN DEFAULT TRUE,
    
    -- タイムスタンプ
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- インデックス
CREATE INDEX idx_email_templates_category ON email_templates(category);
CREATE INDEX idx_email_templates_active ON email_templates(is_active);

-- =============================================
-- 管理者アカウントテーブル
-- =============================================
CREATE TABLE admin_users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- 認証情報
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    
    -- 権限
    role VARCHAR(20) DEFAULT 'admin' CHECK (role IN ('admin', 'super_admin', 'viewer')),
    
    -- ステータス
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP WITH TIME ZONE,
    
    -- タイムスタンプ
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- インデックス
CREATE INDEX idx_admin_users_email ON admin_users(email);
CREATE INDEX idx_admin_users_active ON admin_users(is_active);

-- =============================================
-- 管理者アクティビティログ
-- =============================================
CREATE TABLE admin_activity_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    admin_id UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- アクション情報
    action VARCHAR(100) NOT NULL, -- login, send_email, export_data, etc.
    description TEXT,
    
    -- 詳細情報（JSON形式）
    details JSONB,
    
    -- IPアドレス
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    -- タイムスタンプ
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- インデックス
CREATE INDEX idx_admin_activity_admin ON admin_activity_logs(admin_id);
CREATE INDEX idx_admin_activity_action ON admin_activity_logs(action);
CREATE INDEX idx_admin_activity_created ON admin_activity_logs(created_at DESC);

-- =============================================
-- Triggers for updated_at
-- =============================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- email_logs
DROP TRIGGER IF EXISTS update_email_logs_updated_at ON email_logs;
CREATE TRIGGER update_email_logs_updated_at BEFORE UPDATE ON email_logs
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- email_batches
DROP TRIGGER IF EXISTS update_email_batches_updated_at ON email_batches;
CREATE TRIGGER update_email_batches_updated_at BEFORE UPDATE ON email_batches
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- email_templates
DROP TRIGGER IF EXISTS update_email_templates_updated_at ON email_templates;
CREATE TRIGGER update_email_templates_updated_at BEFORE UPDATE ON email_templates
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- admin_users
DROP TRIGGER IF EXISTS update_admin_users_updated_at ON admin_users;
CREATE TRIGGER update_admin_users_updated_at BEFORE UPDATE ON admin_users
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =============================================
-- コメント
-- =============================================
COMMENT ON TABLE email_logs IS 'メール送信ログ - すべてのメール送信記録を保存';
COMMENT ON TABLE email_batches IS 'メール一斉送信バッチ管理';
COMMENT ON TABLE email_templates IS 'メールテンプレート管理（ローカル）';
COMMENT ON TABLE admin_users IS '管理者アカウント';
COMMENT ON TABLE admin_activity_logs IS '管理者アクティビティログ';

-- =============================================
-- 初期データ挿入（オプション）
-- =============================================

-- デフォルトの管理者アカウント（パスワード: admin123 - 本番環境では必ず変更してください）
INSERT INTO admin_users (username, email, password_hash, role, is_active)
VALUES (
    'admin',
    'admin@univ-cambridge-japan.academy',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "admin123"
    'super_admin',
    TRUE
);

-- 完了メッセージ
DO $$
BEGIN
    RAISE NOTICE '==============================================';
    RAISE NOTICE 'Email System Tables created successfully!';
    RAISE NOTICE '==============================================';
    RAISE NOTICE 'Created tables:';
    RAISE NOTICE '  - email_batches';
    RAISE NOTICE '  - email_logs';
    RAISE NOTICE '  - email_templates';
    RAISE NOTICE '  - admin_users';
    RAISE NOTICE '  - admin_activity_logs';
    RAISE NOTICE '==============================================';
    RAISE NOTICE 'Default admin account created:';
    RAISE NOTICE '  Username: admin';
    RAISE NOTICE '  Password: admin123';
    RAISE NOTICE '  ⚠️  IMPORTANT: Change this password immediately!';
    RAISE NOTICE '==============================================';
END $$;

