<?php
$pagePath = substr(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), 1);
$query = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);

foreach (explode('&', $query) as $chunk)
{
  $param = explode("=", $chunk);
  if ($param)
    $_GET[urldecode($param[0])] = urldecode($param[1]);
}

require_once("src/auth.php");
require_once("src/link_helper.php");
require_once("src/constants.php");
require_once("src/db.php");

define("PAGE_WITHOUT_HEADER", 1);
define("NORMAL_PAGE", 2);

foreach (array("login",
               "logout",
               "register_action",
               "report_action",
               "edit_player_name_action",
               "delete_game_action",
               "sgf",
               "process_tournament",
               "update_rating",
               "process_tournament_batch") as $target)
  $pages[$target] = PAGE_WITHOUT_HEADER;

foreach (array("player",
               "players",
               "invite",
               "invites",
               "tournaments",
               "tournament",
               "register",
               "register.php",
               "report",
               "about",
               "get_all_egd_players",
               "update_tournament_list",
               "deleted_games") as $target)
  $pages[$target] = NORMAL_PAGE;

if ($pagePath == "")
  $pageType = NORMAL_PAGE;
else
  $pageType = $pages[$pagePath];

if (!$pageType)
  $player = query("SELECT user.id as id from user WHERE user.username=".escape($pagePath))->fetch_assoc();

if ($pageType == NORMAL_PAGE or $player)
{
  require("src/header.php");
  if (!empty($_GET["message"]))
    echo "<div class=\"message-div\"><h3><b>Message:</b></h3></br>".$_GET["message"]."</div>";
}

if ($pagePath == "")
  require("home.php");
else if ($pageType)
  require($pagePath.".php");
else if ($player)
{
  $_GET["id"] = $player["id"];
  require("player.php");
}
else
  echo "Unknown page:".$pagePath;

if ($pageType == NORMAL_PAGE or $player)
  require("src/footer.php");
?>
