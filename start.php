<?php
$page = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
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

if ($page == "/login")
  require("login.php");
elseif ($page == "/logout")
  require("logout.php");
elseif ($page == "/register_action")
  require("register_action.php");
elseif ($page == "/report_action")
  require("report_action.php");
elseif ($page == "/edit_player_name_action")
  require("edit_player_name_action.php");
elseif ($page == "/sgf")
  require("sgf.php");
elseif ($page == "/process_tournament")
  require("process_tournament.php");
elseif ($page == "/process_tournament_batch")
  require("process_tournament_batch.php");
else
{
  require("src/header.php");
  if (!empty($_GET["message"]))
    echo "<div>".$_GET["message"]."</div>";
  if ($page == "/")
    require("home.php");
  elseif ($page == "/player")
    require("player.php");
    elseif ($page == "/players")
    require("players.php");
  elseif ($page == "/invite")
    require("invite.php");
  elseif ($page == "/invites")
    require("invites.php");
  elseif ($page == "/tournaments")
    require("tournaments.php");
  elseif ($page == "/tournament")
    require("tournament.php");
  elseif ($page == "/register")
    require("register.php");
  elseif ($page == "/report")
    require("report.php");
  elseif ($page == "/about")
    require("about.php");
  elseif ($page == "/get_all_egd_players")
    require("get_all_egd_players.php");
  elseif ($page == "/update_tournament_list")
    require("update_tournament_list.php");
  else
    echo "Unknown page:".$page;
}
require("src/footer.php");
?>
