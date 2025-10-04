<?php
require_once("link_helper.php");
function checkUserRatingChanges($userID)
{
  beginTransaction();
  $games = query("SELECT
                IF(game.winner_user_id=".escape($userID).", game.winner_old_egd_rating, game.loser_old_egd_rating) as old_egd_rating,
                IF(game.winner_user_id=".escape($userID).", game.winner_new_egd_rating, game.loser_new_egd_rating) as new_egd_rating,
                egd_tournament.egd_key as egd_tournament_key,
                game.egd_tournament_round as egd_tournament_round,
                egd_tournament.timestamp as tournament_timestamp
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
      {
        $lastTournamentRatingEnd = $egdRating;
        $lastTournamentTimestamp = $tournamentTimestamp;
      }
      $tournamentRatingStart = $egdOld;
      $tournamentTimestamp = $row["tournament_timestamp"];
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
        query("INSERT INTO
                 rating_change(old_egd_rating,
                               new_egd_rating,
                               user_id,
                               timestamp,
                               rating_change_type_id)
                 VALUES(".escape($egdRating).",".
                          escape($egdOld).",".
                          escape($userID).",".
                          escape($lastTournamentTimestamp).",".
                          getRatingJumpReason($egdRating,
                                              $egdOld,
                                              $firstRoundOfTheTournament,
                                              $lastTournamentRatingStart,
                                              $lastTournamentRatingEnd).")");
      }
      $previousTournamentKey = $tournamentKey;
    }
    $egdRating = $egdNew;
    $firstRoundOfTheTournament = false;
  }
  query("UPDATE user SET rating_changes_checked=true WHERE id=".escape($userID));
  commitTransaction();
}
?>
