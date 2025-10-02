<?php
require_once("src/link_helper.php");
$userID = @$_GET["id"];
if ($userID)
{
  $user = query("SELECT * FROM user WHERE id=".escape($userID))->fetch_assoc();
  if (!$user)
    return "User with id=\"".$userID."\" doesn't exist.";
}
else
{
  $username = @$_GET["username"];
  if ($username)
  {
    $user = query("SELECT * FROM user WHERE username=".escape($username))->fetch_assoc();
    if (!$user)
      return "User with username=\"".$username."\" doesn't exist.";
    $userID = $user["id"];
  }
  else
    return "User id nor username provided";
}

$games = query("SELECT
                IF(game.winner_user_id=".escape($userID).", game.winner_old_egd_rating, game.loser_old_egd_rating) as old_egd_rating,
                IF(game.winner_user_id=".escape($userID).", game.winner_new_egd_rating, game.loser_new_egd_rating) as new_egd_rating,
                egd_tournament.egd_key as egd_tournament_key,
                game.egd_tournament_round as egd_tournament_round
               FROM
                 game LEFT JOIN egd_tournament ON game.egd_tournament_id=egd_tournament.id
               WHERE
                 game.deleted = false AND
                 (game.winner_user_id=".escape($userID)." or game.loser_user_id=".escape($userID).") AND
                 winner_old_egd_rating IS NOT NULL
               ORDER BY
                 game.timestamp, game.egd_tournament_id, game.egd_tournament_round");
while ($row = $games->fetch_assoc())
{
  $egdOld = $row["old_egd_rating"];
  $egdNew = $row["new_egd_rating"];
  $egdTournamentKey = $row["egd_tournament_key"];
  if (!isset($lastEgdTournamentKey) or $egdTournamentKey != $lastEgdTournamentKey)
  {
    if (isset($tournamentRatingStart))
      $lastTournamentRatingStart = $tournamentRatingStart;
    if (isset($egdRating))
      $lastTournamentRatingEnd = $egdRating;
    $tournamentRatingStart = $egdOld;
    $firstRoundOfTheTournament = true;
    $lastEgdTournamentKey = $egdTournamentKey;
  }
  if (isset($egdRating))
  {
    $tournamentKey = $row["egd_tournament_key"];
    if (abs($egdRating - $egdOld) > 0.01)
    {
      echo "Rating jumped from ".$egdRating." to ".$egdOld." in tournament ".egdTournamentLink($tournamentKey)." round ".$row["egd_tournament_round"];
      if ($previousTournamentKey and $row["egd_tournament_round"] == 1)
        echo " (previous tournament ".egdTournamentLink($previousTournamentKey).
             ", rating: ".$lastTournamentRatingStart."->".$lastTournamentRatingEnd.
             ", rank: ".readableRank(rankFromRating($lastTournamentRatingStart))."->".readableRank(rankFromRating($lastTournamentRatingEnd)).")";
      echo "<br/>\n";

      echo "&nbsp;&nbsp;&nbsp;".getRatingJumpReason($egdRating,
                                                    $egdOld,
                                                    $firstRoundOfTheTournament,
                                                    $lastTournamentRatingStart,
                                                    $lastTournamentRatingEnd)."<br/>\n";
    }
    $previousTournamentKey = $tournamentKey;
  }
  $egdRating = $egdNew;
  $firstRoundOfTheTournament = false;
}
?>
