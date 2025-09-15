<?php
require("src/db.php");
require("src/auth.php");
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


query("INSERT INTO
       game(winner_user_id,
            loser_user_id,
            game_type_id,
            location,
            loser_comment)
       VALUES(".$opponent["id"].",".
                userID().",".
                escape($_POST["game_type"]).",".
                escape($_POST["location"]).",".
                escape($_POST["comment"]).")");

redirectWithMessageCustom("/player?id=".userID(), "Game added");
?>
