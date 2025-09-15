<?php
require_once("db.php");
require_once("auth.php");
require("header_internal.php");

if (!empty($_SESSION["user"]))
  echo "<div style=\"position: absolute;right: 0px;\">
          Currently logged in as".$_SESSION["user"]["username"]."
          <form method=\"post\" action=\"logout\" style=\"display:inline;\">
            <input type=\"submit\" value=\"Logoff\"/>
          </form>
        </div>";
else
  echo "<div style=\"position: absolute;right: 0px;\">
          Currently not logged in
          <form method=\"post\" action=\"login\" style=\"display:inline;\">
            <input type=\"submit\" value=\"Login\"/>
          </form>
        </div>";
echo "<div>";
echo "<a href=\"/\">Home</a>";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/players\">Players</a>";
if (adminLevel() <= ADMIN_LEVEL_USER)
  echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/invites\">Invites</a>";
echo "</div>";
echo "<br/><br/>";
?>
