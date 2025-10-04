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

if (!empty($_FILES["sgf"] and !empty($_FILES["sgf"]["tmp_name"])))
  $sgf = file_get_contents($_FILES['sgf']['tmp_name']);

query("UPDATE
         game
       SET
         winner_user_id=".escape($winnerID).",".
        "loser_user_id=".escape($loserID).",".
        "winner_is_black=".($winnerIsBlack ? "true" : "false").",".
        "handicap=".escape($_POST["handicap"]).",".
        "komi=".escape($_POST["komi"]).",".
        "location=".escape($_POST["location"]).
        (isset($sgf) ? (", sgf=".escape($sgf)) : "").
       " WHERE game.id=".$_POST["id"], true);

$message = "Game id=".$_POST["id"].":<br/>\n";
if ($originalWinnerID != $winnerID)
  $message .= "Winner was changed.<br/>\n";
if (($originalWinnerIsBlack != $winnerIsBlack) != ($originalWinnerID != $winnerID))
  $message .= "Black color player was changed.<br/>\n";
if ($game["handicap"] != $_POST["handicap"])
  $message .= "Handicap was changed from ".$game["handicap"]." to ".$_POST["handicap"]."<br/>\n";
if ($game["komi"] != $_POST["komi"])
  $message .= "Komi was changed from ".$game["komi"]." to ".$_POST["komi"]."<br/>\n";
if (isset($sgf))
  $message .= "SGF was ".(empty($game["sgf"]) ? "added" : "updated").".<br/>\n";
$message .= processRating(50);
redirectWithMessage($message);
?>
