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

function readableRank($rank)
{
  if ($rank <= 30)
    return strval(31 - $rank)."k";
  return strval($rank - 30)."D";
}

function playerNameWithRank($nameOrArray, $rating = null)
{
  if (!is_array($nameOrArray))
    return $nameOrArray." ".readableRank(rankFromRating($rating));
  return $nameOrArray["name"]." ".readableRank(rankFromRating($nameOrArray["rating"]));
}

?>
