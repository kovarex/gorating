<?php
$userID = @$_GET["id"];
if (!$userID)
  return "User id not provided";
$user = query("SELECT * FROM user WHERE id=".escape($userID))->fetch_assoc();
if (!$user)
  return "User with id=\"".$userID."\" doesn't exist.";
$games = query("SELECT
                IF(winner.id=".escape($userID).", game.winner_old_egd_rating, game.loser_old_egd_rating) as old_egd_rating,
                IF(winner.id=".escape($userID).", game.winner_new_egd_rating, game.loser_new_egd_rating) as new_egd_rating
               FROM
                 game,
                 user as winner,
                 user as loser
               WHERE
                 game.deleted = false AND
                 game.winner_user_id = winner.id AND
                 game.loser_user_id = loser.id AND
                 (winner_user_id=".escape($_GET["id"])." or loser_user_id=".escape($_GET["id"]).") AND
                 winner_old_egd_rating IS NOT NULL
               ORDER BY
                 timestamp, egd_tournament_id, egd_tournament_round", true);
while ($row = $games->fetch_assoc())
{
  $egdOld = $row["old_egd_rating"];
  $egdNew = $row["new_egd_rating"];
  if (isset($egdRating))
  {
    if (abs($egdRating - $egdOld) > 0.01)
      echo "Rating jumped from ".$egdRating." to ".$egdOld."<br/>\n";
  }
  $egdRating = $egdNew;
}
?>
