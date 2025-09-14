<?php
$player = query("SELECT * from user where id=".escape($_GET["id"]))->fetch_assoc();
if (empty($player))
  die("Unknown player with id=".$_GET["id"]);

echo "<h1>".$player["first_name"]." ".$player["last_name"]."</h1>";
echo "<table>";
echo "<tr><td>Rating:</td><td>".$player["rating"]."</td></tr>";
echo "<tr><td>EGD Rating:</td><td>".$player["egd_rating"]."</td></tr>";
echo "</table>";
?>
