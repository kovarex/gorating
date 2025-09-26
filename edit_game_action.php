<?php
require_once("src/process_rating_update_helper.php");
if (!canEditGames())
  die("No permission to edit games.");

$game = query("SELECT * FROM game WHERE id=".escape($_POST["id"]))->fetch_assoc();
if (!$game)
  die("Game to update doesn't exist.");

$originalWinnerID = $game["winner_user_id"];
$originalLoserID = $game["loser_user_id"];
$originalWinnerIsBlack = $game["winner_is_black"];

if ($_POST["original_winner"] != "winner")
{
  $winnerID = $originalLoserID;
  $loserID = $originalWinnerID;
  $winnerIsBlack = !$originalWinnerIsBlack;
}
else
{
  $winnerID = $originalWinnerID;
  $loserID = $originalLoserID;
  $winnerIsBlack = $originalWinnerIsBlack;
}

if (($_POST["original_winner_color"] == "black") != $originalWinnerIsBlack)
  $winnerIsBlack = !$winnerIsBlack;

query("UPDATE
         game
       SET
         winner_user_id=".escape($winnerID).",".
        "loser_user_id=".escape($loserID).",".
        "winner_is_black=".($winnerIsBlack ? "true" : "false").",".
        "handicap=".escape($_POST["handicap"]).",".
        "komi=".escape($_POST["komi"]).
       " WHERE game.id=".$_POST["id"]);

$message = "Game with id=".$_POST["id"]." updated:<br/>\n";
if ($originalWinnerID != $winnerID)
  $message = "Winner was changed.<br/>\n";
if (($originalWinnerIsBlack != $winnerIsBlack) != ($originalWinnerID != $winnerID))
  $message = "Black color player was changed.<br/>\n";
$message .= processRating(50);
redirectWithMessage($message);
?>
