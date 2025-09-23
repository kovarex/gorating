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
('Bulgaria', 'BG'),
('Denmark', 'DK'),
('Estonia', 'EE'),
('Ireland', 'IE'),
('Greece', 'GR'),
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
('United States', 'US'),
('Bosnia and Herzegovina', 'BA'),
('Republic of Serbia', 'RS'),
('Armenia', 'AM'),
('Azerbaijan', 'AZ'),
('Europe', 'EU'),
('Israel', 'IL'),
('Iceland', 'IS'),
('Kyrgyzstan', 'KG'),
('Kazakhstan', 'KZ'),
('Moldova', 'MD'),
('North Makedonia', 'MK'),
('Russia', 'RU'),
('Turkey', 'TR'),
('Ukraine', 'UA'),
('Yugoslavia', 'YU'),
('Andora', 'AD'),
('Switzerland', 'CH'),
('Norway', 'NO'),
('Georgia', 'GE'),
('Belarus', 'BY'),
('Undefined', 'XX'),
('Canada', 'CA'),
('Korea', 'KR'),
('Japan', 'JP'),
('China', 'CN'),
('Taiwan', 'TW'),
('Philippines', 'PH'),
('Cuba', 'CU'),
('Chile', 'CL'),
('Colombia', 'CO'),
('Costa Rica', 'CR'),
('Thailand', 'TH'),
('Ecuador', 'EC'),
('Argentina', 'AR'),
('Mongolia', 'MN'),
('Uzbekistan', 'UZ'),
('Venezuela', 'VE'),
('Brunei Darussalam', 'BN'),
('Morocco', 'MA'),
('Brazil', 'BR'),
('Iran', 'IR'),
('Singapore', 'SG'),
('Mexico', 'MX'),
('Indonesia', 'ID'),
('Uruguay', 'UY'),
('Australia', 'AU'),
('New Zeland', 'NZ'),
('South Africa', 'ZA'),
('Viet Nam', 'VN'),
('India', 'IN'),
('Guatemala', 'GT'),
('North Korea', 'KP'),
('Hong Kong', 'HK'),
('Malaysia', 'MY'),
('Macao', 'MO'),
('Syria', 'SY'),
('Dominican Republic', 'DO'),
('Lao', 'LA'),
('Panama', 'PA'),
('Peru', 'PE'),
('Nepal', 'NP'),
('Madagascar', 'MG'),
('French Guiana', 'GF'),
('San Marino', 'SM');

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
  `winner_old_rating` double DEFAULT NULL,
  `winner_new_rating` double DEFAULT NULL,
  `loser_old_rating` double DEFAULT NULL,
  `loser_new_rating` double DEFAULT NULL,
  `winner_old_egd_rating` double DEFAULT NULL,
  `winner_new_egd_rating` double DEFAULT NULL,
  `loser_old_egd_rating` double DEFAULT NULL,
  `loser_new_egd_rating` double DEFAULT NULL,
  `last_rating_update_id` int unsigned DEFAULT NULL,
  `winner_is_black` tinyint(1) NOT NULL DEFAULT '1',
  `handicap` int NOT NULL DEFAULT '0',
  `komi` double NOT NULL DEFAULT '6.5',
  `egd_tournament_id` int unsigned DEFAULT NULL,
  `egd_tournament_round` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `winner_user_id` (`winner_user_id`),
  INDEX `loser_user_id` (`loser_user_id`),
  INDEX `timestamp` (`timestamp`),
  INDEX `game_type_id` (`game_type_id`),
  INDEX `egd_tournament_id` (`egd_tournament_id`),
  INDEX `egd_tournament_round` (`egd_tournament_round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `game_type` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `egd` boolean NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb3 AUTO_INCREMENT=1;

INSERT INTO `game_type` (`id`, `name`) VALUES
(1, 'EGD - class A', true),
(2, 'EGD - class B', true),
(3, 'EGD - class C', true),
(4, 'EGD - class D', true),
(5, 'Serious', false),
(6, 'Rapid', false),
(7, 'Blitz', false);

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
  `password` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_level_id` int unsigned NOT NULL,
  `invited_by_user_id` int unsigned DEFAULT NULL,
  `register_timestamp` timestamp NULL DEFAULT NULL,
  `club` varchar(10) DEFAULT 'xxx',
  `win_count` int unsigned NOT NULL DEFAULT 0,
  `loss_count` int unsigned NOT NULL DEFAULT 0,
  `egd_win_count` int unsigned NOT NULL DEFAULT 0,
  `egd_loss_count` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY (username),
  UNIQUE KEY (email),
  UNIQUE KEY (egd_pin),
  INDEX (invited_by_user_id),
  INDEX (country_id),
  FULLTEXT KEY (first_name),
  FULLTEXT KEY (last_name)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `egd_tournament`
(
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `egd_key` varchar(10) NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `player_count` int unsigned NOT NULL,
  `round_count` int unsigned NOT NULL,
  `timestamp` timestamp NOT NULL,
  `country_id` int unsigned NOT NULL,
  `game_type_id` int unsigned NOT NULL,
  `city` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (egd_key),
  INDEX `timestamp` (`timestamp`),
  INDEX `country_id` (`country_id`),
  INDEX `game_type_id` (`game_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `egd_tournament_result` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `egd_tournament_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `placement` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `egd_tournament_id` (`egd_tournament_id`,`user_id`,`placement`),
  UNIQUE KEY (`egd_tournament_id`, `placement`),
  UNIQUE KEY (`egd_tournament_id`, `user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `egd_tournament_to_process` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `egd_key` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `egd_key` (`egd_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_game_count_to_update`
(
  `user_id` int unsigned NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER //
CREATE PROCEDURE update_user_game_count (local_user_id int unsigned)
BEGIN
  DECLARE local_win_count int unsigned;
  DECLARE local_loss_count int unsigned;
  DECLARE local_egd_win_count int unsigned;
  DECLARE local_egd_loss_count int unsigned;

  SELECT COUNT(*) INTO local_win_count
  FROM game JOIN game_type ON game.game_type_id=game_type.id
  WHERE
    game.winner_user_id = local_user_id and
    game_type.egd = false;

  SELECT COUNT(*) INTO local_loss_count
  FROM game JOIN game_type ON game.game_type_id=game_type.id
  WHERE
    game.loser_user_id = local_user_id and
    game_type.egd = false;

  SELECT COUNT(*) INTO local_egd_win_count
  FROM game JOIN game_type ON game.game_type_id=game_type.id
  WHERE
    game.winner_user_id = local_user_id and
    game_type.egd = true;

  SELECT COUNT(*) INTO local_egd_loss_count
  FROM game JOIN game_type ON game.game_type_id=game_type.id
  WHERE
    game.loser_user_id = local_user_id and
    game_type.egd = true;

  UPDATE user SET
    win_count = local_win_count,
    loss_count = local_loss_count,
    egd_win_count = local_egd_win_count,
    egd_loss_count = local_egd_loss_count
  WHERE
    user.id = local_user_id;
  DELETE FROM user_game_count_to_update WHERE user_id = local_user_id;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE process_user_game_count_to_update()
BEGIN
  DECLARE finished INTEGER DEFAULT 0;
  DECLARE _user_id INT unsigned;
  DEClARE curlo CURSOR FOR SELECT user_id FROM user_game_count_to_update;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;
  OPEN curlo;

  getDat: LOOP
      FETCH curlo INTO _user_id;
      IF finished = 1 THEN
          LEAVE getDat;
      END IF;

      CALL update_user_game_count(_user_id);
  END LOOP getDat;
  CLOSE curlo;
END //
DELIMITER ;

ALTER TABLE `game`
  ADD CONSTRAINT `game_fk_1` FOREIGN KEY (`game_type_id`) REFERENCES `game_type` (`id`),
  ADD CONSTRAINT `game_fk_2` FOREIGN KEY (`winner_user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `game_fk_3` FOREIGN KEY (`loser_user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `game_fk_4` FOREIGN KEY (`egd_tournament_id`) REFERENCES `egd_tournament` (`id`);

ALTER TABLE `user`
  ADD CONSTRAINT `user_fk_1` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`),
  ADD CONSTRAINT `user_fk_2` FOREIGN KEY (`admin_level_id`) REFERENCES `admin_level` (`id`),
  ADD CONSTRAINT `user_fk_3` FOREIGN KEY (`invited_by_user_id`) REFERENCES `user` (`id`);

ALTER TABLE `invite`
  ADD CONSTRAINT `invite_fk_1` FOREIGN KEY (`from_user_id`) REFERENCES `user` (`id`);

ALTER TABLE `egd_tournament`
  ADD CONSTRAINT `egd_tournament_fk_1` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`),
  ADD CONSTRAINT `egd_tournament_fk_2` FOREIGN KEY (`game_type_id`) REFERENCES `game_type` (`id`);

ALTER TABLE `egd_tournament_result`
  ADD CONSTRAINT `egd_tournament_result_fk_1` FOREIGN KEY (`egd_tournament_id`) REFERENCES `egd_tournament` (`id`),
  ADD CONSTRAINT `egd_tournament_result_fk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

DELIMITER //
CREATE TRIGGER `game_after_insert` AFTER INSERT ON `game`
 FOR EACH ROW
 BEGIN
   CALL update_user_game_count(NEW.winner_user_id);
   CALL update_user_game_count(NEW.loser_user_id);
 END; //
DELIMITER ;

DELIMITER //
CREATE TRIGGER `game_after_delete` AFTER DELETE ON `game`
 FOR EACH ROW
 BEGIN
   CALL update_user_game_count(OLD.winner_user_id);
   CALL update_user_game_count(OLD.loser_user_id);
   CALL start_rating_update(OLD.winner_user_id, OLD.winner_old_rating, OLD.loser_user_id, OLD.loser_old_rating, OLD.timestamp);
   CALL update
 END; //
DELIMITER ;

DELIMITER //
CREATE TRIGGER `game_after_update` AFTER UPDATE ON `game`
 FOR EACH ROW
 BEGIN
   CALL update_user_game_count(OLD.winner_user_id);
   CALL update_user_game_count(OLD.loser_user_id);
   CALL update_user_game_count(NEW.winner_user_id);
   CALL update_user_game_count(NEW.loser_user_id);
 END; //
DELIMITER ;

CREATE TABLE IF NOT EXISTS `rating_update` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL,
  `finished` boolean DEFAULT false
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DELIMITER //
CREATE PROCEDURE start_rating_update(user1_id int unsigned, user1_rating double, user2_id int unsigned, user2_rating double, start_timestamp timestamp)
BEGIN
  DECLARE last_id int unsigned;
  DECLARE last_timestamp timestamp;
  IF user1_rating IS NOT NULL THEN
    SELECT
      `id`, `timestamp`
    FROM rating_update
    WHERE finished = false
    ORDER BY id DESC
    INTO last_id, last_timestamp;

    IF last_id IS NOT NULL THEN
      DELETE FROM rating_update_values;
      UPDATE rating_update SET timestamp = LEAST(last_timestamp, start_timestamp), id = last_id + 1 WHERE id = last_id;
    ELSEIF
      last_id IS NULL THEN INSERT INTO rating_update(timestamp) VALUES(start_timestamp);
    END IF;

    INSERT INTO
      rating_update_values(user_id, rating)
    VALUES(user1_id, user1_rating),
          (user2_id, user2_rating);
  END IF;
END //
DELIMITER ;
