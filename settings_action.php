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

if (!isset($_POST["rating_change_format"]))
  return "rating_change_format not provided";

$ratingChangeFormat = $_POST["rating_change_format"];
if ($ratingChangeFormat != "1" and $ratingChangeFormat != "2")
  return "Invalid rating change format value";

query("UPDATE ".
      "  user ".
      "SET ".
      "  setting_significant_digits_in_rating=".escape($significantDigits).",".
      "  setting_rating_change_format=".escape($ratingChangeFormat).
     " WHERE id=".escape(userID()));
$_SESSION["user"]["setting_significant_digits_in_rating"] = $significantDigits;
$_SESSION["user"]["setting_rating_change_format"] = $ratingChangeFormat;
redirect("/");
?>
