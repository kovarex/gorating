CREATE TABLE IF NOT EXISTS `country` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb3 AUTO_INCREMENT=1 ;

INSERT INTO `country` (`id`, `name`, `code`) VALUES
(1, 'Czechia', 'CZ');

CREATE TABLE IF NOT EXISTS `game` (
  `id` int NOT NULL AUTO_INCREMENT,
  `winner_user_id` int unsigned NOT NULL,
  `loser_user_id` int unsigned NOT NULL,
  `game_type_id` int NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `location` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `winner_comment` varchar(128) DEFAULT NULL,
  `loser_comment` varchar(128) DEFAULT NULL,
  `sgf` blob,
  PRIMARY KEY (`id`),
  KEY `winner_user_id` (`winner_user_id`,`loser_user_id`,`game_type_id`),
  KEY `FK_game_game_type` (`game_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `game_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb3 AUTO_INCREMENT=1 ;

INSERT INTO `game_type` (`id`, `name`) VALUES
(1, 'EGD - class A'),
(2, 'EGD - class B'),
(3, 'EGD - class C'),
(4, 'EGD - class D'),
(5, 'Serious'),
(6, 'Rapid'),
(7, 'Blitz');

CREATE TABLE IF NOT EXISTS `invite` (
  `id` int NOT NULL AUTO_INCREMENT,
  `from_user_id` int NOT NULL,
  `egd_pin` int DEFAULT NULL,
  `first_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(256) NOT NULL,
  `secret` int NOT NULL,
  `rating` double DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `from_user_id` (`from_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `egd_pin` int DEFAULT NULL,
  `egd_rating` double DEFAULT NULL,
  `rating` double NOT NULL,
  `country_id` int NOT NULL,
  `password` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`,`email`),
  KEY `country_id` (`country_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- first user is inserted manually
INSERT INTO `user` (`id`, `username`, `first_name`, `last_name`, `email`, `egd_pin`, `egd_rating`, `rating`, `country_id`, `password`) VALUES
(1, 'kovarex', 'Michal', 'Kovařík', 'kovarex@gmail.com', 13050378, 2206, 2206, 1, '$2y$10$OdqeMM4K7QwUsDRC38y1yezecOsHNi7wQj5T8EPNclZ.KWHLrkEMK');

ALTER TABLE `game`
  ADD CONSTRAINT `game_ibfk_1` FOREIGN KEY (`game_type_id`) REFERENCES `game_type` (`id`),
  ADD CONSTRAINT `user_ibfk_2` FOREIGN KEY (`winner_user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `user_ibfk_3` FOREIGN KEY (`loser_user_id`) REFERENCES `user` (`id`);
  
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`);
