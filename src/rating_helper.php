<?php
function modifier($gameType)
{
  switch ($gameType)
  {
    case GAME_TYPE_EGD_A: return 1;
    case GAME_TYPE_EGD_B: return 0.75;
    case GAME_TYPE_EGD_C: return 0.5;
    case GAME_TYPE_EGD_D: return 0.25;
    case GAME_TYPE_SERIOUS: return 0.5;
    case GAME_TYPE_RAPID: return 0.25;
    case GAME_TYPE_BLITZ: return 0.1;
    default: die("Unexpected game type:".$gameType);
  }
}

function beta($rating)
{
  return -7 * log(3300 - $rating);
}

function calculateNewRating($rating, $opponentRating, $result, $gameType, $extraHandicap, $extraKomi)
{
  // the effective handicap rating is just my approximation
  // the idea is, that 10 extra points would be one rank, and one handicap stone would be 0.8 of rank
  $adjustedRating = $rating + 100 * (($extraHandicap * 0.8) + ($extraKomi / 10.0));

  $Se = 1.0 / (1.0 + exp(beta($opponentRating)- beta($adjustedRating))); // for the probability of winning, the adjusted rating is used
  $con = pow(((3300 - $rating) / 200), 1.6); // for constant I use the normal rating, so playing handicap doesn't lower/enlarge the gain.
  $bonus = log(1 + exp((2300 - $rating) / 80)) / 5; // for bonus also the normal rating
  return $rating + modifier($gameType) * ($con * ($result - $Se) + $bonus);
}
?>
