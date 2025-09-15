<?php
$players = query("SELECT
                    user.id as id,
                    user.rating as rating,
                    user.first_name as first_name,
                    user.last_name as last_name,
                    user.egd_pin as egd_pin,
                    user.email as email,
                    admin_level.name as admin_level_name,
                    inviter.id as inviter_id,
                    inviter.first_name as inviter_first_name,
                    inviter.last_name as inviter_last_name
                  FROM user LEFT JOIN user as inviter ON inviter.id = user.invited_by_user_id,
                       admin_level
                  WHERE
                    user.admin_level_id = admin_level.id
                  ORDER BY user.rating DESC");
echo "<table class=\"data-table\">";
echo "<tr>";
echo "<th>Rating</th>";
echo "<th>Name</th>";
echo "<th>EGD link</th>";
echo "<th>Role</th>";
if (canSeeInviters())
  echo "<th>Invited by</th>";
if (canSeeEmails())
  echo "<th>email</th>";
if (userID())
  echo "<th>Report loss</th>";
echo "</tr>";
while($row = $players->fetch_assoc())
{
  echo "<tr>";
  echo "<td style=\"text-align: right;\">".round($row["rating"])."</td>";
  echo "<td>".playerLink($row["id"], $row["first_name"]." ".$row["last_name"])."</td>";
  echo "<td>".egdLink($row["egd_pin"])."</td>";
  echo "<td>".$row["admin_level_name"]."</td>";
  if (canSeeInviters())
    echo "<td>".playerLink($row["inviter_id"], $row["inviter_first_name"]." ".$row["inviter_last_name"])."</td>";
  if (canSeeEmails())
    echo "<td>".$row["email"]."</td>";
  if (userID())
  {
    echo "<td>";
    if ($row["id"] != userID())
      echo "<a href=\"report?id=".$row["id"]."\"/>Report</a>";
    echo "</td>";
  }
  echo "</tr>";
}
echo "</table>";
?>
