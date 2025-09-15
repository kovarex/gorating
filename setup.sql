CREATE TABLE IF NOT EXISTS `country` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name`),
  UNIQUE KEY (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb3 AUTO_INCREMENT=1;

INSERT INTO `country` (`name`, `code`) VALUES
('Czechia', 'CZ'),
('Germany', 'DE'),
('Poland', 'PL'),
('Slovakia', 'SK'),
('Belgium', 'BE'),
('Bulgaria', 'BK'),
('Denmark', 'DK'),
('Estonia', 'EE'),
('Ireland', 'IE'),
('Greece', 'EL'),
('Spain', 'ES'),
('France', 'FR'),
('Croatia', 'HR'),
('Italy', 'IT'),
('Cyprus', 'CY'),
('Latvia', 'LV'),
('Lithuania', 'LT'),
('Luxembourg', 'LU'),
('Hungary', 'HU'),
('Malta', 'MT'),
('Netherlands', 'NL'),
('Austria', 'AT'),
('Portugal', 'PT'),
('Romania', 'RO'),
('Slovenia', 'SI'),
('Finland', 'FI'),
('Sweden', 'SE'),
('United Kingdom', 'UK'),
('United States', 'US');

CREATE TABLE IF NOT EXISTS `game` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `winner_user_id` int unsigned NOT NULL,
  `loser_user_id` int unsigned NOT NULL,
  `game_type_id` int unsigned NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `location` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `winner_comment` varchar(128) DEFAULT NULL,
  `loser_comment` varchar(128) DEFAULT NULL,
  `sgf` blob,
  `winner_old_rating` double NOT NULL,
  `winner_new_rating` double NOT NULL,
  `loser_old_rating` double NOT NULL,
  `loser_new_rating` double NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `winner_user_id` (`winner_user_id`),
  INDEX `loser_user_id` (`loser_user_id`),
  INDEX `game_type_id` (`game_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `game_type` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
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
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `from_user_id` int unsigned NOT NULL,
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

CREATE TABLE IF NOT EXISTS `admin_level` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `description` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb3;

INSERT INTO `admin_level` (`id`, `name`, `description`) VALUES
(1, 'Owner', 'Can do anything'),
(2, 'Admin', 'Anything but promoting/demoting admins'),
(3, 'Mod', 'Full access to invites'),
(4, 'Trusted user', 'Can insert any game results on its own'),
(5, 'User', 'Can only insert his losses'),
(6, 'Unregistered', 'Player who didn''t register but is present as opponent from EGD (or other) database.');

CREATE TABLE IF NOT EXISTS `user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `egd_pin` int DEFAULT NULL,
  `egd_rating` double DEFAULT NULL,
  `rating` double NOT NULL,
  `country_id` int unsigned NOT NULL,
  `password` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_level_id` int unsigned NOT NULL,
  `invited_by_user_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (username),
  UNIQUE KEY (email),
  UNIQUE KEY (egd_pin),
  INDEX `invited_by_user_id` (`invited_by_user_id`),
  INDEX `country_id` (`country_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- first user is inserted manually
INSERT INTO `user` (`id`, `username`, `first_name`, `last_name`, `email`, `egd_pin`, `egd_rating`, `rating`, `country_id`, `password`, admin_level_id) VALUES
(1, 'kovarex', 'Michal', 'Kovařík', 'kovarex@gmail.com', 13050378, 2206, 2206, 1, '$2y$10$OdqeMM4K7QwUsDRC38y1yezecOsHNi7wQj5T8EPNclZ.KWHLrkEMK', 1);

ALTER TABLE `game`
  ADD CONSTRAINT `game_fk_1` FOREIGN KEY (`game_type_id`) REFERENCES `game_type` (`id`),
  ADD CONSTRAINT `game_fk_2` FOREIGN KEY (`winner_user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `game_fk_3` FOREIGN KEY (`loser_user_id`) REFERENCES `user` (`id`);
  
ALTER TABLE `user`
  ADD CONSTRAINT `user_fk_1` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`),
  ADD CONSTRAINT `user_fk_2` FOREIGN KEY (`admin_level_id`) REFERENCES `admin_level` (`id`),
  ADD CONSTRAINT `user_fk_3` FOREIGN KEY (`invited_by_user_id`) REFERENCES `user` (`id`);

ALTER TABLE `invite`
  ADD CONSTRAINT `invite_fk_1` FOREIGN KEY (`from_user_id`) REFERENCES `user` (`id`);
