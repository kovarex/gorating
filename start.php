<?php
$page = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$query = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);

foreach (explode('&', $query) as $chunk)
{
  $param = explode("=", $chunk);
  if ($param)
    $_GET[urldecode($param[0])] = urldecode($param[1]);
}
require("src/link_helper.php");
require("src/constants.php");

if ($page == "/login")
  require("login.php");
elseif ($page == "/logout")
  require("logout.php");
elseif ($page == "/register_action")
  require("register_action.php");
elseif ($page == "/report_action")
  require("report_action.php");
elseif ($page == "/sgf")
  require("sgf.php");
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
  elseif ($page == "/register")
    require("register.php");
  elseif ($page == "/report")
    require("report.php");
  elseif ($page == "/about")
    require("about.php");
  else
    echo "Unknown page:".$page;
}
require("src/footer.php");
?>
