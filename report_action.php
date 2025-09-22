<?php
require("src/rating_helper.php");

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
  $winnerIsBlack = false;
elseif ($_POST["color"] == "white")
  $winnerIsBlack = true;
else
{
  echo "Invalid color value:".$_POST["color"];
  return;
}

$myOldRating = $me["rating"];
$opponentOldRating = $opponent["rating"];

$myHandicap = ($winnerIsBlack ? -1 : 1) * $_POST["handicap"];
$myExtraKomi = ($winnerIsBlack ? 1 : -1) * ($_POST["komi"] - 6.5);
$myNewRating = calculateNewRating($myOldRating, $opponentOldRating, 0.0, $_POST["game_type"], $myHandicap, $myExtraKomi);
$opponentNewRating = calculateNewRating($opponentOldRating, $myOldRating, 1.0, $_POST["game_type"], -$myHandicap, -$myExtraKomi);

query("INSERT INTO
       game(winner_user_id,
            loser_user_id,
            game_type_id,
            location,
            loser_comment,
            winner_old_rating,
            winner_new_rating,
            loser_old_rating,
            loser_new_rating,
            sgf,
            winner_is_black,
            handicap,
            komi)
       VALUES(".$opponent["id"].",".
                userID().",".
                escape($_POST["game_type"]).",".
                escape($_POST["location"]).",".
                escape($_POST["comment"]).",".
                escape($opponentOldRating).",".
                escape($opponentNewRating).",".
                escape($myOldRating).",".
                escape($myNewRating).",".
                escape($sgf).",".
                ($winnerIsBlack ? "true" : "false").",".
                escape($_POST["handicap"]).",".
                escape($_POST["komi"]).")");

query("UPDATE user set rating=".$myNewRating." WHERE id=".userID());
query("UPDATE user set rating=".$opponentNewRating." WHERE id=".escape($_POST["winner_user_id"]));

redirectWithMessageCustom("/player?id=".userID(), "Game added");
?>
