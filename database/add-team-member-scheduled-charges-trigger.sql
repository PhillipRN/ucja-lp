BEGIN;

-- 既存トリガーを再作成するため、一旦削除
DROP TRIGGER IF EXISTS trigger_schedule_charge_on_kyc_completion ON applications;
DROP TRIGGER IF EXISTS trigger_schedule_charge_on_member_kyc_completion ON team_members;

CREATE OR REPLACE FUNCTION schedule_charge_on_kyc_completion()
RETURNS TRIGGER AS $$
DECLARE
    target_application_id UUID;
    target_amount INTEGER;
    target_scheduled_date DATE;
BEGIN
    IF NEW.kyc_status = 'completed' AND (OLD.kyc_status IS DISTINCT FROM 'completed') THEN
        
        IF TG_TABLE_NAME = 'applications' THEN
            IF NEW.card_registered = TRUE AND NEW.stripe_payment_method_id IS NOT NULL THEN
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
                    COALESCE(NEW.scheduled_charge_date, CURRENT_DATE),
                    'scheduled'
                WHERE NOT EXISTS (
                    SELECT 1 FROM scheduled_charges 
                    WHERE application_id = NEW.id AND status IN ('scheduled', 'completed')
                );
                
                NEW.application_status := 'charge_scheduled';
            END IF;
        
        ELSIF TG_TABLE_NAME = 'team_members' THEN
            IF NEW.card_registered = TRUE AND NEW.stripe_payment_method_id IS NOT NULL THEN
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

COMMIT;

