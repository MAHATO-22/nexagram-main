ALTER TABLE `users`
  ADD COLUMN `language_code` varchar(20) NOT NULL DEFAULT 'ja-JP' AFTER `school_name`;
