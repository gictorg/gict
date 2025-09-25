-- Payment Workflow Database Schema Updates
-- Updates to support payment_pending status and payment review workflow

-- Update student_enrollments table to add payment_pending status
ALTER TABLE student_enrollments 
MODIFY COLUMN status ENUM('payment_pending', 'pending', 'enrolled', 'completed', 'dropped', 'rejected') DEFAULT 'payment_pending';

-- Update payments table to add payment verification fields
ALTER TABLE payments 
ADD COLUMN payment_proof_url VARCHAR(500) NULL AFTER transaction_id,
ADD COLUMN payment_verified_by INT NULL AFTER payment_proof_url,
ADD COLUMN payment_verified_at TIMESTAMP NULL AFTER payment_verified_by,
ADD COLUMN payment_notes TEXT NULL AFTER payment_verified_at,
ADD FOREIGN KEY (payment_verified_by) REFERENCES users(id) ON DELETE SET NULL;

-- Create payment_methods table for tracking payment options
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('online', 'offline') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default payment methods
INSERT INTO payment_methods (name, type, is_active) VALUES
('UPI Payment', 'online', TRUE),
('Credit/Debit Card', 'online', TRUE),
('Net Banking', 'online', TRUE),
('Cash Payment', 'offline', TRUE),
('Cheque Payment', 'offline', TRUE),
('Bank Transfer', 'offline', TRUE);

-- Create payment_verification table for tracking payment verification
CREATE TABLE payment_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    verified_by INT NOT NULL,
    verification_status ENUM('verified', 'rejected', 'pending') DEFAULT 'pending',
    verification_notes TEXT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_payments_verification ON payments(payment_verified_by);
CREATE INDEX idx_payment_verification_payment ON payment_verification(payment_id);
CREATE INDEX idx_payment_verification_admin ON payment_verification(verified_by);
CREATE INDEX idx_enrollments_payment_status ON student_enrollments(status);
