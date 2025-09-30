CREATE TABLE `country` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(64) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name` (`name`),
  UNIQUE INDEX `code` (`code`)
  INDEX `id_code` (`id`, `code`)
) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;

INSERT INTO country (name, code) VALUES
('Czechia', 'CZ'),('Germany', 'DE'),('Poland', 'PL'),('Slovakia', 'SK'),('Belgium', 'BE'),('Bulgaria', 'BG'),
('Denmark', 'DK'),('Estonia', 'EE'),('Ireland', 'IE'),('Greece', 'GR'),('Spain', 'ES'),('France', 'FR'),
('Croatia', 'HR'),('Italy', 'IT'),('Cyprus', 'CY'),('Latvia', 'LV'),('Lithuania', 'LT'),('Luxembourg', 'LU'),
('Hungary', 'HU'),('Malta', 'MT'),('Netherlands', 'NL'),('Austria', 'AT'),('Portugal', 'PT'),('Romania', 'RO'),
('Slovenia', 'SI'),('Finland', 'FI'),('Sweden', 'SE'),('United Kingdom', 'UK'),('United States', 'US'),
('Bosnia and Herzegovina', 'BA'),('Republic of Serbia', 'RS'),('Armenia', 'AM'),('Azerbaijan', 'AZ'),
('Europe', 'EU'),('Israel', 'IL'),('Iceland', 'IS'),('Kyrgyzstan', 'KG'),('Kazakhstan', 'KZ'),('Moldova', 'MD'),
('North Makedonia', 'MK'),('Russia', 'RU'),('Turkey', 'TR'),('Ukraine', 'UA'),('Yugoslavia', 'YU'),('Andora', 'AD'),
('Switzerland', 'CH'),('Norway', 'NO'),('Georgia', 'GE'),('Belarus', 'BY'),('Undefined', 'XX'),('Canada', 'CA'),
('Korea', 'KR'),('Japan', 'JP'),('China', 'CN'),('Taiwan', 'TW'),('Philippines', 'PH'),('Cuba', 'CU'),('Chile', 'CL'),
('Colombia', 'CO'),('Costa Rica', 'CR'),('Thailand', 'TH'),('Ecuador', 'EC'),('Argentina', 'AR'),('Mongolia', 'MN'),
('Uzbekistan', 'UZ'),('Venezuela', 'VE'),('Brunei Darussalam', 'BN'),('Morocco', 'MA'),('Brazil', 'BR'),
('Iran', 'IR'),('Singapore', 'SG'),('Mexico', 'MX'),('Indonesia', 'ID'),('Uruguay', 'UY'),('Australia', 'AU'),
('New Zeland', 'NZ'),('South Africa', 'ZA'),('Viet Nam', 'VN'),('India', 'IN'),('Guatemala', 'GT'),
('North Korea', 'KP'),('Hong Kong', 'HK'),('Malaysia', 'MY'),('Macao', 'MO'),('Syria', 'SY'),('Dominican Republic', 'DO'),
('Lao', 'LA'),('Panama', 'PA'),('Peru', 'PE'),('Nepal', 'NP'),('Madagascar', 'MG'),('French Guiana', 'GF'),('San Marino', 'SM');

CREATE TABLE `game_type` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(64) NOT NULL,
  `egd` BOOLEAN NOT NULL,
  PRIMARY KEY (`id`)
) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;

INSERT INTO game_type (id, name, egd) VALUES
(1, 'EGD - class A', true),(2, 'EGD - class B', true),(3, 'EGD - class C', true),(4, 'EGD - class D', TRUE),(5, 'Serious', false),(6, 'Rapid', false),(7, 'Blitz', false);

CREATE TABLE `admin_level` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(64) NOT NULL,
  `description` VARCHAR(128) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `id_name` (`id`, `name`)
) COLLATE='utf8mb4_unicode_ci' ENGINE=INNODB;

INSERT INTO `admin_level` (id, `name`, `description`) VALUES
(1, 'Owner', 'Can do anything'),
(2, 'Admin', 'Anything but promoting/demoting admins'),
(3, 'Mod', 'Full access to invites'),
(4, 'Trusted user', 'Can insert any game results on its own'),
(5, 'User', 'Can only insert his losses'),
(6, 'Unregistered', 'Player who didn''t register but is present as opponent from EGD (or other) database.');

CREATE TABLE `user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NULL DEFAULT NULL,
  `first_name` VARCHAR(64) NOT NULL,
  `last_name` VARCHAR(64) NOT NULL,
  `email` VARCHAR(256) NULL DEFAULT NULL,
  `egd_pin` INT NULL DEFAULT NULL,
  `egd_rating` DOUBLE NULL DEFAULT NULL,
  `rating` DOUBLE NOT NULL,
  `country_id` INT UNSIGNED NOT NULL,
  `password` VARCHAR(512) NULL DEFAULT NULL,
  `admin_level_id` INT UNSIGNED NOT NULL,
  `invited_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `register_timestamp` TIMESTAMP NULL DEFAULT NULL,
  `club` VARCHAR(10) NULL DEFAULT 'xxx',
  `win_count` INT UNSIGNED NOT NULL DEFAULT '0',
  `loss_count` INT UNSIGNED NOT NULL DEFAULT '0',
  `egd_win_count` INT UNSIGNED NOT NULL DEFAULT '0',
  `egd_loss_count` INT UNSIGNED NOT NULL DEFAULT '0',
  `reset_password_secret` INT UNSIGNED NULL DEFAULT NULL,
  `password_password_timestamp` TIMESTAMP NULL DEFAULT NULL,
  `name` VARCHAR(128) GENERATED ALWAYS AS CONCAT(first_name, ' ', last_name) STORED,
  `overall_game_count` INT UNSIGNED GENERATED ALWAYS AS (win_count + loss_count + egd_win_count + egd_loss_count) STORED,
  `overall_win_count` INT UNSIGNED GENERATED ALWAYS AS (win_count + egd_win_count) STORED,
  `overall_loss_count` INT UNSIGNED GENERATED ALWAYS AS (loss_count + egd_loss_count) STORED,
  `game_count` INT UNSIGNED GENERATED ALWAYS AS (win_count + loss_count) STORED,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username` (`username`),
  UNIQUE INDEX `email` (`email`),
  UNIQUE INDEX `egd_pin` (`egd_pin`),
  INDEX `invited_by_user_id` (`invited_by_user_id`),
  INDEX `country_id` (`country_id`),
  INDEX `admin_level_id` (`admin_level_id`),
  INDEX `win_count` (`win_count),
  INDEX `loss_count` (`loss_count),
  INDEX `egd_win_count` (`egd_win_count),
  INDEX `egd_loss_count` (`egd_loss_count),
  INDEX `overall_game_count` (`overall_game_count`),
  INDEX `overall_win_count` (`overall_win_count`),
  INDEX `overall_loss_count` (`overall_loss_count`),
  INDEX `game_count` (`game_count`),
  INDEX `name` (`name`),
  FULLTEXT INDEX `first_name` (`first_name`),
  FULLTEXT INDEX `last_name` (`last_name`),
  FULLTEXT INDEX `name_fulltext` (`name`),
  FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  FOREIGN KEY (`admin_level_id`) REFERENCES `admin_level` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  FOREIGN KEY (`invited_by_user_id`) REFERENCES `user` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
)COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;

CREATE TABLE `invite` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_user_id` INT UNSIGNED NOT NULL,
  `user_id` INT unsigned NULL DEFAULT NULL,
  `first_name` VARCHAR(64) NOT DEFAULT NULL,
  `last_name` VARCHAR(64) NOT DEFAULT NULL,
  `email` VARCHAR(256) NOT NULL,
  `secret` INT NOT NULL,
  `rating` DOUBLE NULL DEFAULT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP),
  PRIMARY KEY (`id`),
  INDEX `from_user_id` (`from_user_id`),
  INDEX `user_id` (`user_id`),
  FOREIGN KEY (`from_user_id`) REFERENCES `user` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) COLLATE='utf8mb4_unicode_ci'ENGINE=INNODB;

CREATE TABLE `egd_tournament` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `egd_key` VARCHAR(10) NOT NULL,
  `name` VARCHAR(128) NULL DEFAULT NULL,
  `player_count` INT UNSIGNED NOT NULL,
  `round_count` INT UNSIGNED NOT NULL,
  `timestamp` TIMESTAMP NOT NULL,
  `country_id` INT UNSIGNED NOT NULL,
  `game_type_id` INT UNSIGNED NOT NULL,
  `city` VARCHAR(64) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `egd_key` (`egd_key`),
  INDEX `timestamp` (`timestamp`),
  INDEX `country_id` (`country_id`),
  INDEX `game_type_id` (`game_type_id`),
  FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  FOREIGN KEY (`game_type_id`) REFERENCES `game_type` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) COLLATE='utf8mb4_unicode_ci' ENGINE=INNODB;

CREATE TABLE `egd_tournament_result` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `egd_tournament_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `placement` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `egd_tournament_id_2` (`egd_tournament_id`, `placement`),
  UNIQUE INDEX `egd_tournament_id_3` (`egd_tournament_id`, `user_id`),
  INDEX `egd_tournament_id` (`egd_tournament_id`, `user_id`, `placement`),
  INDEX `user_id` (`user_id`),
  FOREIGN KEY (`egd_tournament_id`) REFERENCES `egd_tournament` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) COLLATE='utf8mb4_unicode_ci' ENGINE=INNODB;

CREATE TABLE `egd_tournament_to_process` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `egd_key` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `egd_key` (`egd_key`)
) COLLATE='utf8mb4_unicode_ci' ENGINE=INNODB;

CREATE TABLE `user_game_count_to_update` (
  `user_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;

CREATE TABLE `game` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `winner_user_id` INT UNSIGNED NOT NULL,
  `loser_user_id` INT UNSIGNED NOT NULL,
  `game_type_id` INT UNSIGNED NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP),
  `location` VARCHAR(64) NULL DEFAULT NULL,
  `winner_comment` VARCHAR(128) NULL DEFAULT NULL,
  `loser_comment` VARCHAR(128) NULL DEFAULT NULL,
  `sgf` BLOB NULL DEFAULT NULL,
  `winner_old_rating` DOUBLE NULL DEFAULT NULL,
  `winner_new_rating` DOUBLE NULL DEFAULT NULL,
  `loser_old_rating` DOUBLE NULL DEFAULT NULL,
  `loser_new_rating` DOUBLE NULL DEFAULT NULL,
  `winner_old_egd_rating` DOUBLE NULL DEFAULT NULL,
  `winner_new_egd_rating` DOUBLE NULL DEFAULT NULL,
  `loser_old_egd_rating` DOUBLE NULL DEFAULT NULL,
  `loser_new_egd_rating` DOUBLE NULL DEFAULT NULL,
  `rating_update_version` INT UNSIGNED NULL DEFAULT '0',
  `winner_is_black` BOOLEAN NOT NULL DEFAULT 1,
  `handicap` INT NOT NULL DEFAULT '0',
  `komi` DOUBLE NOT NULL DEFAULT '6.5',
  `egd_tournament_id` INT UNSIGNED NULL DEFAULT NULL,
  `egd_tournament_round` INT UNSIGNED NULL DEFAULT NULL,
  `jigo` BOOLEAN NOT NULL DEFAULT FALSE,
  `deleted` boolean DEFAULT false NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `winner_user_id` (`winner_user_id`),
  INDEX `loser_user_id` (`loser_user_id`),
  INDEX `timestamp` (`timestamp`),
  INDEX `game_type_id` (`game_type_id`),
  INDEX `egd_tournament_id` (`egd_tournament_id`),
  INDEX `egd_tournament_round` (`egd_tournament_round`),
  CONSTRAINT `game_ibfk_1` FOREIGN KEY (`winner_user_id`) REFERENCES `user` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `game_ibfk_2` FOREIGN KEY (`loser_user_id`) REFERENCES `user` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `game_ibfk_3` FOREIGN KEY (`game_type_id`) REFERENCES `game_type` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `game_ibfk_4` FOREIGN KEY (`egd_tournament_id`) REFERENCES `egd_tournament` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) COLLATE='utf8mb4_unicode_ci' ENGINE=INNODB;

CREATE TABLE `variable` (
  `name` VARCHAR(64) NOT NULL,
  `value` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`name`)
) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;

INSERT INTO `variable` (`name`, `value`) VALUES
('rating_update_in_progress', '0'),
('rating_update_timestamp', '1975-01-01'),
('rating_update_version', '0');

CREATE TABLE `rating_update_value` (
  `user_id` int unsigned NOT NULL,
  `rating` double NOT NULL,
  `timestamp` timestamp NOT NULL,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;

DROP TRIGGER IF EXISTS game_after_insert;
DELIMITER //
CREATE TRIGGER `game_after_insert` AFTER INSERT ON `game`
 FOR EACH ROW
 BEGIN
   CALL update_user_game_count(NEW.winner_user_id);
   CALL update_user_game_count(NEW.loser_user_id);
 END; //
DELIMITER ;

DROP TRIGGER IF EXISTS game_after_delete;
DELIMITER //
CREATE TRIGGER `game_after_delete` AFTER DELETE ON `game`
 FOR EACH ROW
 BEGIN
   CALL update_user_game_count(OLD.winner_user_id);
   CALL update_user_game_count(OLD.loser_user_id);
   CALL start_rating_update(OLD.winner_user_id, OLD.winner_old_rating, OLD.loser_user_id, OLD.loser_old_rating, OLD.timestamp);
 END; //
DELIMITER ;

DROP TRIGGER IF EXISTS game_after_update;
DELIMITER //
CREATE TRIGGER `game_after_update` AFTER UPDATE ON `game`
 FOR EACH ROW
 BEGIN
   IF OLD.winner_user_id != NEW.winner_user_id THEN
     CALL update_user_game_count(OLD.winner_user_id);
     CALL update_user_game_count(NEW.winner_user_id);
   END IF;
   IF OLD.loser_user_id != NEW.loser_user_id THEN
     CALL update_user_game_count(OLD.loser_user_id);
     CALL update_user_game_count(NEW.loser_user_id);
   END IF;
   IF OLD.loser_user_id != NEW.loser_user_id or
      OLD.winner_user_id != NEW.winner_user_id or
      OLD.handicap != NEW.handicap or
      OLD.komi != NEW.komi or
      OLD.winner_is_black != NEW.winner_is_black THEN
      CALL start_rating_update(OLD.winner_user_id, OLD.winner_old_rating, OLD.loser_user_id, OLD.loser_old_rating, OLD.timestamp);
   END IF;

   IF OLD.deleted != NEW.deleted THEN
     CALL update_user_game_count(OLD.winner_user_id);
     CALL start_rating_update(OLD.winner_user_id, OLD.winner_old_rating, OLD.loser_user_id, OLD.loser_old_rating, OLD.timestamp);
     IF OLD.winner_user_id != NEW.winner_user_id THEN
       CALL update_user_game_count(NEW.winner_user_id);
     END IF;
     CALL update_user_game_count(OLD.loser_user_id);
     IF OLD.loser_user_id != NEW.loser_user_id THEN
       CALL update_user_game_count(NEW.loser_user_id);
     END IF;
   END IF;
 END; //
DELIMITER ;

DROP PROCEDURE IF EXISTS update_user_game_count;
DELIMITER //
CREATE PROCEDURE update_user_game_count (local_user_id int unsigned)
BEGIN
  DECLARE local_win_count int unsigned;
  DECLARE local_loss_count int unsigned;
  DECLARE local_egd_win_count int unsigned;
  DECLARE local_egd_loss_count int unsigned;

  SELECT COUNT(*) INTO local_win_count
  FROM game
  JOIN game_type ON game.game_type_id=game_type.id
  WHERE
    game.deleted = false and
    game.winner_user_id = local_user_id and
    game_type.egd = false;

  SELECT COUNT(*) INTO local_loss_count
  FROM game
  JOIN game_type ON game.game_type_id=game_type.id
  WHERE
    game.deleted = false and
    game.loser_user_id = local_user_id and
    game_type.egd = false;

  SELECT COUNT(*) INTO local_egd_win_count
  FROM game
  JOIN game_type ON game.game_type_id=game_type.id
  WHERE
    game.deleted = false and
    game.winner_user_id = local_user_id and
    game_type.egd = true;

  SELECT COUNT(*) INTO local_egd_loss_count
  FROM game
  JOIN game_type ON game.game_type_id=game_type.id
  WHERE
    game.deleted = false and
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

DROP PROCEDURE IF EXISTS process_user_game_count_to_update;
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

DROP PROCEDURE IF EXISTS add_or_update_user_rating_update_value;
DELIMITER //
CREATE PROCEDURE add_or_update_user_rating_update_value(my_user_id int unsigned, user_rating double, change_timestamp timestamp)
BEGIN
  DECLARE existing_timestamp timestamp;
  SELECT `timestamp` FROM rating_update_value WHERE user_id=my_user_id INTO existing_timestamp;
  IF existing_timestamp IS NULL THEN
    INSERT INTO rating_update_value(user_id, rating, timestamp) VALUES(my_user_id, user_rating, change_timestamp);
  ELSE
    IF change_timestamp < existing_timestamp THEN
      UPDATE rating_update_value SET rating = user_rating, timestamp = change_timestamp WHERE user_id = my_user_id;
    END IF;
  END IF;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS add_or_force_update_user_rating_update_value;
DELIMITER //
CREATE PROCEDURE add_or_force_update_user_rating_update_value(my_user_id int unsigned, user_rating double, change_timestamp timestamp)
BEGIN
  DECLARE existing_timestamp timestamp;
  SELECT `timestamp` FROM rating_update_value WHERE user_id=my_user_id INTO existing_timestamp;
  IF existing_timestamp IS NULL THEN
    INSERT INTO rating_update_value(user_id, rating, timestamp) VALUES(my_user_id, user_rating, change_timestamp);
  ELSE
    UPDATE rating_update_value SET rating = user_rating, timestamp = change_timestamp WHERE user_id = my_user_id;
  END IF;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS start_rating_update;
DELIMITER //
CREATE PROCEDURE start_rating_update(user1_id int unsigned, user1_rating double, user2_id int unsigned, user2_rating double, start_timestamp timestamp)
BEGIN
  DECLARE last_id int unsigned;
  DECLARE last_timestamp timestamp;
  DECLARE user1_rating_timestamp timestamp;
  DECLARE user2_rating_timestamp timestamp;
  DECLARE rating_update_in_progress boolean;
  DECLARE textual_timestamp_of_current_rating_update varchar(64);
  DECLARE timestamp_of_current_rating_update timestamp;

  IF user1_rating IS NOT NULL THEN
    SELECT value = '1' as in_progress FROM variable WHERE name='rating_update_finished' INTO rating_update_in_progress;

    UPDATE variable SET value = CAST(value AS unsigned) + 1 WHERE name='rating_update_version';
    CALL add_or_update_user_rating_update_value(user1_id, user1_rating, start_timestamp);
    CALL add_or_update_user_rating_update_value(user2_id, user2_rating, start_timestamp);

    IF rating_update_in_progress = true THEN
      SELECT value FROM variable WHERE name='rating_update_timestamp' INTO textual_timestamp_of_current_rating_update;
      SET timestamp_of_current_rating_update = STR_TO_DATE(textual_timestamp_of_current_rating_update,'%Y-%m-%d %H:%i:%s');
      UPDATE variable SET value = DATE_FORMAT(LEAST(timestamp_of_current_rating_update, start_timestamp), '%Y-%m-%d %H:%i:%s') WHERE name='rating_update_timestamp';
    ELSEIF last_id IS NULL THEN
      UPDATE variable SET value = DATE_FORMAT(start_timestamp, '%Y-%m-%d %H:%i:%s') WHERE name='rating_update_timestamp';
      UPDATE variable SET value = '1' WHERE name='rating_update_in_progress';
    END IF;
  END IF;
END //
DELIMITER ;
