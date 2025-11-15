-- =============================================
-- email_templates テーブル（ハイブリッド方式）
-- SendGrid Dynamic Templates + 独自テンプレートの両方に対応
-- =============================================

-- 既存のテーブルを削除して再作成
DROP TABLE IF EXISTS email_templates CASCADE;

-- ハイブリッド型テーブル
CREATE TABLE email_templates (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- テンプレート識別情報
    template_type VARCHAR(50) NOT NULL UNIQUE, -- application_confirmation, card_registration, etc.
    template_name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- SendGrid Dynamic Template（オプション）
    sendgrid_template_id VARCHAR(100), -- SendGridのテンプレートIDを保存
    use_sendgrid_template BOOLEAN DEFAULT FALSE, -- SendGridテンプレートを使うかどうか
    
    -- 独自テンプレート（SendGrid使わない場合）
    subject VARCHAR(500), -- メール件名（変数使用可能）
    body_text TEXT, -- プレーンテキスト版
    body_html TEXT, -- HTML版
    
    -- メタ情報
    category VARCHAR(50) DEFAULT 'automatic', -- automatic, manual, notification
    sort_order INTEGER DEFAULT 0, -- 表示順序
    variables JSONB, -- 使用可能な変数のリスト（ドキュメント用）
    
    -- ステータス
    is_active BOOLEAN DEFAULT TRUE,
    
    -- 管理情報
    created_by UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    updated_by UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- タイムスタンプ
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- 制約: SendGridまたは独自テンプレートのいずれかが必須
    CONSTRAINT check_template_content CHECK (
        (use_sendgrid_template = TRUE AND sendgrid_template_id IS NOT NULL) OR
        (use_sendgrid_template = FALSE AND subject IS NOT NULL AND body_text IS NOT NULL)
    )
);

-- インデックス
CREATE INDEX idx_email_templates_type ON email_templates(template_type);
CREATE INDEX idx_email_templates_active ON email_templates(is_active);
CREATE INDEX idx_email_templates_category ON email_templates(category);
CREATE INDEX idx_email_templates_sort_order ON email_templates(category, sort_order);
CREATE INDEX idx_email_templates_sendgrid ON email_templates(sendgrid_template_id);

-- コメント
COMMENT ON TABLE email_templates IS 'メールテンプレート管理（ハイブリッド方式）';
COMMENT ON COLUMN email_templates.template_type IS 'テンプレートタイプ（システム内部での識別子、UNIQUE）';
COMMENT ON COLUMN email_templates.template_name IS 'テンプレート表示名';
COMMENT ON COLUMN email_templates.sendgrid_template_id IS 'SendGridのDynamic Template ID';
COMMENT ON COLUMN email_templates.use_sendgrid_template IS 'TRUE: SendGridテンプレート使用、FALSE: 独自テンプレート使用';
COMMENT ON COLUMN email_templates.subject IS 'メール件名（独自テンプレート用、変数使用可能）';
COMMENT ON COLUMN email_templates.body_text IS 'メール本文テキスト版（独自テンプレート用）';
COMMENT ON COLUMN email_templates.body_html IS 'メール本文HTML版（独自テンプレート用）';
COMMENT ON COLUMN email_templates.category IS 'カテゴリ（application_flow, exam_related, announcements, post_exam）';
COMMENT ON COLUMN email_templates.sort_order IS '表示順序（カテゴリ内での並び順）';
COMMENT ON COLUMN email_templates.variables IS '使用可能な変数のリスト（JSON形式、ドキュメント用）';

-- 確認
SELECT 
    column_name, 
    data_type, 
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name = 'email_templates'
ORDER BY ordinal_position;

