<?php
require("src/db.php");
require("src/auth.php");
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

$myOldRating = $me["rating"];
$opponentOldRating = $opponent["rating"];
$myNewRating = calculateNewRating($myOldRating, $opponentOldRating, 0.0, $_POST["game_type"]);
$opponentNewRating = calculateNewRating($opponentOldRating, $myOldRating, 1.0, $_POST["game_type"]);
if (!empty($_FILES["sgf"] and !empty($_FILES["sgf"]["tmp_name"])))
  $sgf = file_get_contents($_FILES['sgf']['tmp_name']);

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
            sgf)
       VALUES(".$opponent["id"].",".
                userID().",".
                escape($_POST["game_type"]).",".
                escape($_POST["location"]).",".
                escape($_POST["comment"]).",".
                escape($opponentOldRating).",".
                escape($opponentNewRating).",".
                escape($myOldRating).",".
                escape($myNewRating).",".
                escape($sgf).")");
query("UPDATE user set rating=".$myNewRating." WHERE id=".userID());
query("UPDATE user set rating=".$opponentNewRating." WHERE id=".escape($_POST["winner_user_id"]));

redirectWithMessageCustom("/player?id=".userID(), "Game added");
?>
