<?php

echo "<div style=\"text-align:center;\">";
echo "<form>";
echo "<input type=\"text\" name=\"search\"/>";
echo "</form>";
echo "</div>";

$search = @$_GET["search"];
if (empty($search))
  return;


function addWithOr($query, $addition)
{
  if (empty($query))
    return $addition;
  return $query." or ".$addition;
}

$parts = explode(" ", $search);
$searchQuery = "";

foreach ($parts as $part)
{
  if (is_numeric($part) and strlen($part) == 8)
    $searchQuery = addWithOr($searchQuery, "user.egd_pin=".escape($search));
  else
  {
    $searchQuery = addWithOr($searchQuery, "MATCH(user.first_name) AGAINST(".escape("*".$part."*")." IN BOOLEAN MODE)");
    $searchQuery = addWithOr($searchQuery, "MATCH(user.last_name) AGAINST(".escape("*".$part."*")." IN BOOLEAN MODE)");
  }
}

$players = query("SELECT
                    user.id as id,
                    user.rating as rating,
                    user.first_name as first_name,
                    user.last_name as last_name,
                    user.egd_pin as egd_pin,
                    user.email as email,
                    user.register_timestamp as register_timestamp,
                    admin_level.name as admin_level_name,
                    inviter.id as inviter_id,
                    inviter.first_name as inviter_first_name,
                    inviter.last_name as inviter_last_name,
                    country.code as country_code
                  FROM user LEFT JOIN user as inviter ON inviter.id = user.invited_by_user_id,
                       admin_level,
                       country
                  WHERE
                    user.admin_level_id = admin_level.id and
                    user.country_id = country.id and
                    (".$searchQuery.")
                  ORDER BY user.rating DESC
                  LIMIT 50");
echo "<table class=\"data-table\">";
echo "<tr>";
echo "<th>Rating</th>";
echo "<th>Name</th>";
echo "<th>EGD link</th>";
echo "<th>Country</th>";
echo "<th>Role</th>";
if (canSeeInviters())
  echo "<th>Invited by</th>";
if (canSeeEmails())
  echo "<th>email</th>";
echo "<th>Registered</th>";
if (userID())
  echo "<th>Report loss</th>";
echo "</tr>";
while($row = $players->fetch_assoc())
{
  echo "<tr>";
  echo "<td style=\"text-align: right;\">".round($row["rating"])."</td>";
  echo "<td>".playerLink($row["id"], $row["first_name"]." ".$row["last_name"])."</td>";
  echo "<td>".egdLink($row["egd_pin"])."</td>";
  echo "<td style=\"text-align: center;\">".$row["country_code"]."</td>";
  echo "<td>".$row["admin_level_name"]."</td>";
  if (canSeeInviters())
    echo "<td>".playerLink($row["inviter_id"], $row["inviter_first_name"]." ".$row["inviter_last_name"])."</td>";
  if (canSeeEmails())
    echo "<td>".$row["email"]."</td>";
  echo "<td>".(empty($row["register_timestamp"]) ? "" : date("d. m. Y H:i", strtotime($row["register_timestamp"])))."</td>";
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
