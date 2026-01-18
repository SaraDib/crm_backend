<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Views
        DB::unprepared("
            CREATE OR REPLACE VIEW customer_summary_view AS
            SELECT 
                c.id, c.company_id, c.customer_number, c.type, c.first_name, c.last_name, c.company_name, c.email, c.phone, c.status,
                (SELECT COUNT(*) FROM invoices WHERE customer_id = c.id) as total_invoices,
                (SELECT SUM(total_amount) FROM invoices WHERE customer_id = c.id) as total_invoice_amount,
                (SELECT SUM(amount) FROM payments WHERE customer_id = c.id) as total_paid_amount,
                (SELECT MAX(sent_at) FROM communications WHERE customer_id = c.id) as last_communication_at,
                (SELECT COUNT(*) FROM devices WHERE customer_id = c.id) as total_devices
            FROM customers c;
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW invoice_summary_view AS
            SELECT 
                i.id, i.company_id, i.invoice_number, i.invoice_date, i.due_date, i.total_amount, i.status,
                c.first_name, c.last_name, c.company_name,
                DATEDIFF(i.due_date, CURDATE()) as days_until_due
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id;
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW communication_summary_view AS
            SELECT 
                com.company_id, com.type, com.status, COUNT(*) as total_count,
                YEAR(com.sent_at) as year, MONTH(com.sent_at) as month
            FROM communications com
            WHERE com.sent_at IS NOT NULL
            GROUP BY com.company_id, com.type, com.status, YEAR(com.sent_at), MONTH(com.sent_at);
        ");

        // 2. Stored Functions
        DB::unprepared("DROP FUNCTION IF EXISTS generate_customer_number;");
        DB::unprepared("
            CREATE FUNCTION generate_customer_number(comp_id BIGINT) RETURNS VARCHAR(50)
            DETERMINISTIC
            BEGIN
                DECLARE next_val INT;
                SELECT COUNT(*) + 1 INTO next_val FROM customers WHERE company_id = comp_id;
                RETURN CONCAT('CUST-', LPAD(next_val, 6, '0'));
            END;
        ");

        DB::unprepared("DROP FUNCTION IF EXISTS generate_invoice_number;");
        DB::unprepared("
            CREATE FUNCTION generate_invoice_number(comp_id BIGINT) RETURNS VARCHAR(50)
            DETERMINISTIC
            BEGIN
                DECLARE next_val INT;
                SELECT COUNT(*) + 1 INTO next_val FROM invoices WHERE company_id = comp_id;
                RETURN CONCAT('INV-', YEAR(CURDATE()), '-', LPAD(next_val, 6, '0'));
            END;
        ");

        // 3. Stored Procedures
        DB::unprepared("DROP PROCEDURE IF EXISTS update_invoice_status;");
        DB::unprepared("
            CREATE PROCEDURE update_invoice_status(IN inv_id BIGINT)
            BEGIN
                DECLARE total_paid DECIMAL(15,2);
                DECLARE total_inv DECIMAL(15,2);
                
                SELECT SUM(amount) INTO total_paid FROM payment_allocations WHERE invoice_id = inv_id;
                SELECT total_amount INTO total_inv FROM invoices WHERE id = inv_id;
                
                IF total_paid >= total_inv THEN
                    UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE id = inv_id;
                ELSEIF total_paid > 0 THEN
                    UPDATE invoices SET status = 'partial' WHERE id = inv_id;
                END IF;
            END;
        ");

        DB::unprepared("DROP PROCEDURE IF EXISTS send_invoice_reminders;");
        DB::unprepared("
            CREATE PROCEDURE send_invoice_reminders()
            BEGIN
                INSERT INTO reminders (company_id, customer_id, invoice_id, title, type, priority, reminder_date, status, created_at, updated_at)
                SELECT company_id, customer_id, id, CONCAT('Payment Reminder: ', invoice_number), 'invoice', 'high', CURDATE(), 'pending', NOW(), NOW()
                FROM invoices
                WHERE status NOT IN ('paid', 'canceled') 
                AND due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND due_reminder_sent = 0;
                
                UPDATE invoices 
                SET due_reminder_sent = 1 
                WHERE status NOT IN ('paid', 'canceled') 
                AND due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY);
            END;
        ");

        DB::unprepared("DROP PROCEDURE IF EXISTS daily_maintenance;");
        DB::unprepared("
            CREATE PROCEDURE daily_maintenance()
            BEGIN
                UPDATE invoices 
                SET status = 'overdue' 
                WHERE status NOT IN ('paid', 'canceled', 'overdue') AND due_date < CURDATE();
                
                CALL send_invoice_reminders();
            END;
        ");

        // 4. Triggers
        DB::unprepared("DROP TRIGGER IF EXISTS update_customer_last_contacted;");
        DB::unprepared("
            CREATE TRIGGER update_customer_last_contacted
            AFTER INSERT ON communications
            FOR EACH ROW
            BEGIN
                UPDATE customers SET last_contacted_at = NEW.sent_at WHERE id = NEW.customer_id;
            END;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS update_customer_purchase_stats;");
        DB::unprepared("
            CREATE TRIGGER update_customer_purchase_stats
            AFTER UPDATE ON invoices
            FOR EACH ROW
            BEGIN
                IF NEW.status = 'paid' AND OLD.status != 'paid' THEN
                    UPDATE customers 
                    SET last_purchase_at = NEW.invoice_date,
                        total_purchases = total_purchases + NEW.total_amount
                    WHERE id = NEW.customer_id;
                END IF;
            END;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS generate_customer_number_trigger;");
        DB::unprepared("
            CREATE TRIGGER generate_customer_number_trigger
            BEFORE INSERT ON customers
            FOR EACH ROW
            BEGIN
                IF NEW.customer_number IS NULL OR NEW.customer_number = '' THEN
                    SET NEW.customer_number = generate_customer_number(NEW.company_id);
                END IF;
            END;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS generate_invoice_number_trigger;");
        DB::unprepared("
            CREATE TRIGGER generate_invoice_number_trigger
            BEFORE INSERT ON invoices
            FOR EACH ROW
            BEGIN
                IF NEW.invoice_number IS NULL OR NEW.invoice_number = '' THEN
                    SET NEW.invoice_number = generate_invoice_number(NEW.company_id);
                END IF;
            END;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS log_invoice_status_change;");
        DB::unprepared("
            CREATE TRIGGER log_invoice_status_change
            AFTER UPDATE ON invoices
            FOR EACH ROW
            BEGIN
                IF OLD.status != NEW.status THEN
                    INSERT INTO activity_logs (company_id, user_id, activity_type, description, related_type, related_id, created_at)
                    VALUES (NEW.company_id, NEW.updated_by, 'invoice_status_change', CONCAT('Invoice ', NEW.invoice_number, ' status changed from ', OLD.status, ' to ', NEW.status), 'invoice', NEW.id, NOW());
                END IF;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS log_invoice_status_change;");
        DB::unprepared("DROP TRIGGER IF EXISTS generate_invoice_number_trigger;");
        DB::unprepared("DROP TRIGGER IF EXISTS generate_customer_number_trigger;");
        DB::unprepared("DROP TRIGGER IF EXISTS update_customer_purchase_stats;");
        DB::unprepared("DROP TRIGGER IF EXISTS update_customer_last_contacted;");
        DB::unprepared("DROP PROCEDURE IF EXISTS daily_maintenance;");
        DB::unprepared("DROP PROCEDURE IF EXISTS send_invoice_reminders;");
        DB::unprepared("DROP PROCEDURE IF EXISTS update_invoice_status;");
        DB::unprepared("DROP FUNCTION IF EXISTS generate_invoice_number;");
        DB::unprepared("DROP FUNCTION IF EXISTS generate_customer_number;");
        DB::unprepared("DROP VIEW IF EXISTS communication_summary_view;");
        DB::unprepared("DROP VIEW IF EXISTS invoice_summary_view;");
        DB::unprepared("DROP VIEW IF EXISTS customer_summary_view;");
    }
};
