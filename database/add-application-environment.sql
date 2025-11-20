BEGIN;

-- applications に環境識別用カラムを追加
ALTER TABLE applications
    ADD COLUMN IF NOT EXISTS environment VARCHAR(20) DEFAULT 'development';

-- 既存レコードでNULLのものにデフォルト値を設定
UPDATE applications
SET environment = COALESCE(environment, 'development');

-- application_number 生成関数を更新
CREATE OR REPLACE FUNCTION generate_application_number()
RETURNS TRIGGER AS $$
DECLARE
    year_str VARCHAR(4);
    month_str VARCHAR(2);
    seq_num INTEGER;
    prefix VARCHAR(10);
BEGIN
    year_str := TO_CHAR(CURRENT_DATE, 'YYYY');
    month_str := TO_CHAR(CURRENT_DATE, 'MM');

    IF COALESCE(NEW.environment, 'development') = 'production' THEN
        prefix := 'UCJA';
    ELSE
        prefix := 'DEV';
    END IF;

    SELECT COALESCE(MAX(
        CAST(SUBSTRING(application_number FROM '[0-9]{6}$') AS INTEGER)
    ), 0) + 1 INTO seq_num
    FROM applications
    WHERE application_number LIKE prefix || '-' || year_str || '-' || month_str || '-%';

    NEW.application_number := prefix || '-' || year_str || '-' || month_str || '-' || LPAD(seq_num::TEXT, 6, '0');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMIT;

