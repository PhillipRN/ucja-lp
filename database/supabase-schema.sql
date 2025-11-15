-- =============================================
-- Cambridge Exam Application System
-- Supabase Database Schema
-- =============================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =============================================
-- Users Table (ユーザーアカウント)
-- =============================================
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    user_type VARCHAR(20) NOT NULL CHECK (user_type IN ('student', 'guardian', 'admin')),
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP WITH TIME ZONE
);

-- =============================================
-- Applications Table (申込情報)
-- =============================================
CREATE TABLE applications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_number VARCHAR(50) UNIQUE NOT NULL, -- 申込番号（例：APP-2025-00001）
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    
    -- 参加形式
    participation_type VARCHAR(20) NOT NULL CHECK (participation_type IN ('individual', 'team')),
    
    -- 料金プラン
    pricing_type VARCHAR(50) NOT NULL,
    amount INTEGER NOT NULL,
    
    -- 支払いステータス
    payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'completed', 'failed', 'refunded')),
    payment_method VARCHAR(50) DEFAULT 'stripe',
    stripe_payment_intent_id VARCHAR(255),
    paid_at TIMESTAMP WITH TIME ZONE,
    
    -- KYC（本人確認）ステータス
    kyc_status VARCHAR(20) DEFAULT 'pending' CHECK (kyc_status IN ('pending', 'in_progress', 'completed', 'failed')),
    kyc_verified_at TIMESTAMP WITH TIME ZONE,
    
    -- 申込ステータス
    application_status VARCHAR(20) DEFAULT 'draft' CHECK (application_status IN ('draft', 'submitted', 'payment_pending', 'confirmed', 'cancelled')),
    
    -- 特記事項
    special_requests TEXT,
    
    -- メタデータ
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP WITH TIME ZONE,
    
    -- 管理用
    admin_notes TEXT
);

-- =============================================
-- Individual Applications (個人戦詳細)
-- =============================================
CREATE TABLE individual_applications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID UNIQUE NOT NULL REFERENCES applications(id) ON DELETE CASCADE,
    
    -- 生徒情報
    student_name VARCHAR(100) NOT NULL,
    school VARCHAR(200) NOT NULL,
    grade VARCHAR(50) NOT NULL,
    student_email VARCHAR(255) NOT NULL,
    student_phone VARCHAR(50),
    
    -- 保護者情報
    guardian_name VARCHAR(100) NOT NULL,
    guardian_email VARCHAR(255) NOT NULL,
    guardian_phone VARCHAR(50) NOT NULL,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Team Applications (チーム戦詳細)
-- =============================================
CREATE TABLE team_applications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID UNIQUE NOT NULL REFERENCES applications(id) ON DELETE CASCADE,
    
    -- チーム情報
    team_name VARCHAR(100) NOT NULL,
    school VARCHAR(200) NOT NULL,
    
    -- 代表者（保護者）情報
    guardian_name VARCHAR(100) NOT NULL,
    guardian_email VARCHAR(255) NOT NULL,
    guardian_phone VARCHAR(50) NOT NULL,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Team Members (チームメンバー)
-- =============================================
CREATE TABLE team_members (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    team_application_id UUID NOT NULL REFERENCES team_applications(id) ON DELETE CASCADE,
    
    member_number INTEGER NOT NULL CHECK (member_number BETWEEN 1 AND 5),
    member_name VARCHAR(100) NOT NULL,
    member_email VARCHAR(255) NOT NULL,
    is_representative BOOLEAN DEFAULT FALSE, -- メンバー1は代表者
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(team_application_id, member_number)
);

-- =============================================
-- Exam Results (試験結果)
-- =============================================
CREATE TABLE exam_results (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID UNIQUE NOT NULL REFERENCES applications(id) ON DELETE CASCADE,
    
    -- スコア
    total_score INTEGER,
    rank INTEGER,
    percentile DECIMAL(5,2),
    
    -- 各セクションスコア（必要に応じて追加）
    listening_score INTEGER,
    reading_score INTEGER,
    writing_score INTEGER,
    speaking_score INTEGER,
    
    -- 賞・研修プログラム
    prize_eligible BOOLEAN DEFAULT FALSE,
    cambridge_program_eligible BOOLEAN DEFAULT FALSE,
    
    -- 結果公開
    results_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP WITH TIME ZONE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Payment Transactions (決済トランザクション履歴)
-- =============================================
CREATE TABLE payment_transactions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID NOT NULL REFERENCES applications(id) ON DELETE CASCADE,
    
    transaction_type VARCHAR(20) NOT NULL CHECK (transaction_type IN ('payment', 'refund')),
    amount INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'JPY',
    
    -- Stripe情報
    stripe_payment_intent_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),
    stripe_refund_id VARCHAR(255),
    
    status VARCHAR(20) NOT NULL CHECK (status IN ('pending', 'succeeded', 'failed', 'cancelled')),
    
    error_message TEXT,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- User Sessions (ユーザーセッション管理)
-- =============================================
CREATE TABLE user_sessions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address INET,
    user_agent TEXT,
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Email Logs (メール送信ログ)
-- =============================================
CREATE TABLE email_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID REFERENCES applications(id) ON DELETE SET NULL,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    
    email_type VARCHAR(50) NOT NULL, -- 'application_confirmation', 'payment_confirmation', 'kyc_reminder', etc.
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    
    status VARCHAR(20) NOT NULL CHECK (status IN ('pending', 'sent', 'failed', 'bounced')),
    sent_at TIMESTAMP WITH TIME ZONE,
    
    error_message TEXT,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Indexes for Performance
-- =============================================
CREATE INDEX idx_applications_user_id ON applications(user_id);
CREATE INDEX idx_applications_application_number ON applications(application_number);
CREATE INDEX idx_applications_payment_status ON applications(payment_status);
CREATE INDEX idx_applications_application_status ON applications(application_status);
CREATE INDEX idx_applications_created_at ON applications(created_at);

CREATE INDEX idx_individual_applications_student_email ON individual_applications(student_email);
CREATE INDEX idx_individual_applications_guardian_email ON individual_applications(guardian_email);

CREATE INDEX idx_team_members_team_application_id ON team_members(team_application_id);
CREATE INDEX idx_team_members_member_email ON team_members(member_email);

CREATE INDEX idx_payment_transactions_application_id ON payment_transactions(application_id);
CREATE INDEX idx_payment_transactions_stripe_payment_intent_id ON payment_transactions(stripe_payment_intent_id);

CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_session_token ON user_sessions(session_token);
CREATE INDEX idx_user_sessions_expires_at ON user_sessions(expires_at);

-- =============================================
-- Row Level Security (RLS) Policies
-- =============================================

-- Enable RLS
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE applications ENABLE ROW LEVEL SECURITY;
ALTER TABLE individual_applications ENABLE ROW LEVEL SECURITY;
ALTER TABLE team_applications ENABLE ROW LEVEL SECURITY;
ALTER TABLE team_members ENABLE ROW LEVEL SECURITY;
ALTER TABLE exam_results ENABLE ROW LEVEL SECURITY;
ALTER TABLE payment_transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_sessions ENABLE ROW LEVEL SECURITY;

-- Users: ユーザーは自分の情報のみ閲覧・更新可能
CREATE POLICY "Users can view own data" ON users
    FOR SELECT USING (auth.uid() = id);

CREATE POLICY "Users can update own data" ON users
    FOR UPDATE USING (auth.uid() = id);

-- Applications: ユーザーは自分の申込のみ閲覧可能
CREATE POLICY "Users can view own applications" ON applications
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can create applications" ON applications
    FOR INSERT WITH CHECK (auth.uid() = user_id);

-- Individual Applications: 関連する申込の所有者のみ閲覧可能
CREATE POLICY "Users can view own individual applications" ON individual_applications
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM applications 
            WHERE applications.id = individual_applications.application_id 
            AND applications.user_id = auth.uid()
        )
    );

-- Team Applications: 関連する申込の所有者のみ閲覧可能
CREATE POLICY "Users can view own team applications" ON team_applications
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM applications 
            WHERE applications.id = team_applications.application_id 
            AND applications.user_id = auth.uid()
        )
    );

-- Exam Results: ユーザーは自分の結果のみ閲覧可能（公開後）
CREATE POLICY "Users can view own exam results" ON exam_results
    FOR SELECT USING (
        results_published = TRUE AND
        EXISTS (
            SELECT 1 FROM applications 
            WHERE applications.id = exam_results.application_id 
            AND applications.user_id = auth.uid()
        )
    );

-- =============================================
-- Functions and Triggers
-- =============================================

-- 更新日時を自動更新するトリガー関数
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 各テーブルにトリガーを設定
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_applications_updated_at BEFORE UPDATE ON applications
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_individual_applications_updated_at BEFORE UPDATE ON individual_applications
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_team_applications_updated_at BEFORE UPDATE ON team_applications
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_exam_results_updated_at BEFORE UPDATE ON exam_results
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- 申込番号を自動生成する関数
CREATE OR REPLACE FUNCTION generate_application_number()
RETURNS TRIGGER AS $$
DECLARE
    year_str VARCHAR(4);
    seq_num INTEGER;
    new_number VARCHAR(50);
BEGIN
    year_str := TO_CHAR(CURRENT_DATE, 'YYYY');
    
    -- 今年度の最大番号を取得
    SELECT COALESCE(MAX(
        CAST(
            SUBSTRING(application_number FROM '[0-9]+$') AS INTEGER
        )
    ), 0) + 1 INTO seq_num
    FROM applications
    WHERE application_number LIKE 'APP-' || year_str || '-%';
    
    -- 新しい申込番号を生成（APP-2025-00001形式）
    new_number := 'APP-' || year_str || '-' || LPAD(seq_num::TEXT, 5, '0');
    NEW.application_number := new_number;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 申込番号自動生成トリガー
CREATE TRIGGER generate_application_number_trigger
    BEFORE INSERT ON applications
    FOR EACH ROW
    WHEN (NEW.application_number IS NULL)
    EXECUTE FUNCTION generate_application_number();

-- =============================================
-- Initial Data (Optional)
-- =============================================

-- 管理者アカウントの作成（パスワードは後で変更してください）
-- INSERT INTO users (email, user_type, email_verified, password_hash)
-- VALUES ('admin@example.com', 'admin', TRUE, 'CHANGE_THIS_PASSWORD_HASH');

