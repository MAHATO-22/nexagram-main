ALTER TABLE `likes`
  ADD COLUMN `reaction` varchar(16) NOT NULL DEFAULT '❤️' AFTER `post_id`;
