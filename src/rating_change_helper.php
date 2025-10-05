<?php
require_once("link_helper.php");
require_once("process_tournament_helper.php");
function checkUserRatingChanges($userID)
{
  beginTransaction();
  echo "user_id=".$userID."<br/>\n";
  $games = query("SELECT
                    IF(game.winner_user_id=".escape($userID).", game.winner_old_egd_rating, game.loser_old_egd_rating) as old_egd_rating,
                    IF(game.winner_user_id=".escape($userID).", game.winner_new_egd_rating, game.loser_new_egd_rating) as new_egd_rating,
                    egd_tournament.egd_key as egd_tournament_key,
                    game.egd_tournament_round as egd_tournament_round,
                    egd_tournament.timestamp as timestamp,
                    NULL as rating_change_id
                  FROM
                    game LEFT JOIN egd_tournament ON game.egd_tournament_id=egd_tournament.id
                  WHERE
                    game.deleted = false AND
                    (game.winner_user_id=".escape($userID)." or game.loser_user_id=".escape($userID).") AND
                    winner_old_egd_rating IS NOT NULL
                  UNION ALL
                  SELECT
                    rating_change.old_egd_rating as old_egd_rating,
                    rating_change.new_egd_rating as new_egd_rating,
                    'z' as egd_tournament_key,
                    NULL as egd_tournament_round,
                    rating_change.timestamp as timestamp,
                    rating_change.id as rating_change_id
                  FROM
                    rating_change
                  WHERE
                    rating_change.user_id=".escape($userID)."
                  ORDER BY
                    timestamp, egd_tournament_key, egd_tournament_round");
  while ($row = $games->fetch_assoc())
  {
    $egdOld = $row["old_egd_rating"];
    $egdNew = $row["new_egd_rating"];
    $egdTournamentKey = $row["egd_tournament_key"];
    $thisIsJump = !empty($row["rating_change_id"]);
    $lastWasJump = false;
    if (!isset($lastEgdTournamentKey) or $egdTournamentKey != $lastEgdTournamentKey)
    {
      if (isset($tournamentRatingStart))
        $lastTournamentRatingStart = $tournamentRatingStart;
      if (!$lastWasJump)
      {
        if (isset($egdRating))
        {
          $lastTournamentRatingEnd = $egdRating;
          if (isset($tournamentTimestamp))
            $lastTournamentTimestamp = $tournamentTimestamp;
        }
      }
      if (!$thisIsJump)
      {
        $tournamentRatingStart = $egdOld;
        $tournamentTimestamp = $row["timestamp"];
        $firstRoundOfTheTournament = true;
      }
      $lastEgdTournamentKey = $egdTournamentKey;
      $lastWasJump = $thisIsJump;
    }
    if (isset($egdRating))
    {
      $tournamentKey = $row["egd_tournament_key"];
      if (abs($egdRating - $egdOld) > 0.01)
      {
        if (!$thisIsJump)
        {
          echo "Rating jumped from ".$egdRating." to ".$egdOld." in tournament ".egdTournamentLink($tournamentKey)." round ".$row["egd_tournament_round"];
          if (isset($previousTournamentKey) and $row["egd_tournament_round"] == 1)
            echo " (previous tournament ".egdTournamentLink($previousTournamentKey).
                 ", rating: ".$lastTournamentRatingStart."->".$lastTournamentRatingEnd.
                 ", rank: ".readableRank(rankFromRating($lastTournamentRatingStart))."->".readableRank(rankFromRating($lastTournamentRatingEnd)).")";
          echo "<br/>\n";

          if (isset($lastTournamentRatingStart))
            $jumpType = getRatingJumpReason($egdRating,
                                            $egdOld,
                                            $firstRoundOfTheTournament,
                                            $lastTournamentRatingStart,
                                            $lastTournamentRatingEnd);
          if (!isset($jumpType) or $jumpType == RATING_CHANGE_TYPE_ERROR)
          {
            echo "Since this looks like an error, recomputing ";
            if (isset($previousTournamentKey))
            {
              processTournament($previousTournamentKey);
              echo $previousTournamentKey." ";
            }
            processTournament($tournamentKey);
            echo $tournamentKey."<br/>\n";
            rollbackTransaction();
            return false;
          }

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
        else
          query("UPDATE rating_change SET old_egd_rating=".escape($egdOld)." WHERE id=".escape($row["rating_change_id"]));
      }
      $previousTournamentKey = $tournamentKey;
    }
    $egdRating = $egdNew;
    $firstRoundOfTheTournament = false;
  }
  query("UPDATE user SET rating_change_checked=true WHERE id=".escape($userID));
  commitTransaction();
  return true;
}
?>
