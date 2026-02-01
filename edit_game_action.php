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

beginTransaction();

query("UPDATE
         game
       SET
         winner_user_id=".escape($winnerID).",".
        "loser_user_id=".escape($loserID).",".
        "winner_is_black=".($winnerIsBlack ? "true" : "false").",".
        "handicap=".escape($_POST["handicap"]).",".
        "komi=".escape($_POST["komi"]).",".
        "location=".escape($_POST["location"]).",".
        "timestamp=".escape($_POST["timestamp"]).
        ($winnerSwitched ? (", winner_old_egd_rating=".escape($game["loser_old_egd_rating"]).
                            ", winner_old_rating=".escape($game["loser_old_rating"]).
                            ", winner_new_egd_rating=".escape($game["loser_old_egd_rating"]).
                            ", winner_new_rating=".escape($game["loser_old_rating"]).
                            ", loser_old_egd_rating=".escape($game["winner_old_egd_rating"]).
                            ", loser_old_rating=".escape($game["winner_old_rating"]).
                            ", loser_new_egd_rating=".escape($game["winner_new_egd_rating"]).
                            ", loser_new_rating=".escape($game["winner_new_rating"])) : "").
        (isset($sgf) ? (", sgf=".escape($sgf)) : (@$_POST["delete-sgf"] ? ",sgf=null" : "")).
       " WHERE game.id=".$_POST["id"]);

$message = "Game id=".$_POST["id"].":<br/>\n";
if ($originalWinnerID != $winnerID)
{
  $message .= "Winner was changed.<br/>\n";
  query("INSERT INTO ".
      "  `change`(change_type_id, game_id, executed_by_user_id)".
      "  VALUES(".escape(CHANGE_GAME_WINNER).",".escape($game["id"]).",".escape(userID()).")");
}
if (($originalWinnerIsBlack != $winnerIsBlack) != ($originalWinnerID != $winnerID))
{
  $message .= "Black color player was changed.<br/>\n";
  query("INSERT INTO ".
      "  `change`(change_type_id, game_id, executed_by_user_id)".
      "  VALUES(".escape(CHANGE_GAME_BLACK_PLAYER).",".escape($game["id"]).",".escape(userID()).")");
}
if ($game["handicap"] != $_POST["handicap"])
{
  $message .= "Handicap was changed from ".$game["handicap"]." to ".$_POST["handicap"]."<br/>\n";
  query("INSERT INTO ".
      "  `change`(change_type_id, game_id, executed_by_user_id,old_value,new_value)".
      "  VALUES(".escape(CHANGE_GAME_HANDICAP).",".escape($game["id"]).",".escape(userID()).",".escape($game["handicap"]).",".escape($_POST["handicap"]).")");
}
if ($game["komi"] != $_POST["komi"])
{
  $message .= "Komi was changed from ".$game["komi"]." to ".$_POST["komi"]."<br/>\n";
  query("INSERT INTO ".
      "  `change`(change_type_id, game_id, executed_by_user_id,old_value,new_value)".
      "  VALUES(".escape(CHANGE_GAME_KOMI).",".escape($game["id"]).",".escape(userID()).",".escape($game["komi"]).",".escape($_POST["komi"]).")");
}

if ($game["location"] != $_POST["location"])
{
  $message .= "Location was changed from ".$game["location"]." to ".$_POST["location"]."<br/>\n";
  query("INSERT INTO ".
      "  `change`(change_type_id, game_id, executed_by_user_id,old_value,new_value)".
      "  VALUES(".escape(CHANGE_GAME_LOCATION).",".escape($game["id"]).",".escape(userID()).",".escape($game["location"]).",".escape($_POST["location"]).")");
}

if ($game["timestamp"] != $_POST["timestamp"])
{
  $message .= "Timestamp was changed from ".$game["timestamp"]." to ".$_POST["timestamp"]."<br/>\n";
  query("INSERT INTO ".
      "  `change`(change_type_id, game_id, executed_by_user_id,old_value,new_value)".
      "  VALUES(".escape(CHANGE_GAME_TIMESTAMP).",".escape($game["id"]).",".escape(userID()).",".escape($game["timestamp"]).",".escape($_POST["timestamp"]).")");
}

if (isset($sgf))
{
  $message .= "SGF was ".(empty($game["sgf"]) ? "added" : "updated").".<br/>\n";
  query("INSERT INTO ".
        "  `change`(change_type_id, game_id, executed_by_user_id".(!empty($game["sgf"]) ? ",old_value" : "").",new_value)".
        "  VALUES(".escape(CHANGE_GAME_SGF).",".escape($game["id"]).",".escape(userID()).(!empty($game["sgf"]) ? ",".escape($game["sgf"]) : "").",".escape($sgf).")");
}
else if ($_POST["delete-sgf"] and $game["sgf"])
{
  $message .= "SGF was removed<br/>\n";
  query("INSERT INTO ".
      "  `change`(change_type_id, game_id, executed_by_user_id,old_value)".
      "  VALUES(".escape(CHANGE_GAME_SGF).",".escape($game["id"]).",".escape(userID()).",".escape($game["sgf"]).")");
}
$message .= processRating(50);
//echo $message;
commitTransaction();
redirectWithMessage($message);
