ALTER TABLE `users`
  ADD COLUMN `reset_token_hash` varchar(64) DEFAULT NULL,
  ADD COLUMN `reset_token_expires` datetime DEFAULT NULL;
