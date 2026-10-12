-- Non-destructive migration. Run once through the CLI migration below or phpMyAdmin.
CREATE TABLE IF NOT EXISTS seat_holds (
    ticket_id INT NOT NULL PRIMARY KEY,
    user_id INT NOT NULL,
    hold_token CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    bill_id INT NULL,
    KEY hold_token (hold_token),
    KEY expires_at (expires_at),
    KEY user_id (user_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (bill_id) REFERENCES bills(bill_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS payment_expires_at DATETIME NULL;
ALTER TABLE promotions ADD COLUMN IF NOT EXISTS audience ENUM('all','new_member') NOT NULL DEFAULT 'all';
ALTER TABLE promotions ADD COLUMN IF NOT EXISTS per_user_limit INT NOT NULL DEFAULT 0;
-- Existing explicitly named welcome code: one successful/reserved use per account.
UPDATE promotions SET audience='new_member',per_user_limit=1 WHERE code='THANHVIENMOI1';
