-- Performance indexes for homepage optimization

-- Composite index for users queries (user_type_id + status + ordering)
ALTER TABLE users ADD INDEX idx_user_type_status (user_type_id, status, created_at);
ALTER TABLE users ADD INDEX idx_user_type_status_joining (user_type_id, status, joining_date);

-- Index for courses status queries
ALTER TABLE courses ADD INDEX idx_courses_status (status);

-- Index for experience_years ordering (faculty)
ALTER TABLE users ADD INDEX idx_experience_years (experience_years);

