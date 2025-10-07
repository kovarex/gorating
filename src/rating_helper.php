<?php

function beta($rating)
{
  return -7 * log(3300 - $rating);
}

function calculateNewRating($rating, $opponentRating, $result, $gameType, $extraHandicap, $extraKomi)
{
  $adjustedRating = $rating + 100 * ($extraHandicap * RATING_RANKS_PER_HANDICAP + $extraKomi * RATING_RANKS_PER_KOMI);

  $Se = 1.0 / (1.0 + exp(beta($opponentRating)- beta($adjustedRating))); // for the probability of winning, the adjusted rating is used
  $con = pow(((3300 - $rating) / 200), 1.6); // for constant I use the normal rating, so playing handicap doesn't lower/enlarge the gain.
  $bonus = log(1 + exp((2300 - $rating) / 80)) / 5; // for bonus also the normal rating
  return $rating + GAME_TYPE_RATING_MODIFIER[$gameType] * ($con * ($result - $Se) + $bonus);
}

function createJsFunctionToCalculateRating()
{
  $result = "var RATING_RANKS_PER_HANDICAP = ".RATING_RANKS_PER_HANDICAP.";\n";
  $result .= "var RATING_RANKS_PER_KOMI = ".RATING_RANKS_PER_KOMI.";\n";
  $result .= "var GAME_TYPE_RATING_MODIFIER = [];\n";
  for ($i = 1; $i <= GAME_TYPE_COUNT; $i++)
    $result .= "GAME_TYPE_RATING_MODIFIER[".$i."] = ".GAME_TYPE_RATING_MODIFIER[$i].";\n";
  $result .= "\n";
  $result .= "function beta(rating)\n";
  $result .= "{\n";
  $result .= "  return -7 * Math.log(3300 - rating)\n";
  $result .= "}\n";
  $result .= "function calculateAdjustedRating(rating, extraHandicap, extraKomi)\n";
  $result .= "{\n";
  $result .= "  return rating + 100 * (extraHandicap * RATING_RANKS_PER_HANDICAP + extraKomi * RATING_RANKS_PER_KOMI);\n";
  $result .= "}\n";
  $result .= "\n";
  $result .= "// result 1 is win, and 0 is loss\n";
  $result .= "function calculateNewRating(rating, opponentRating, result, gameType, extraHandicap, extraKomi)\n";
  $result .= "{\n";
  $result .= "  let Se = 1.0 / (1.0 + Math.exp(beta(opponentRating) - beta(calculateAdjustedRating(rating, extraHandicap, extraKomi))));\n";
  $result .= "  let con = Math.pow(((3300 - rating) / 200), 1.6);\n";
  $result .= "  let bonus = Math.log(1 + Math.exp((2300 - rating) / 80)) / 5;\n";
  $result .= "  return rating + GAME_TYPE_RATING_MODIFIER[gameType] * (con * (result - Se) + bonus);\n";
  $result .= "}\n";
  return $result;
}

function rankFromRating($rating)
{
  // I need some internal number for rank representation, better than the textual "18k" etc, so it is just going to be integer like this
  // 30k   = rating [-950, -850) = rank  1
  // "20k" = rating [  50,  150) = rank 11
  // "10k" = rating [1050, 1150) = rank 21
  // 1 k   = rating [1950, 2050) = rank 30
  // 1 d   = rating [2050, 2150) = rank 31
  // 7 d   = rating [2650, 2750) = rank 37
  // 8 d   = rating [2750, 2850) = rank 38
  // I would start by 30 from 8d upper, but lets keep it simple for now
    return floor(max(($rating + 1050) / 100, 1));
}

function middleRatingFromRank($rank)
{
  return ($rank - 10) * 100;
}

function readableRank($rank)
{
  if ($rank <= 30)
    return strval(31 - $rank)."k";
  return strval($rank - 30)."D";
}

function playerNameWithRank($nameOrArray, $ratingOrValuePrefix = null)
{
  if (!is_array($nameOrArray))
    return $nameOrArray." ".readableRank(rankFromRating($ratingOrValuePrefix));
  if (array_key_exists(($ratingOrValuePrefix ?: "")."rating", $nameOrArray))
    $rating = $nameOrArray[($ratingOrValuePrefix ?: "")."rating"];
  if (!isset($rating))
    $rating = $nameOrArray[($ratingOrValuePrefix ?: "")."egd_rating"];
  return $nameOrArray[($ratingOrValuePrefix ?: "")."name"]." ".readableRank(rankFromRating($rating));
}

function getRatingJumpReason($expectedRating,
                             $reportedRating,
                             $firstRoundOfTheTournament,
                             $lastTournamentRatingStart,
                             $lastTournamentRatingEnd)
{
  if ($reportedRating > 2700 and $reportedRating == floor(($reportedRating - 2700) / 30) * 30 + 2700)
    return RATING_CHANGE_TYPE_MANUAL_EGD_RANK_PROMOTION; // pro ranks

  if (fmod($reportedRating, 100) != 0)
    return RATING_CHANGE_TYPE_ERROR;

  if (!$firstRoundOfTheTournament)
    return RATING_CHANGE_TYPE_ERROR;

  if (!isset($lastTournamentRatingStart))
    return RATING_CHANGE_TYPE_ERROR;

  if ($reportedRating == (floor($lastTournamentRatingEnd / 100) + 1) * 100)
  {
    $lastTournamentStartingRank = rankFromRating($lastTournamentRatingStart);
    $lastTournamentEndingRank = rankFromRating($lastTournamentRatingEnd);
    if (($lastTournamentEndingRank - $lastTournamentStartingRank) >= 2)
      return RATING_CHANGE_TYPE_AUTOMATIC_EGD_RATING_RANK_RESET_NEW;
    $lastTournamentStartingRankOldStyle = rankFromRating($lastTournamentRatingStart + 50);
    $lastTournamentEndingRankOldStyle = rankFromRating($lastTournamentRatingEnd + 50);
    if ($lastTournamentEndingRankOldStyle - $lastTournamentStartingRankOldStyle >= 2)
      return RATING_CHANGE_TYPE_AUTOMATIC_EGD_RATING_RANK_RESET_OLD;
  }

  return RATING_CHANGE_TYPE_MANUAL_EGD_RANK_PROMOTION;
}

function showRating($rating)
{
  $significantDigits = isset($_SESSION["user"]["setting_significant_digits_in_rating"]) ? $_SESSION["user"]["setting_significant_digits_in_rating"] : 0;
  return sprintf("%.".$significantDigits."f", $rating);
}

function showRatingChange($oldRating, $newRating)
{
  $myResultName = $oldRating < $newRating ? "winner" : "loser";
  if (isset($_SESSION["user"]["setting_rating_change_format"]))
    $ratingChangeFormat = $_SESSION["user"]["setting_rating_change_format"];
  else
    $ratingChangeFormat = 1;

  if ($ratingChangeFormat == 1)
    return "<span class=\"".$myResultName."\">".showRating($oldRating)."&rarr;".showRating($newRating)."</span>";

  $delta = $newRating - $oldRating;
  $significantDigits = isset($_SESSION["user"]["setting_significant_digits_in_rating"]) ? $_SESSION["user"]["setting_significant_digits_in_rating"] : 0;
  if (abs($delta) < 1 and $significantDigits < 1)
    $significantDigits = 1;

  if (abs($delta) < 0.1 and $significantDigits < 2)
    $significantDigits = 2;

  if (abs($delta) < 0.01 and $significantDigits < 3)
    $significantDigits = 3;

  return "<span class=\"".$myResultName."\">".($delta > 0 ? "+" : "").sprintf("%.".$significantDigits."f", $delta)."</span>&nbsp;".showRating($newRating);
}

function showHandicap($handicap, $komi)
{
  if ($handicap == 0 and ($komi == 6.5 or $komi == 7.5))
    return "Even";

  if ($handicap == 0)
    return "komi ".$komi;

  return $handicap."h".($komi == 0.5 ? " " : (" komi ".$komi));
}

function fetchUserRatingBeforeFromUser($user,
                                       $userID,
                                       $timestamp = NULL,
                                       $tournamentID = NULL,
                                       $lastResortGorFromTournament = NULL,
                                       $egdOnly = false)
{
  $lastGame = query("SELECT".
                    "  IF(game.winner_user_id=".$userID.", game.winner_new_rating, game.loser_new_rating) as rating,\n".
                    "  IF(game.winner_user_id=".$userID.", game.winner_new_egd_rating, game.loser_new_egd_rating) as egd_rating\n".
                    "FROM\n".
                    "  game\n".
                    "WHERE\n".
                    "  (game.winner_user_id=".escape($userID)." or game.loser_user_id=".escape($userID).")\n".
                    ($timestamp ? (" and  game.timestamp < ".escape($timestamp)."\n") : "").
                    ($tournamentID ? " and (game.egd_tournament_id is null or game.egd_tournament_id != ".escape($tournamentID).")\n" : "").
                    ($egdOnly ? " and IF(game.winner_user_id=".$userID.", game.winner_new_egd_rating, game.loser_new_egd_rating) is not null \n" : "").
                   " ORDER BY game.timestamp DESC, game.egd_tournament_round DESC \n".
                    "LIMIT 1")->fetch_assoc();
  if (!$lastGame)
  {
    if ($user["register_rating"])
      return $user["register_rating"];
    return $lastResortGorFromTournament;
  }
  if (!$egdOnly and isset($lastGame["rating"]))
    return $lastGame["rating"];
  return $lastGame["egd_rating"];
}

function fetchUserRatingBefore($userID, $timestamp, $tournamentID, $lastResortGorFromTournament = NULL)
{
  return fetchUserRatingBeforeFromUser(query("SELECT * FROM user where id=".escape($userID))->fetch_assoc(), $userID, $timestamp, $tournamentID, $lastResortGorFromTournament);
}

function updateFinalRating($userID)
{
  $user = query("SELECT * FROM user where id=".escape($userID))->fetch_assoc();
  $rating = fetchUserRatingBeforeFromUser($user, $userID, NULL /* timestamp */, NULL /* tournamentID */, NULL /* last resort */, false /* egdOnly */);
  $egdRating = fetchUserRatingBeforeFromUser($user, $userID, NULL /* timestamp */, NULL /* tournamentID */, NULL /* last resort */, true /* egdOnly */);
  if ($user["rating"] != $rating or $user["egd_rating"] != $egdRating)
    query("UPDATE user SET  rating=".escape($rating).",egd_rating=".escape($egdRating)." WHERE user.id=".escape($userID));
}

?>
