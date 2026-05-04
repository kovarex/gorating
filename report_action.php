<?php
require_once("src/rating_helper.php");

if (empty(userID()))
{
  echo "Can't report game when not logged in.";
  return;
}

$opponent = query("SELECT * FROM user WHERE id=".escape($_POST["winner_user_id"]))->fetch_assoc();
if (empty($opponent))
{
  echo "User with id=".$_POST["winner_user_id"]." doesn't exist";
  return;
}

if ($_POST["game_type"] != GAME_TYPE_SERIOUS and
    $_POST["game_type"] != GAME_TYPE_RAPID and
    $_POST["game_type"] != GAME_TYPE_BLITZ)
{
  echo "Invalid game type:".$_POST["game_type"];
  return;
}

$me = query("SELECT * FROM user WHERE id=".escape(userID()))->fetch_assoc();

if (empty($me))
{
  echo "The record of currently logged user doesn't exist, this shouldn't happen!";
  return;
}

if (!empty($_FILES["sgf"] and !empty($_FILES["sgf"]["tmp_name"])))
  $sgf = file_get_contents($_FILES['sgf']['tmp_name']);

if (!is_numeric($_POST["handicap"]))
{
  echo "Handicap value must be numeric";
  return;
}

if ($_POST["handicap"] > 9)
{
  echo "Maximum handicap value is 9";
  return;
}

if ($_POST["handicap"] < 0)
{
  echo "Handicap can't be negative";
  return;
}

if (!is_numeric($_POST["komi"]))
{
  echo "Komi value must be numeric";
  return;
}

if ($_POST["color"] == "black")
  $opponentIsBlack = false;
elseif ($_POST["color"] == "white")
  $opponentIsBlack = true;
else
{
  echo "Invalid color value:".$_POST["color"];
  return;
}

$reportingWin = $_POST['result'] == "win";

$myOldRating = $me["rating"];
$opponentOldRating = $opponent["rating"];

$myHandicap = ($opponentIsBlack ? -1 : 1) * $_POST["handicap"];
$myExtraKomi = ($opponentIsBlack ? 1 : -1) * ($_POST["komi"] - 6.5);
$myNewRating = calculateNewRating($myOldRating, $opponentOldRating, $reportingWin ? 1.0 : 0.0, $_POST["game_type"], $myHandicap, $myExtraKomi);
$opponentNewRating = calculateNewRating($opponentOldRating, $myOldRating, $reportingWin ? 0.0 : 1.0, $_POST["game_type"], -$myHandicap, -$myExtraKomi);

query("INSERT INTO
       game(winner_user_id,
            loser_user_id,
            game_type_id,
            location,
            winner_comment,
            loser_comment,
            winner_old_rating,
            winner_new_rating,
            loser_old_rating,
            loser_new_rating,
            sgf,
            winner_is_black,
            handicap,
            komi)
       VALUES(".($reportingWin ? userID() : $opponent["id"]).",".
                ($reportingWin ? $opponent["id"] : userID()).",".
                escape($_POST["game_type"]).",".
                escape($_POST["location"]).",".
                escape($reportingWin ? $_POST["comment"] : "").",".
                escape($reportingWin ? "" : $_POST["comment"]).",".
                escape($reportingWin ? $myOldRating : $opponentOldRating).",".
                escape($reportingWin ? $myNewRating : $opponentNewRating).",".
                escape($reportingWin ? $opponentOldRating : $myOldRating).",".
                escape($reportingWin ? $opponentNewRating : $myNewRating).",".
                escape($sgf).",".
                (($opponentIsBlack != $reportingWin) ? "true" : "false").",".
                escape($_POST["handicap"]).",".
                escape($_POST["komi"]).")");

query("UPDATE user set rating=".$myNewRating." WHERE id=".userID());
query("UPDATE user set rating=".$opponentNewRating." WHERE id=".escape($_POST["winner_user_id"]));

redirectWithMessageCustom("/player?id=".userID(), "Game added");
?>
