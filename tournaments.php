<?php
require("src/tournament_helper.php");

$search = @$_GET["search"];

echo "<div style=\"text-align:center;\">";
echo "<form>";
echo "<table class=\"centered-table\">";
//echo "<tr><td><label for=\"search\">Search:</label></td><td><input type=\"text\" name=\"search\"".($search ? "value=\"".$search."\"" : "")."/></td></tr>";
echo "<tr><td><label for=\"country_code\">Country:</label></td><td>".countrySelector(@$_GET["country_code"])."</td></tr>";
echo "<tr><td colspan=2><input type=\"submit\" value=\"Submit\"/></td></tr>";
echo "</table>";
echo "</form>";
echo "</div>";

showTournaments(NULL, NULL, @$_GET["country_code"], $search)
?>
