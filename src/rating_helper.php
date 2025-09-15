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

function calculateNewRating($rating, $opponentRating, $result, $gameType)
{
  $Se = 1.0 / (1.0 + exp(beta($opponentRating)- beta($rating)));
  $con = pow(((3300 - $rating) / 200), 1.6);
  $bonus = log(1 + exp((2300 - $rating) / 80)) / 5;
  return $rating + modifier($gameType) * ($con * ($result - $Se) + $bonus);
}
?>
