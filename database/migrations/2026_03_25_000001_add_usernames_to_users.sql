-- Add username support for email-or-username authentication.
-- Safe for existing rows: usernames are backfilled from role_name + padded user id.

ALTER TABLE `users`
  ADD COLUMN `username` VARCHAR(100) NULL AFTER `role_id`,
  ADD UNIQUE KEY `uq_users_username` (`username`);

UPDATE `users` u
INNER JOIN `roles` r ON r.`id` = u.`role_id`
SET u.`username` = CONCAT(r.`name`, '-', LPAD(u.`id`, 4, '0'))
WHERE u.`username` IS NULL
   OR u.`username` = '';
