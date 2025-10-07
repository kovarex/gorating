<?php

echo "<form method=\"post\" action=\"/settings_action\">";
echo "<table>";

echo "<tr>";
  echo "<td><label for=\"significant_digits\">Singificant digits to show in rating:</form></td>";
  echo "<td><select id=\"significant_digits\" name=\"significant_digits\">";
  $significantDigitsToShow = isset($_SESSION["user"]["setting_significant_digits_in_rating"]) ? $_SESSION["user"]["setting_significant_digits_in_rating"] : 0;
  for ($i = 0; $i <= 3; $i++)
    echo "<option value=\"".$i."\"".($i == $significantDigitsToShow ? " selected=\"selected\"" : "").">".$i."</option>";
  echo "</select></td></tr>";
echo "</tr>";

$ratingChangeFormat = 1;
if (isset($_SESSION["user"]["setting_rating_change_format"]))
  $ratingChangeFormat = $_SESSION["user"]["setting_rating_change_format"];

echo "<tr>";
  echo "<td><label for=\"rating_change_format\">Rating change format:</label></td>";
  echo "<td>";
    echo "<input type=\"radio\" id=\"format_1\" name=\"rating_change_format\" value=\"1\"".($ratingChangeFormat == 1 ? " checked=\"checked\"" : "")."/> <label for=\"format_1\">1657&rarr;1667</label><br/>\n";
    echo "<input type=\"radio\" id=\"format_2\" name=\"rating_change_format\" value=\"2\"".($ratingChangeFormat == 2 ? " checked=\"checked\"" : "")."/> <label for=\"format_2\">+12 1667</label><br/>\n";
  echo "</td>";
echo "</tr>";
echo "</table>";
echo "<input type=\"submit\" value=\"submit\"/>";
echo "</form>";
?>
