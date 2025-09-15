<?php
$players = query("SELECT * FROM user ORDER BY user.rating DESC");
echo "<table class=\"data-table\">";
echo "<tr><th>EGD link</th><th>Name</th><th>Rating</th></tr>";
while($row = $players->fetch_assoc())
{
  echo "<tr>";
  echo "<td>".egdLink($row["egd_pin"])."</td>";
  echo "<td>".playerLink($row["id"], $row["first_name"]." ".$row["last_name"])."</td>";
  echo "<td>".$row["rating"]."</td>";
  echo "</tr>";
}
echo "</table>";
?>