#!/bin/bash
# Compares gorating_php and gorating_kpp after the same scenario ran against each: every table a
# page writes, with the values that cannot match masked (the time a row was written, random
# secrets, password hashes, which bcrypt salts at random). Prints "databases equal" or the differences.
ENV=${GORATING_ENV:?set GORATING_ENV to the local setup: mariadb/}
M=$ENV/mariadb/bin/mariadb.exe
dump() {
  local db=$1
  $M -h127.0.0.1 -P3307 -uroot -N --default-character-set=utf8mb4 $db 2>/dev/null <<EOF
SELECT 'user', id, username, first_name, last_name, email, egd_pin, egd_rating, rating, win_count, loss_count, egd_win_count,
       egd_loss_count, country_id, password IS NOT NULL, admin_level_id, invited_by_user_id,
       IF(register_timestamp > '2026-01-01', 'new', register_timestamp), club, reset_password_secret IS NOT NULL,
       reset_password_timestamp IS NOT NULL, setting_significant_digits_in_rating, setting_rating_change_format,
       rating_change_checked, register_rating FROM user ORDER BY id;
SELECT 'game', id, winner_user_id, loser_user_id, game_type_id, IF(id > 3503, 'new', timestamp), location,
       winner_comment, loser_comment, HEX(sgf), winner_old_rating, winner_new_rating, loser_old_rating,
       loser_new_rating, winner_old_egd_rating, winner_new_egd_rating, loser_old_egd_rating,
       loser_new_egd_rating, winner_is_black, handicap, komi, egd_tournament_id, egd_tournament_round,
       rating_update_version, deleted, jigo FROM game ORDER BY id;
SELECT 'change', id, change_type_id, game_id, user_id, HEX(old_value), HEX(new_value), executed_by_user_id,
       IF(id > 40, 'new', timestamp) FROM \`change\` ORDER BY id;
SELECT 'invite', id, from_user_id, user_id, first_name, last_name, email, IF(id > 15, 'random', secret), rating,
       IF(id > 15, 'new', timestamp) FROM invite ORDER BY id;
SELECT 'rating_change', r.* FROM rating_change r ORDER BY id;
SELECT 'variable', v.* FROM variable v ORDER BY name;
SELECT 'egd_tournament', t.* FROM egd_tournament t ORDER BY id;
SELECT 'egd_tournament_result', r.* FROM egd_tournament_result r ORDER BY id;
SELECT 'egd_tournament_to_process', q.* FROM egd_tournament_to_process q ORDER BY id;
SELECT 'rating_update_value', r.* FROM rating_update_value r ORDER BY user_id;
SELECT 'user_game_count_to_update', u.* FROM user_game_count_to_update u ORDER BY user_id;
SELECT 'rating_discrepancy', game_id, user_id, kind, stored_old, stored_new, computed_old, computed_new, corrected
       FROM rating_discrepancy ORDER BY game_id, user_id, kind;
EOF
}
dump gorating_php > /tmp/db-php.txt
dump gorating_kpp > /tmp/db-kpp.txt
if cmp -s /tmp/db-php.txt /tmp/db-kpp.txt; then
  echo "databases equal ($(wc -l < /tmp/db-php.txt) rows)"
else
  echo "databases differ:"
  diff /tmp/db-php.txt /tmp/db-kpp.txt | cut -c1-${WIDTH:-300} | head -${LINES:-20}
fi
