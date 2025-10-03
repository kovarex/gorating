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
echo "</table>";
echo "<input type=\"submit\" value=\"submit\"/>";
echo "</form>";
?>
