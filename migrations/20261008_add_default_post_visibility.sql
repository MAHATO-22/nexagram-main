ALTER TABLE `users`
  ADD COLUMN `default_visibility` enum('public','followers','friends','only_me') NOT NULL DEFAULT 'public' AFTER `school_name`;
