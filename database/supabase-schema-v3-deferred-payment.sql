-- =============================================
-- Cambridge Exam Application System
-- Supabase Database Schema v3.0
-- 
-- Stripe後日課金対応版（SetupIntent方式）
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
    
    -- Stripe顧客情報
    stripe_customer_id VARCHAR(255), -- Stripe Customer ID
    
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
    application_number VARCHAR(50) UNIQUE NOT NULL, -- 申込番号（例：UCJA-2025-01-000001）
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    
    -- 環境情報
    environment VARCHAR(20) DEFAULT 'development',
    
    -- 参加形式
    participation_type VARCHAR(20) NOT NULL CHECK (participation_type IN ('individual', 'team')),
    
    -- 料金プラン
    pricing_type VARCHAR(50) NOT NULL,
    amount INTEGER NOT NULL, -- 総額（チーム戦の場合は代表者分のみ）
    
    -- Stripe情報（SetupIntent方式）
    stripe_customer_id VARCHAR(255), -- Stripe Customer ID
    stripe_setup_intent_id VARCHAR(255), -- SetupIntent ID（カード登録時）
    stripe_payment_method_id VARCHAR(255), -- 保存されたPaymentMethod ID
    stripe_payment_intent_id VARCHAR(255), -- 実際の課金時のPaymentIntent ID
    
    -- カード登録状態
    card_registered BOOLEAN DEFAULT FALSE,
    card_registered_at TIMESTAMP WITH TIME ZONE,
    card_last4 VARCHAR(4), -- カード下4桁（表示用）
    card_brand VARCHAR(20), -- VISA, MasterCard等
    
    -- 支払いステータス
    payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN (
        'pending',           -- 未登録（カード登録前）
        'card_registered',   -- カード登録済み（課金前）
        'processing',        -- 決済処理中
        'completed',         -- 決済完了
        'failed',            -- 決済失敗
        'refunded'           -- 返金済み
    )),
    payment_method VARCHAR(50) DEFAULT 'stripe',
    
    -- 課金スケジュール
    scheduled_charge_date DATE, -- 課金予定日（NULLの場合は本人確認完了時に即課金）
    charged_at TIMESTAMP WITH TIME ZONE, -- 実際の課金日時
    
    -- KYC（本人確認）ステータス
    kyc_status VARCHAR(20) DEFAULT 'pending' CHECK (kyc_status IN ('pending', 'in_progress', 'completed', 'failed', 'rejected')),
    kyc_verified_at TIMESTAMP WITH TIME ZONE,
    
    -- 申込ステータス
    application_status VARCHAR(20) DEFAULT 'draft' CHECK (application_status IN (
        'draft',                    -- 下書き
        'submitted',                -- 申込送信済み
        'card_pending',             -- カード登録待ち
        'kyc_pending',              -- 本人確認待ち
        'charge_scheduled',         -- 課金予約済み（本人確認完了、課金予定日待ち）
        'payment_processing',       -- 決済処理中
        'confirmed',                -- 確定（参加可能）
        'cancelled'                 -- キャンセル
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
    all_members_card_registered BOOLEAN DEFAULT FALSE, -- 全員カード登録完了
    all_members_kyc_completed BOOLEAN DEFAULT FALSE, -- 全員本人確認完了
    all_members_paid BOOLEAN DEFAULT FALSE, -- 全員支払い完了
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
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    
    member_number INTEGER NOT NULL CHECK (member_number BETWEEN 1 AND 5),
    member_name VARCHAR(100) NOT NULL,
    member_email VARCHAR(255) NOT NULL,
    is_representative BOOLEAN DEFAULT FALSE,
    
    -- Stripe情報（SetupIntent方式）
    stripe_customer_id VARCHAR(255),
    stripe_setup_intent_id VARCHAR(255),
    stripe_payment_method_id VARCHAR(255),
    stripe_payment_intent_id VARCHAR(255),
    
    -- カード登録状態
    card_registered BOOLEAN DEFAULT FALSE,
    card_registered_at TIMESTAMP WITH TIME ZONE,
    card_last4 VARCHAR(4),
    card_brand VARCHAR(20),
    
    -- 支払い情報
    payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN (
        'pending',
        'card_registered',
        'processing',
        'completed',
        'failed',
        'refunded'
    )),
    payment_link_sent_at TIMESTAMP WITH TIME ZONE,
    
    -- 課金スケジュール
    scheduled_charge_date DATE,
    charged_at TIMESTAMP WITH TIME ZONE,
    
    -- 本人確認
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
-- Scheduled Charges (課金スケジュール管理)
-- =============================================
CREATE TABLE scheduled_charges (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID REFERENCES applications(id) ON DELETE CASCADE,
    team_member_id UUID REFERENCES team_members(id) ON DELETE CASCADE,
    
    -- 課金情報
    amount INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'JPY',
    
    -- Stripe情報
    stripe_customer_id VARCHAR(255) NOT NULL,
    stripe_payment_method_id VARCHAR(255) NOT NULL,
    
    -- スケジュール
    scheduled_date DATE NOT NULL,
    scheduled_time TIME DEFAULT '09:00:00', -- 課金実行時刻
    
    -- ステータス
    status VARCHAR(20) DEFAULT 'scheduled' CHECK (status IN (
        'scheduled',      -- 予約済み
        'processing',     -- 処理中
        'completed',      -- 完了
        'failed',         -- 失敗
        'cancelled'       -- キャンセル
    )),
    
    -- 実行情報
    executed_at TIMESTAMP WITH TIME ZONE,
    stripe_payment_intent_id VARCHAR(255),
    
    -- エラー情報
    error_code VARCHAR(50),
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    
    -- メタデータ
    metadata JSONB,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- application_id OR team_member_id のどちらか必須
    CHECK (
        (application_id IS NOT NULL AND team_member_id IS NULL) OR
        (application_id IS NULL AND team_member_id IS NOT NULL)
    )
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
    liquid_verification_id VARCHAR(255),
    verification_url TEXT,
    verification_status VARCHAR(50) DEFAULT 'pending',
    
    -- 本人確認書類情報
    document_type VARCHAR(50),
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
    exam_year INTEGER NOT NULL,
    
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL CHECK (question_type IN ('multiple_choice', 'fill_in_blank', 'true_false')),
    
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
    
    correct_answer VARCHAR(255) NOT NULL,
    points INTEGER DEFAULT 1,
    difficulty VARCHAR(20) CHECK (difficulty IN ('easy', 'medium', 'hard')),
    category VARCHAR(50),
    
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
    
    user_answer VARCHAR(255),
    is_correct BOOLEAN,
    points_earned INTEGER DEFAULT 0,
    
    answer_started_at TIMESTAMP WITH TIME ZONE,
    answer_submitted_at TIMESTAMP WITH TIME ZONE,
    time_taken_seconds INTEGER,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(application_id, question_id)
);

-- =============================================
-- Exam Results (試験結果)
-- =============================================
CREATE TABLE exam_results (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID UNIQUE REFERENCES applications(id) ON DELETE CASCADE,
    team_member_id UUID UNIQUE REFERENCES team_members(id) ON DELETE CASCADE,
    
    total_questions INTEGER NOT NULL DEFAULT 0,
    correct_answers INTEGER NOT NULL DEFAULT 0,
    total_points INTEGER NOT NULL DEFAULT 0,
    
    total_time_seconds INTEGER NOT NULL DEFAULT 0,
    time_bonus_points INTEGER DEFAULT 0,
    
    final_score INTEGER NOT NULL DEFAULT 0,
    
    individual_rank INTEGER,
    team_rank INTEGER,
    percentile DECIMAL(5,2),
    
    prize_eligible BOOLEAN DEFAULT FALSE,
    prize_name VARCHAR(100),
    cambridge_program_eligible BOOLEAN DEFAULT FALSE,
    
    counted_in_team_score BOOLEAN DEFAULT FALSE,
    
    vocabulary_score INTEGER,
    grammar_score INTEGER,
    reading_score INTEGER,
    
    results_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP WITH TIME ZONE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
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
    
    total_team_score INTEGER NOT NULL DEFAULT 0,
    
    top_member_1_id UUID REFERENCES team_members(id),
    top_member_2_id UUID REFERENCES team_members(id),
    top_member_3_id UUID REFERENCES team_members(id),
    top_member_4_id UUID REFERENCES team_members(id),
    
    team_rank INTEGER,
    percentile DECIMAL(5,2),
    
    prize_eligible BOOLEAN DEFAULT FALSE,
    prize_name VARCHAR(100),
    
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
    
    transaction_type VARCHAR(20) NOT NULL CHECK (transaction_type IN ('setup', 'payment', 'refund')),
    amount INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'JPY',
    
    -- Stripe情報
    stripe_customer_id VARCHAR(255),
    stripe_setup_intent_id VARCHAR(255),
    stripe_payment_method_id VARCHAR(255),
    stripe_payment_intent_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),
    stripe_refund_id VARCHAR(255),
    
    status VARCHAR(20) NOT NULL CHECK (status IN ('pending', 'succeeded', 'failed', 'cancelled')),
    
    error_code VARCHAR(50),
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
    
    email_type VARCHAR(50) NOT NULL,
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
    
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP WITH TIME ZONE,
    
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
    
    action_type VARCHAR(50) NOT NULL,
    target_table VARCHAR(50) NOT NULL,
    target_id UUID,
    
    description TEXT,
    changes JSONB,
    
    ip_address INET,
    user_agent TEXT,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Indexes for Performance
-- =============================================

CREATE INDEX idx_applications_user_id ON applications(user_id);
CREATE INDEX idx_applications_stripe_customer_id ON applications(stripe_customer_id);
CREATE INDEX idx_applications_stripe_payment_method_id ON applications(stripe_payment_method_id);
CREATE INDEX idx_applications_payment_status ON applications(payment_status);
CREATE INDEX idx_applications_kyc_status ON applications(kyc_status);
CREATE INDEX idx_applications_application_status ON applications(application_status);
CREATE INDEX idx_applications_scheduled_charge_date ON applications(scheduled_charge_date);

CREATE INDEX idx_team_members_stripe_customer_id ON team_members(stripe_customer_id);
CREATE INDEX idx_team_members_payment_status ON team_members(payment_status);
CREATE INDEX idx_team_members_kyc_status ON team_members(kyc_status);

CREATE INDEX idx_scheduled_charges_scheduled_date ON scheduled_charges(scheduled_date);
CREATE INDEX idx_scheduled_charges_status ON scheduled_charges(status);
CREATE INDEX idx_scheduled_charges_application_id ON scheduled_charges(application_id);
CREATE INDEX idx_scheduled_charges_team_member_id ON scheduled_charges(team_member_id);

CREATE INDEX idx_payment_transactions_stripe_setup_intent_id ON payment_transactions(stripe_setup_intent_id);
CREATE INDEX idx_payment_transactions_stripe_payment_method_id ON payment_transactions(stripe_payment_method_id);
CREATE INDEX idx_payment_transactions_transaction_type ON payment_transactions(transaction_type);

-- その他のインデックスは v2 と同様

-- =============================================
-- Row Level Security (RLS) Policies
-- =============================================
-- v2 と同様のポリシー

-- =============================================
-- Functions and Triggers
-- =============================================

-- 更新日時を自動更新
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 各テーブルにトリガーを設定
CREATE TRIGGER update_applications_updated_at BEFORE UPDATE ON applications
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ... 他のトリガーも同様

-- 申込番号自動生成
CREATE OR REPLACE FUNCTION generate_application_number()
RETURNS TRIGGER AS $$
DECLARE
    year_str VARCHAR(4);
    month_str VARCHAR(2);
    seq_num INTEGER;
    prefix VARCHAR(10);
    new_number VARCHAR(50);
BEGIN
    year_str := TO_CHAR(CURRENT_DATE, 'YYYY');
    month_str := TO_CHAR(CURRENT_DATE, 'MM');
    
    IF COALESCE(NEW.environment, 'development') = 'production' THEN
        prefix := 'UCJA';
    ELSE
        prefix := 'DEV';
    END IF;
    
    SELECT COALESCE(MAX(
        CAST(
            SUBSTRING(application_number FROM '[0-9]{6}$') AS INTEGER
        )
    ), 0) + 1 INTO seq_num
    FROM applications
    WHERE application_number LIKE prefix || '-' || year_str || '-' || month_str || '-%';
    
    new_number := prefix || '-' || year_str || '-' || month_str || '-' || LPAD(seq_num::TEXT, 6, '0');
    NEW.application_number := new_number;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER generate_application_number_trigger
    BEFORE INSERT ON applications
    FOR EACH ROW
    WHEN (NEW.application_number IS NULL)
    EXECUTE FUNCTION generate_application_number();

-- 本人確認完了時に課金スケジュールを自動作成
CREATE OR REPLACE FUNCTION schedule_charge_on_kyc_completion()
RETURNS TRIGGER AS $$
DECLARE
    target_application_id UUID;
    target_amount INTEGER;
    target_scheduled_date DATE;
BEGIN
    -- KYCステータスが completed になった場合
    IF NEW.kyc_status = 'completed' AND (OLD.kyc_status IS DISTINCT FROM 'completed') THEN
        
        -- applicationsの場合
        IF TG_TABLE_NAME = 'applications' THEN
            -- カード登録済みかチェック
            IF NEW.card_registered = TRUE AND NEW.stripe_payment_method_id IS NOT NULL THEN
                -- 課金スケジュールがない場合は作成
                INSERT INTO scheduled_charges (
                    application_id,
                    amount,
                    stripe_customer_id,
                    stripe_payment_method_id,
                    scheduled_date,
                    status
                )
                SELECT
                    NEW.id,
                    NEW.amount,
                    NEW.stripe_customer_id,
                    NEW.stripe_payment_method_id,
                    COALESCE(NEW.scheduled_charge_date, CURRENT_DATE), -- 即座に課金 or 指定日
                    'scheduled'
                WHERE NOT EXISTS (
                    SELECT 1 FROM scheduled_charges 
                    WHERE application_id = NEW.id AND status IN ('scheduled', 'completed')
                );
                
                -- application_statusを更新
                NEW.application_status := 'charge_scheduled';
            END IF;
        
        -- team_membersの場合
        ELSIF TG_TABLE_NAME = 'team_members' THEN
            IF NEW.card_registered = TRUE AND NEW.stripe_payment_method_id IS NOT NULL THEN
                -- 紐づくapplication情報を取得
                SELECT ta.application_id INTO target_application_id
                FROM team_applications ta
                WHERE ta.id = NEW.team_application_id;
                
                IF target_application_id IS NULL THEN
                    RETURN NEW;
                END IF;
                
                SELECT amount, scheduled_charge_date
                INTO target_amount, target_scheduled_date
                FROM applications
                WHERE id = target_application_id;
                
                INSERT INTO scheduled_charges (
                    application_id,
                    team_member_id,
                    amount,
                    stripe_customer_id,
                    stripe_payment_method_id,
                    scheduled_date,
                    status
                )
                SELECT
                    target_application_id,
                    NEW.id,
                    COALESCE(target_amount, 0),
                    NEW.stripe_customer_id,
                    NEW.stripe_payment_method_id,
                    COALESCE(NEW.scheduled_charge_date, target_scheduled_date, CURRENT_DATE),
                    'scheduled'
                WHERE NOT EXISTS (
                    SELECT 1 FROM scheduled_charges 
                    WHERE team_member_id = NEW.id AND status IN ('scheduled', 'completed')
                );
            END IF;
        END IF;
        
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_schedule_charge_on_kyc_completion
    BEFORE UPDATE OF kyc_status ON applications
    FOR EACH ROW
    EXECUTE FUNCTION schedule_charge_on_kyc_completion();

CREATE TRIGGER trigger_schedule_charge_on_member_kyc_completion
    BEFORE UPDATE OF kyc_status ON team_members
    FOR EACH ROW
    EXECUTE FUNCTION schedule_charge_on_kyc_completion();

-- チームメンバーのステータス更新
CREATE OR REPLACE FUNCTION update_team_status()
RETURNS TRIGGER AS $$
DECLARE
    team_id UUID;
    all_cards BOOLEAN;
    all_kyc BOOLEAN;
    all_paid BOOLEAN;
BEGIN
    team_id := NEW.team_application_id;
    
    -- 全員カード登録完了チェック
    SELECT BOOL_AND(card_registered = TRUE) INTO all_cards
    FROM team_members
    WHERE team_application_id = team_id;
    
    -- 全員本人確認完了チェック
    SELECT BOOL_AND(kyc_status = 'completed') INTO all_kyc
    FROM team_members
    WHERE team_application_id = team_id;
    
    -- 全員支払い完了チェック
    SELECT BOOL_AND(payment_status = 'completed') INTO all_paid
    FROM team_members
    WHERE team_application_id = team_id;
    
    -- team_applicationsを更新
    UPDATE team_applications
    SET 
        all_members_card_registered = COALESCE(all_cards, FALSE),
        all_members_kyc_completed = COALESCE(all_kyc, FALSE),
        all_members_paid = COALESCE(all_paid, FALSE),
        team_ready = (COALESCE(all_paid, FALSE) AND COALESCE(all_kyc, FALSE))
    WHERE id = team_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER team_members_status_update_trigger
    AFTER INSERT OR UPDATE OF card_registered, payment_status, kyc_status ON team_members
    FOR EACH ROW
    EXECUTE FUNCTION update_team_status();

-- 試験結果の最終スコア自動計算
CREATE OR REPLACE FUNCTION calculate_final_score()
RETURNS TRIGGER AS $$
BEGIN
    NEW.final_score := NEW.total_points + COALESCE(NEW.time_bonus_points, 0);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER calculate_final_score_trigger
    BEFORE INSERT OR UPDATE OF total_points, time_bonus_points ON exam_results
    FOR EACH ROW
    EXECUTE FUNCTION calculate_final_score();

