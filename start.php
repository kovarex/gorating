<?php
$pageStart = microtime(true);
error_reporting(E_ALL);
$uri = urldecode($_SERVER["REQUEST_URI"]);
$pagePath = substr(parse_url($uri, PHP_URL_PATH), 1);
$query = parse_url($uri, PHP_URL_QUERY);

if ($query)
  foreach (explode('&', $query) as $chunk)
  {
    $param = explode("=", $chunk);
    if ($param and @$param[0] and @$param[1])
      $_GET[urldecode($param[0])] = urldecode($param[1]);
  }

require_once("src/auth.php");
require_once("src/link_helper.php");
require_once("src/constants.php");
require_once("src/db.php");
require_once("src/rating_helper.php");

define("PAGE_WITHOUT_HEADER", 1);
define("NORMAL_PAGE", 2);

foreach (array("login_action",
               "logout",
               "register_action",
               "report_action",
               "edit_player_name_action",
               "delete_game_action",
               "restore_game_action",
               "sgf",
               "process_tournament",
               "update_rating",
               "process_tournament_batch",
               "invite_action",
               "invite_existing_user_action",
               "edit_game_action",
               "check_new_tournaments",
               "get_new_tournaments_contents",
               "scrap",
               "reset_password_action",
               "reset_password_confirm_action",
               "toggle_statistics",
               "collect_manual_rating_changes") as $target)
  $pages[$target] = PAGE_WITHOUT_HEADER;

foreach (array("login",
               "player",
               "players",
               "invites",
               "tournaments",
               "tournament",
               "register",
               "report",
               "about",
               "get_all_egd_players",
               "update_tournament_list",
               "deleted_games",
               "invite_existing_user",
               "edit_game",
               "reset_password",
               "reset_password_confirm") as $target)
  $pages[$target] = NORMAL_PAGE;

if ($pagePath == "")
  $pageType = NORMAL_PAGE;
else
  $pageType = @$pages[$pagePath];

if (!$pageType)
  $player = query("SELECT user.id as id from user WHERE user.username=".escape($pagePath))->fetch_assoc();

if ($pageType == NORMAL_PAGE or isset($player))
{
  require("src/header.php");
  if (!empty($_GET["message"]))
    echo "<div class=\"message-div\"><h3><b>Message:</b></h3></br>".$_GET["message"]."</div>";
}

if ($pagePath == "")
  $result = require("home.php");
else if ($pageType)
  $result = require($pagePath.".php");
else if ($player)
{
  $_GET["id"] = $player["id"];
  $result = require("player.php");
}
else
  echo "Unknown page:".$pagePath;

if (!empty($result) and is_string($result))
  echo "<div class=\"message-div\"><h3><b>Message:</b></h3></br>".$result."</div>";

if ($pageType == NORMAL_PAGE or isset($player))
  require("src/footer.php");
?>
