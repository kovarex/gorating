-- The kpp version's changes to the PHP version's database, run once on it (DEPLOY.md). The PHP
-- site keeps working on the changed database.

-- Invite and password reset secrets: 32 hexadecimal digits (128 random bits) instead of an int
-- (kovarex, 2026-11-03). The existing ones keep their value as text.
ALTER TABLE invite MODIFY secret VARCHAR(64) NOT NULL;
ALTER TABLE user MODIFY reset_password_secret VARCHAR(64) NULL DEFAULT NULL;

-- What processing a tournament again found different from what a game holds (kovarex, 2026-11-03:
-- "we should somehow notice it, so we can decide how to fix it later"). One row per game, player
-- and kind: 'egd' when EGD's ratings changed (the game is corrected, as PHP did), 'own' when
-- gorating's computed again differ (corrected only together with EGD's), 'added' for a game EGD
-- did not have before (stored). Found again, the row gets the latest values and last_seen.
CREATE TABLE IF NOT EXISTS rating_discrepancy (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `kind` VARCHAR(8) NOT NULL,
  `stored_old` DOUBLE NULL DEFAULT NULL,
  `stored_new` DOUBLE NULL DEFAULT NULL,
  `computed_old` DOUBLE NULL DEFAULT NULL,
  `computed_new` DOUBLE NULL DEFAULT NULL,
  `corrected` BOOLEAN NOT NULL,
  `first_seen` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `game_user_kind` (`game_id`, `user_id`, `kind`),
  INDEX `user_id` (`user_id`),
  FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;
