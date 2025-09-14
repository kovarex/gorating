<?php
$page = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
require("src/header.php");
if ($page == "/")
  require("home.php");
elseif ($page == "/player")
  require("player.php");
else
  echo "Unknown page:".$page;
?>
