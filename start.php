<?php
$page = substr(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), 1);
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

if ($page == "login" or
    $page == "logout" or
    $page == "register_action" or
    $page == "report_action" or
    $page == "edit_player_name_action" or
    $page == "delete_game_action" or
    $page == "sgf" or
    $page == "process_tournament" or
    $page == "update_rating" or
    $page == "process_tournament_batch")
  require($page.".php");
else
{
  require("src/header.php");
  if (!empty($_GET["message"]))
    echo "<div class=\"message-div\"><h3><b>Message:</b></h3></br>".$_GET["message"]."</div>";
  if ($page == "")
    require("home.php");
  elseif ($page == "player" or
          $page == "players" or
          $page == "invite" or
          $page == "invites" or
          $page == "tournaments" or
          $page == "tournament" or
          $page == "register" or
          $page == "register.php" or
          $page == "report" or
          $page == "about" or
          $page == "get_all_egd_players" or
          $page == "update_tournament_list")
    require($page.".php");
  else
  {
    $player = query("SELECT user.id as id from user WHERE user.username=".escape($page))->fetch_assoc();
    if ($player)
    {
      $_GET["id"] = $player["id"];
      require("player.php");
    }
    else
      echo "Unknown page:".$page;
  }
  require("src/footer.php");
}
?>
