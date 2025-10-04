<?php
require_once("rating_helper.php");

function getRatingToUse($rating, $ratingTimestmap, $gameRating, $gameRatingTimestamp)
{
  if ($rating == NULL)
  {
    assert($gameRating != NULL);
    return $gameRating;
  }
  if ($gameRating == NULL)
  {
    assert($rating != NULL);
    return $rating;
  }

  return $ratingTimestmap < $gameRatingTimestamp ? $rating : $gameRating;
}

function reportRatingFinishedResults()
{
  $data = query("SELECT
                   user.id as id,
                   user.name as name,
                   user.username as username,
                   user.rating as old_value,
                   rating_update_value.rating as rating
                 FROM
                   rating_update_value JOIN user ON rating_update_value.user_id=user.id");
  $result = "<table class=\"data-table\">";
  $result .= "<caption>Rating changes</caption>";
  $result .= "<tr><th>Name</th><th>Rating change</th></tr>";
  while ($row = $data->fetch_assoc())
    $result .= "<tr><td>".playerLink($row)."</td><td>".showRatingChange($row["old_value"], $row["rating"])."</td></tr>";
  $result .= "</table>";
  return $result;
}

function processRating($iterationCount)
{
  beginTransaction();
  $ratingUpdateInProgress = query("SELECT value from variable WHERE name='rating_update_in_progress'")->fetch_assoc()['value'] == '1';
  if (!$ratingUpdateInProgress)
    return "No rating changes to process.";

  $result = "";
  for ($i = 0; $i < $iterationCount; $i++)
  {
    $ratingUpdateVersion = query("SELECT value from variable WHERE name='rating_update_version'")->fetch_assoc()['value'];
    $ratingUpdateTimestamp = query("SELECT value from variable WHERE name='rating_update_timestamp'")->fetch_assoc()['value'];

    $gameToProcess = query("SELECT
                              game_id,
                              winner_user_id as winner_user_id,
                              loser_user_id as loser_user_id,
                              MAX(winner_rating) as winner_rating,
                              MAX(winner_rating_timestamp) as winner_rating_timestamp,
                              game_winner_rating as game_winner_rating,
                              MAX(loser_rating) as loser_rating,
                              MAX(loser_rating_timestamp) as loser_rating_timestamp,
                              game_loser_rating as game_loser_rating,
                              game_timestamp as game_timestamp,
                              game_type_id as game_type_id,
                              game_handicap as game_handicap,
                              game_komi as game_komi,
                              game_winner_is_black as game_winner_is_black
                            FROM
                            (SELECT
                              game.id as game_id,
                              game.winner_user_id as winner_user_id,
                              rating_update_value.rating as winner_rating,
                              rating_update_value.timestamp as winner_rating_timestamp,
                              game.winner_old_rating as game_winner_rating,

                              game.loser_user_id as loser_user_id,
                              NULL as loser_rating,
                              NULL as loser_rating_timestamp,
                              game.loser_old_rating as game_loser_rating,
                              game.timestamp as game_timestamp,
                              game.game_type_id as game_type_id,
                              game.handicap as game_handicap,
                              game.komi as game_komi,
                              game.winner_is_black as game_winner_is_black
                            FROM
                              rating_update_value JOIN game ON rating_update_value.user_id=game.winner_user_id
                            WHERE
                              game.deleted = false and
                              game.rating_update_version < ".$ratingUpdateVersion." and
                              game.timestamp >= '".$ratingUpdateTimestamp."'

                            UNION

                            SELECT
                              game.id as game_id,
                              game.winner_user_id as winner_user_id,
                              NULL as winner_rating,
                              NULL as winner_rating_timestamp,
                              game.winner_old_rating as game_winner_rating,

                              game.loser_user_id as loser_user_id,
                              rating_update_value.rating as loser_rating,
                              rating_update_value.timestamp as loser_rating_timestamp,
                              game.loser_old_rating as game_loser_rating,
                              game.timestamp as game_timestamp,
                              game.game_type_id as game_type_id,
                              game.handicap as game_handicap,
                              game.komi as game_komi,
                              game.winner_is_black as game_winner_is_black
                            FROM
                              rating_update_value JOIN game ON rating_update_value.user_id=game.loser_user_id
                            WHERE
                              game.deleted = false and
                              game.rating_update_version < ".$ratingUpdateVersion." and
                              game.timestamp >= '".$ratingUpdateTimestamp."'
                            ORDER BY game_timestamp) as tmp
                            GROUP BY game_id
                            LIMIT 1");
    $row = $gameToProcess->fetch_assoc();
    if (!$row)
    {
      $result .= "Rating update finished<br/><br/>\n\n";
      $result .= reportRatingFinishedResults();
      query("UPDATE user JOIN rating_update_value ON rating_update_value.user_id=user.id SET user.rating=rating_update_value.rating");
      query("DELETE FROM rating_update_value");
      query("update variable SET value='0' WHERE name='rating_update_in_progress'");
      commitTransaction();
      return $result;
    }

    $gameTimestamp = $row["game_timestamp"];
    $gameType = $row["game_type_id"];

    $winnerOldRating = getRatingToUse($row["winner_rating"], $row["winner_rating_timestamp"], $row["game_winner_rating"], $gameTimestamp);
    $loserOldRating = getRatingToUse($row["loser_rating"], $row["loser_rating_timestamp"], $row["game_loser_rating"], $gameTimestamp);

    $winnerIsBlack = $row["game_winner_is_black"];

    $winnerExtraHandicap = ($winnerIsBlack ? 1 : -1) * $row["game_handicap"];
    $winnerExtraKomi = ($winnerIsBlack ? -1 : 1) * ($row["game_komi"] - 6.5);

    $winnerNewRating = calculateNewRating($winnerOldRating, $loserOldRating, 1.0, $gameType, $winnerExtraHandicap, $winnerExtraKomi);
    $loserNewRating = calculateNewRating($loserOldRating, $winnerOldRating, 0.0, $gameType, -$winnerExtraHandicap, -$winnerExtraKomi);
    if (true)
      echo "Loser(id=".$row["loser_user_id"]."):".$loserOldRating."->".$loserNewRating." extraHandicap:".(-$winnerExtraHandicap)." extraKomi:".(-$winnerExtraKomi)."<br/>\n";
    $winnerId = $row["winner_user_id"];
    $loserId = $row["loser_user_id"];

    query("UPDATE
            game
           SET
             winner_old_rating=".$winnerOldRating.",
             winner_new_rating=".$winnerNewRating.",
             loser_old_rating=".$loserOldRating.",
             loser_new_rating=".$loserNewRating.",
             rating_update_version=".$ratingUpdateVersion."
           WHERE
             game.id=".$row["game_id"]);
    query("CALL add_or_force_update_user_rating_update_value(".$winnerId.",".$winnerNewRating.",STR_TO_DATE('".date("Y-m-d H:i:s", strtotime($gameTimestamp))."','%Y-%m-%d %H:%i:%s'))");
    query("CALL add_or_force_update_user_rating_update_value(".$loserId.",".$loserNewRating.",STR_TO_DATE('".date("Y-m-d H:i:s", strtotime($gameTimestamp))."','%Y-%m-%d %H:%i:%s'))");
    $result .= "Processed game ".$row["game_id"]."<br/>\n";
  }
  commitTransaction();
  return $result;
}
?>
