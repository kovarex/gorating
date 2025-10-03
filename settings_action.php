<?php
if (!userID())
  return "Not logged in";
if (!isset($_POST["significant_digits"]))
  return "Significant digits not provided";
$significantDigits = (int)$_POST["significant_digits"];
if ($_POST["significant_digits"] < 0)
  return "Significant digits can't be lower than 0";
if ($_POST["significant_digits"] > 3)
  return "Significant digits can be 3 at most";
query("UPDATE user SET setting_significant_digits_in_rating=".escape($significantDigits)." WHERE id=".escape(userID()));
$_SESSION["user"]["setting_significant_digits_in_rating"] = $significantDigits;
redirect("/");
?>
