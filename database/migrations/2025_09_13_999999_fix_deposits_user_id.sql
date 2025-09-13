-- Update all existing deposits to use the correct user_id (numeric)
-- This SQL assumes user_id currently stores mobile_number

UPDATE deposits d
JOIN users u ON d.user_id = u.mobile_number
SET d.user_id = u.id
WHERE d.user_id = u.mobile_number;
