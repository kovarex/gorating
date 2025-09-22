<?php
require("header_internal.php");

if (!empty($_SESSION["user"]))
  echo "<div style=\"position: absolute;right: 0px;\">
          Currently logged in as ".$_SESSION["user"]["username"]."
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
echo "<div class=\"centered-div\">\n";
echo "<div class=\"centered-div\">\n";
echo "<a href=\"/\">Home</a>\n";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/players\">Players</a>\n";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/tournaments\">Tournaments</a>\n";
if (adminLevel() <= ADMIN_LEVEL_USER)
  echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/invites\">Invites</a>\n";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/about\">About</a>\n";
echo "</div>";
echo "<br/><br/>\n";
?>
