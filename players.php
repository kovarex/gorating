<?php
$players = query("SELECT
                    user.id as id,
                    user.rating as rating,
                    user.first_name as first_name,
                    user.last_name as last_name,
                    user.egd_pin as egd_pin,
                    admin_level.name as admin_level_name
                  FROM user, admin_level
                  WHERE
                    user.admin_level_id = admin_level.id
                  ORDER BY user.rating DESC");
echo "<table class=\"data-table\">";
echo "<tr><th>Rating</th><th>Name</th><th>EGD link</th><th>Role</th></tr>";
while($row = $players->fetch_assoc())
{
  echo "<tr>";
  echo "<td>".$row["rating"]."</td>";
  echo "<td>".playerLink($row["id"], $row["first_name"]." ".$row["last_name"])."</td>";
  echo "<td>".egdLink($row["egd_pin"])."</td>";
  echo "<td>".$row["admin_level_name"]."</td>";
  echo "</tr>";
}
echo "</table>";
?>
