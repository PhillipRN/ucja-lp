-- =============================================
-- Cambridge Exam Application System
-- Supabase Database Schema v2.0
-- 
-- サービス全体をカバーする完全版スキーマ
-- =============================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =============================================
-- Users Table (ユーザーアカウント)
-- =============================================
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255), -- Supabase Authを使う場合は不要
    user_type VARCHAR(20) NOT NULL CHECK (user_type IN ('student', 'guardian', 'admin')),
    
    -- プロフィール
    full_name VARCHAR(100),
    phone VARCHAR(50),
    
    -- 認証
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires_at TIMESTAMP WITH TIME ZONE,
    
    -- タイムスタンプ
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
    amount INTEGER NOT NULL, -- 総額（チーム戦の場合は代表者分のみ）
    
    -- 支払いステータス（代表者分）
    payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'completed', 'failed', 'refunded')),
    payment_method VARCHAR(50) DEFAULT 'stripe',
    stripe_payment_intent_id VARCHAR(255),
    paid_at TIMESTAMP WITH TIME ZONE,
    
    -- KYC（本人確認）ステータス（代表者分）
    kyc_status VARCHAR(20) DEFAULT 'pending' CHECK (kyc_status IN ('pending', 'in_progress', 'completed', 'failed', 'rejected')),
    kyc_verified_at TIMESTAMP WITH TIME ZONE,
    
    -- 申込ステータス
    application_status VARCHAR(20) DEFAULT 'draft' CHECK (application_status IN (
        'draft',              -- 下書き
        'submitted',          -- 申込送信済み
        'kyc_pending',        -- 本人確認待ち
        'payment_pending',    -- 決済待ち
        'confirmed',          -- 確定（参加可能）
        'cancelled'           -- キャンセル
    )),
    
    -- 特記事項
    special_requests TEXT,
    
    -- 試験参加情報
    exam_started_at TIMESTAMP WITH TIME ZONE,
    exam_completed_at TIMESTAMP WITH TIME ZONE,
    exam_status VARCHAR(20) DEFAULT 'not_started' CHECK (exam_status IN (
        'not_started',
        'in_progress',
        'completed',
        'disqualified'
    )),
    
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
    
    -- チーム全体のステータス
    all_members_paid BOOLEAN DEFAULT FALSE, -- 全員支払い完了
    all_members_kyc_completed BOOLEAN DEFAULT FALSE, -- 全員本人確認完了
    team_ready BOOLEAN DEFAULT FALSE, -- チーム参加準備完了
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Team Members (チームメンバー)
-- =============================================
CREATE TABLE team_members (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    team_application_id UUID NOT NULL REFERENCES team_applications(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL, -- ユーザーアカウントと紐付け
    
    member_number INTEGER NOT NULL CHECK (member_number BETWEEN 1 AND 5),
    member_name VARCHAR(100) NOT NULL,
    member_email VARCHAR(255) NOT NULL,
    is_representative BOOLEAN DEFAULT FALSE, -- メンバー1は代表者
    
    -- 各メンバーの決済情報
    payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'completed', 'failed', 'refunded')),
    stripe_payment_intent_id VARCHAR(255),
    payment_link_sent_at TIMESTAMP WITH TIME ZONE, -- 支払いリンク送信日時
    paid_at TIMESTAMP WITH TIME ZONE,
    
    -- 各メンバーの本人確認情報
    kyc_status VARCHAR(20) DEFAULT 'pending' CHECK (kyc_status IN ('pending', 'in_progress', 'completed', 'failed', 'rejected')),
    kyc_verified_at TIMESTAMP WITH TIME ZONE,
    
    -- 試験参加状況
    exam_participated BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(team_application_id, member_number),
    UNIQUE(team_application_id, member_email)
);

-- =============================================
-- KYC Verifications (本人確認詳細 - Liquid eKYC)
-- =============================================
CREATE TABLE kyc_verifications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID REFERENCES applications(id) ON DELETE CASCADE,
    team_member_id UUID REFERENCES team_members(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    
    -- Liquid eKYC情報
    liquid_verification_id VARCHAR(255), -- Liquid側のID
    verification_url TEXT, -- Liquid eKYCのURL
    verification_status VARCHAR(50) DEFAULT 'pending',
    
    -- 本人確認書類情報
    document_type VARCHAR(50), -- 'drivers_license', 'passport', 'residence_card'等
    document_number VARCHAR(100),
    document_verified BOOLEAN DEFAULT FALSE,
    
    -- 個人情報（Liquidから取得）
    verified_name VARCHAR(100),
    verified_name_kana VARCHAR(100),
    verified_date_of_birth DATE,
    verified_address TEXT,
    
    -- 顔認証
    face_verified BOOLEAN DEFAULT FALSE,
    liveness_check_passed BOOLEAN DEFAULT FALSE,
    
    -- Liquidのレスポンスデータ（JSON）
    liquid_response_data JSONB,
    
    -- ステータス
    started_at TIMESTAMP WITH TIME ZONE,
    completed_at TIMESTAMP WITH TIME ZONE,
    failed_at TIMESTAMP WITH TIME ZONE,
    failure_reason TEXT,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- application_id OR team_member_id のどちらか必須
    CHECK (
        (application_id IS NOT NULL AND team_member_id IS NULL) OR
        (application_id IS NULL AND team_member_id IS NOT NULL)
    )
);

-- =============================================
-- Questions (試験問題)
-- =============================================
CREATE TABLE questions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    question_number INTEGER NOT NULL,
    exam_year INTEGER NOT NULL, -- 2026等
    
    -- 問題内容
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL CHECK (question_type IN ('multiple_choice', 'fill_in_blank', 'true_false')),
    
    -- 選択肢（multiple_choiceの場合）
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
    
    -- 正解
    correct_answer VARCHAR(255) NOT NULL,
    
    -- 配点
    points INTEGER DEFAULT 1,
    
    -- 難易度
    difficulty VARCHAR(20) CHECK (difficulty IN ('easy', 'medium', 'hard')),
    
    -- カテゴリー
    category VARCHAR(50), -- 'vocabulary', 'grammar', 'reading'等
    
    -- 公開状態
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(exam_year, question_number)
);

-- =============================================
-- User Answers (ユーザー回答)
-- =============================================
CREATE TABLE user_answers (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID NOT NULL REFERENCES applications(id) ON DELETE CASCADE,
    team_member_id UUID REFERENCES team_members(id) ON DELETE CASCADE,
    question_id UUID NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    
    -- 回答
    user_answer VARCHAR(255),
    is_correct BOOLEAN,
    points_earned INTEGER DEFAULT 0,
    
    -- 時間計測
    answer_started_at TIMESTAMP WITH TIME ZONE,
    answer_submitted_at TIMESTAMP WITH TIME ZONE,
    time_taken_seconds INTEGER, -- 解答にかかった秒数
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(application_id, question_id)
);

-- =============================================
-- Exam Results (試験結果)
-- =============================================
CREATE TABLE exam_results (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID UNIQUE NOT NULL REFERENCES applications(id) ON DELETE CASCADE,
    team_member_id UUID UNIQUE REFERENCES team_members(id) ON DELETE CASCADE,
    
    -- 基本スコア
    total_questions INTEGER NOT NULL DEFAULT 0,
    correct_answers INTEGER NOT NULL DEFAULT 0,
    total_points INTEGER NOT NULL DEFAULT 0, -- 正答による得点
    
    -- 時間スコア
    total_time_seconds INTEGER NOT NULL DEFAULT 0, -- 全体の解答時間
    time_bonus_points INTEGER DEFAULT 0, -- 時間ボーナス（速さによる加点）
    
    -- 最終スコア（正答点 + 時間ボーナス）
    final_score INTEGER NOT NULL DEFAULT 0,
    
    -- ランキング
    individual_rank INTEGER, -- 個人戦でのランク
    team_rank INTEGER, -- チーム戦でのチーム順位
    
    -- パーセンタイル
    percentile DECIMAL(5,2),
    
    -- 賞・研修プログラム
    prize_eligible BOOLEAN DEFAULT FALSE,
    prize_name VARCHAR(100),
    cambridge_program_eligible BOOLEAN DEFAULT FALSE,
    
    -- チーム戦用：スコア採用フラグ
    counted_in_team_score BOOLEAN DEFAULT FALSE, -- 上位4名に入っているか
    
    -- カテゴリー別スコア（オプション）
    vocabulary_score INTEGER,
    grammar_score INTEGER,
    reading_score INTEGER,
    
    -- 結果公開
    results_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP WITH TIME ZONE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- application_id OR team_member_id のどちらか必須
    CHECK (
        (application_id IS NOT NULL AND team_member_id IS NULL) OR
        (application_id IS NULL AND team_member_id IS NOT NULL)
    )
);

-- =============================================
-- Team Scores (チームスコア集計)
-- =============================================
CREATE TABLE team_scores (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    team_application_id UUID UNIQUE NOT NULL REFERENCES team_applications(id) ON DELETE CASCADE,
    
    -- チーム全体のスコア（上位4名の合計）
    total_team_score INTEGER NOT NULL DEFAULT 0,
    
    -- 採用されたメンバー（上位4名）
    top_member_1_id UUID REFERENCES team_members(id),
    top_member_2_id UUID REFERENCES team_members(id),
    top_member_3_id UUID REFERENCES team_members(id),
    top_member_4_id UUID REFERENCES team_members(id),
    
    -- ランキング
    team_rank INTEGER,
    percentile DECIMAL(5,2),
    
    -- 賞
    prize_eligible BOOLEAN DEFAULT FALSE,
    prize_name VARCHAR(100),
    
    -- 統計
    average_score DECIMAL(10,2),
    highest_individual_score INTEGER,
    lowest_individual_score INTEGER,
    
    calculated_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Payment Transactions (決済トランザクション履歴)
-- =============================================
CREATE TABLE payment_transactions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID REFERENCES applications(id) ON DELETE CASCADE,
    team_member_id UUID REFERENCES team_members(id) ON DELETE CASCADE,
    
    transaction_type VARCHAR(20) NOT NULL CHECK (transaction_type IN ('payment', 'refund')),
    amount INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'JPY',
    
    -- Stripe情報
    stripe_payment_intent_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),
    stripe_refund_id VARCHAR(255),
    stripe_payment_link VARCHAR(500), -- Stripeの支払いリンク
    
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
    team_member_id UUID REFERENCES team_members(id) ON DELETE SET NULL,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    
    email_type VARCHAR(50) NOT NULL, -- 'application_confirmation', 'payment_confirmation', 'payment_request', 'kyc_reminder', 'exam_reminder', 'results_notification'
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    
    status VARCHAR(20) NOT NULL CHECK (status IN ('pending', 'sent', 'failed', 'bounced')),
    sent_at TIMESTAMP WITH TIME ZONE,
    
    error_message TEXT,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Notifications (マイページ通知)
-- =============================================
CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    notification_type VARCHAR(50) NOT NULL, -- 'payment_required', 'kyc_required', 'exam_ready', 'results_available'
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP WITH TIME ZONE,
    
    -- 関連リンク
    action_url VARCHAR(500),
    action_label VARCHAR(100),
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Admin Activity Logs (管理者操作ログ)
-- =============================================
CREATE TABLE admin_activity_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    admin_user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    action_type VARCHAR(50) NOT NULL, -- 'create', 'update', 'delete', 'approve', 'reject'
    target_table VARCHAR(50) NOT NULL,
    target_id UUID,
    
    description TEXT,
    changes JSONB, -- 変更内容のJSON
    
    ip_address INET,
    user_agent TEXT,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Indexes for Performance
-- =============================================

-- Applications
CREATE INDEX idx_applications_user_id ON applications(user_id);
CREATE INDEX idx_applications_application_number ON applications(application_number);
CREATE INDEX idx_applications_payment_status ON applications(payment_status);
CREATE INDEX idx_applications_kyc_status ON applications(kyc_status);
CREATE INDEX idx_applications_application_status ON applications(application_status);
CREATE INDEX idx_applications_exam_status ON applications(exam_status);
CREATE INDEX idx_applications_created_at ON applications(created_at);

-- Individual Applications
CREATE INDEX idx_individual_applications_student_email ON individual_applications(student_email);
CREATE INDEX idx_individual_applications_guardian_email ON individual_applications(guardian_email);

-- Team Members
CREATE INDEX idx_team_members_team_application_id ON team_members(team_application_id);
CREATE INDEX idx_team_members_user_id ON team_members(user_id);
CREATE INDEX idx_team_members_member_email ON team_members(member_email);
CREATE INDEX idx_team_members_payment_status ON team_members(payment_status);
CREATE INDEX idx_team_members_kyc_status ON team_members(kyc_status);

-- KYC Verifications
CREATE INDEX idx_kyc_verifications_application_id ON kyc_verifications(application_id);
CREATE INDEX idx_kyc_verifications_team_member_id ON kyc_verifications(team_member_id);
CREATE INDEX idx_kyc_verifications_liquid_id ON kyc_verifications(liquid_verification_id);

-- Questions
CREATE INDEX idx_questions_exam_year ON questions(exam_year);
CREATE INDEX idx_questions_category ON questions(category);
CREATE INDEX idx_questions_is_active ON questions(is_active);

-- User Answers
CREATE INDEX idx_user_answers_application_id ON user_answers(application_id);
CREATE INDEX idx_user_answers_question_id ON user_answers(question_id);
CREATE INDEX idx_user_answers_team_member_id ON user_answers(team_member_id);

-- Exam Results
CREATE INDEX idx_exam_results_individual_rank ON exam_results(individual_rank);
CREATE INDEX idx_exam_results_team_rank ON exam_results(team_rank);
CREATE INDEX idx_exam_results_final_score ON exam_results(final_score DESC);

-- Payment Transactions
CREATE INDEX idx_payment_transactions_application_id ON payment_transactions(application_id);
CREATE INDEX idx_payment_transactions_team_member_id ON payment_transactions(team_member_id);
CREATE INDEX idx_payment_transactions_stripe_payment_intent_id ON payment_transactions(stripe_payment_intent_id);

-- User Sessions
CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_session_token ON user_sessions(session_token);
CREATE INDEX idx_user_sessions_expires_at ON user_sessions(expires_at);

-- Notifications
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created_at ON notifications(created_at DESC);

-- =============================================
-- Row Level Security (RLS) Policies
-- =============================================

-- Enable RLS
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE applications ENABLE ROW LEVEL SECURITY;
ALTER TABLE individual_applications ENABLE ROW LEVEL SECURITY;
ALTER TABLE team_applications ENABLE ROW LEVEL SECURITY;
ALTER TABLE team_members ENABLE ROW LEVEL SECURITY;
ALTER TABLE kyc_verifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE questions ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_answers ENABLE ROW LEVEL SECURITY;
ALTER TABLE exam_results ENABLE ROW LEVEL SECURITY;
ALTER TABLE team_scores ENABLE ROW LEVEL SECURITY;
ALTER TABLE payment_transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;

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

-- Questions: 公開されている問題は全員閲覧可能
CREATE POLICY "Active questions are viewable by authenticated users" ON questions
    FOR SELECT USING (is_active = TRUE AND auth.role() = 'authenticated');

-- User Answers: 自分の回答のみ閲覧・作成可能
CREATE POLICY "Users can view own answers" ON user_answers
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM applications 
            WHERE applications.id = user_answers.application_id 
            AND applications.user_id = auth.uid()
        )
    );

CREATE POLICY "Users can create own answers" ON user_answers
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM applications 
            WHERE applications.id = user_answers.application_id 
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

-- Notifications: 自分宛の通知のみ閲覧・更新可能
CREATE POLICY "Users can view own notifications" ON notifications
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can update own notifications" ON notifications
    FOR UPDATE USING (auth.uid() = user_id);

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

CREATE TRIGGER update_team_members_updated_at BEFORE UPDATE ON team_members
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_kyc_verifications_updated_at BEFORE UPDATE ON kyc_verifications
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_questions_updated_at BEFORE UPDATE ON questions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_exam_results_updated_at BEFORE UPDATE ON exam_results
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_team_scores_updated_at BEFORE UPDATE ON team_scores
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

-- チームメンバーの決済・KYCステータスを更新したときにチーム全体のステータスを更新
CREATE OR REPLACE FUNCTION update_team_status()
RETURNS TRIGGER AS $$
DECLARE
    team_id UUID;
    all_paid BOOLEAN;
    all_kyc BOOLEAN;
BEGIN
    team_id := NEW.team_application_id;
    
    -- 全員の支払いが完了しているかチェック
    SELECT BOOL_AND(payment_status = 'completed') INTO all_paid
    FROM team_members
    WHERE team_application_id = team_id;
    
    -- 全員の本人確認が完了しているかチェック
    SELECT BOOL_AND(kyc_status = 'completed') INTO all_kyc
    FROM team_members
    WHERE team_application_id = team_id;
    
    -- team_applicationsを更新
    UPDATE team_applications
    SET 
        all_members_paid = COALESCE(all_paid, FALSE),
        all_members_kyc_completed = COALESCE(all_kyc, FALSE),
        team_ready = (COALESCE(all_paid, FALSE) AND COALESCE(all_kyc, FALSE))
    WHERE id = team_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER team_members_status_update_trigger
    AFTER INSERT OR UPDATE OF payment_status, kyc_status ON team_members
    FOR EACH ROW
    EXECUTE FUNCTION update_team_status();

-- 試験結果から最終スコアを自動計算
CREATE OR REPLACE FUNCTION calculate_final_score()
RETURNS TRIGGER AS $$
BEGIN
    -- 最終スコア = 正答点 + 時間ボーナス
    NEW.final_score := NEW.total_points + COALESCE(NEW.time_bonus_points, 0);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER calculate_final_score_trigger
    BEFORE INSERT OR UPDATE OF total_points, time_bonus_points ON exam_results
    FOR EACH ROW
    EXECUTE FUNCTION calculate_final_score();

