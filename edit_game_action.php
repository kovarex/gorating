<?php
require_once("src/process_rating_update_helper.php");
$game = query("SELECT * FROM game WHERE id=".escape($_POST["id"]))->fetch_assoc();
if (!$game)
  die("Game to update doesn't exist.");

if (!canEditAnyGame())
{
  if ($game["winner_user_id"] != userID() and $game["loser_user_id"] != userID())
    die("Can't edit this game.");
  if (!canEditMyGameSince($game["timestamp"]))
    die("You can't edit older than a day, ask a mod to edit it for you.");
}

$originalWinnerID = $game["winner_user_id"];
$originalLoserID = $game["loser_user_id"];
$originalWinnerIsBlack = $game["winner_is_black"];

if (isset($_POST["original_winner"]) and $_POST["original_winner"] != "winner")
{
  $winnerID = $originalLoserID;
  $loserID = $originalWinnerID;
  $winnerIsBlack = !$originalWinnerIsBlack;
  $winnerSwitched = true;
}
else
{
  $winnerID = $originalWinnerID;
  $loserID = $originalLoserID;
  $winnerIsBlack = $originalWinnerIsBlack;
  $winnerSwitched = false;
}

if ($winnerSwitched and !canEditWinner())
  redirectWithMessage("Can't edit winner.");

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
        ($winnerSwitched ? (", winner_old_egd_rating=".escape($game["loser_old_egd_rating"]).
                            ", winner_old_rating=".escape($game["loser_old_rating"]).
                            ", winner_new_egd_rating=".escape($game["loser_old_egd_rating"]).
                            ", winner_new_rating=".escape($game["loser_old_rating"]).
                            ", loser_old_egd_rating=".escape($game["winner_old_egd_rating"]).
                            ", loser_old_rating=".escape($game["winner_old_rating"]).
                            ", loser_new_egd_rating=".escape($game["winner_new_egd_rating"]).
                            ", loser_new_rating=".escape($game["winner_new_rating"])) : "").
        (isset($sgf) ? (", sgf=".escape($sgf)) : ($_POST["delete-sgf"] ? ",sgf=null" : "")).
       " WHERE game.id=".$_POST["id"]);

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
else if ($_POST["delete-sgf"] and $game["sgf"])
  $message .= "SGF was removed<br/>\n";
$message .= processRating(50);
//echo $message;
redirectWithMessage($message);
?>
