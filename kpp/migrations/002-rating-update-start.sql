-- start_rating_update (setup.sql), called by the game table's triggers when a game is deleted,
-- restored or edited, with what the PHP version's did wrong fixed (kovarex, 2026-11-03: the
-- recomputation has to agree with how ratings are computed in the first place):
-- - it read the variable rating_update_finished, which does not exist, so a change made while an
--   update was in progress moved the update's start to that change's time, even a later one, and
--   the games between were left as they were;
-- - it started nothing when the game's winner had no gorating rating (an EGD player who is not
--   registered), even when the loser had one: the loser's later games were not recomputed.
DROP PROCEDURE IF EXISTS start_rating_update;
DELIMITER //
CREATE PROCEDURE start_rating_update(user1_id int unsigned, user1_rating double, user2_id int unsigned, user2_rating double, start_timestamp timestamp)
BEGIN
  DECLARE in_progress boolean;
  DECLARE current_start timestamp;

  IF user1_rating IS NOT NULL OR user2_rating IS NOT NULL THEN
    SELECT value = '1' FROM variable WHERE name='rating_update_in_progress' INTO in_progress;

    UPDATE variable SET value = CAST(value AS unsigned) + 1 WHERE name='rating_update_version';
    IF user1_rating IS NOT NULL THEN
      CALL add_or_update_user_rating_update_value(user1_id, user1_rating, start_timestamp);
    END IF;
    IF user2_rating IS NOT NULL THEN
      CALL add_or_update_user_rating_update_value(user2_id, user2_rating, start_timestamp);
    END IF;

    IF in_progress THEN
      SELECT STR_TO_DATE(value, '%Y-%m-%d %H:%i:%s') FROM variable WHERE name='rating_update_timestamp' INTO current_start;
      UPDATE variable SET value = DATE_FORMAT(LEAST(current_start, start_timestamp), '%Y-%m-%d %H:%i:%s') WHERE name='rating_update_timestamp';
    ELSE
      UPDATE variable SET value = DATE_FORMAT(start_timestamp, '%Y-%m-%d %H:%i:%s') WHERE name='rating_update_timestamp';
      UPDATE variable SET value = '1' WHERE name='rating_update_in_progress';
    END IF;
  END IF;
END //
DELIMITER ;
