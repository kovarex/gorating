<?php

$db->begin_transaction();
$ratingUpdate = query("SELECT value from variable WHERE name='rating_update_in_progress'")->fetch_assoc();
if ($ratingUpdate['value'] != '1')
{
  echo "Nothing to update";
  return;
}

$gameToProcess = query("SELECT
                          game_id,
                          winner_user_id,
                          loser_user_id,
                          MAX(winner_rating) as winner_rating,
                          MAX(winner_rating_timestamp) as winner_rating_timestamp,
                          game_winner_rating,
                          MAX(loser_rating) as loser_rating,
                          MAX(loser_rating_timestamp) as loser_rating_timestamp,
                          game_loser_rating,
                          game_timestamp
                        FROM
                        (SELECT
                          game.id as game_id,
                          rating_update_values.user_id as winner_user_id,
                          rating_update_values.rating as winner_rating,
                          rating_update_values.timestamp as winner_rating_timestamp,
                          game.winner_old_rating as game_winner_rating,

                          game.loser_user_id as loser_user_id,
                          NULL as loser_rating,
                          NULL as loser_rating_timestamp,
                          game.loser_old_rating as game_loser_rating,
                          game.timestamp as game_timestamp
                        FROM
                          rating_update_values JOIN game ON rating_update_values.user_id=game.winner_user_id
                        WHERE
                          game.rating_update_version < 1 and
                          game.timestamp >= '2025-09-18 20:00'

                        UNION

                        SELECT
                          game.id as game_id,
                          game.winner_user_id as winner_user_id,
                          NULL as winner_rating,
                          NULL as winner_rating_timestamp,
                          game.winner_old_rating as game_winner_rating,
                          rating_update_values.user_id as winner_user_id,
                          rating_update_values.rating as loser_rating,
                          rating_update_values.timestamp as loser_rating_timestamp,
                          game.loser_old_rating as game_loser_rating,
                          game.timestamp as game_timestamp
                        FROM
                          rating_update_values JOIN game ON rating_update_values.user_id=game.loser_user_id
                        WHERE
                          game.rating_update_version < 1 and
                          game.timestamp >= '2025-09-18 20:00'
                        ORDER BY game_timestamp) as tmp
                        GROUP BY game_id
                        LIMIT 1");
$row = $gamesToProcess->fetch_assoc();
$winnerId = $row["winner_user_id"];
?>
