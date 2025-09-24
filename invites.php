<?php
checkLoggedIn();
if (!empty(@$_GET["pin"]))
  $player = query("SELECT first_name, last_name FROM user WHERE egd_pin=".escape($_GET["pin"]))->fetch_assoc();

echo "<form method=\"post\" action=\"invite\">
        <table>
          <tr>
            <td><label for=\"first_name\">First name:</label></td>
            <td><input type=\"text\" name=\"first_name\" value=\"".@$player["first_name"]."\"/></td>
          </tr>
          <tr>
            <td><label for=\"last_name\">Last name:</label></td>
            <td><input type=\"text\" name=\"last_name\" value=\"".@$player["last_name"]."\"/></td>
          </tr>
          <tr>
            <td><label for=\"egd_pin\">EGD pin:</label></td>
            <td><input type=\"text\" name=\"egd_pin\" value=\"".@$_GET["pin"]."\"/></td>
          </tr>
          <tr>
            <td><label for=\"rating\">Proposed rating (used when egd is not provided):</label></td>
            <td><input type=\"text\" name=\"rating\"/></td>
          </tr>
          <tr>
            <td><label for=\"email\">email:</label></td>
            <td><input type=\"text\" name=\"email\"/></td>
          </tr>
        </table>
        <input type=\"hidden\" name=\"redirect\" value=\"invites\"/>
        <input type=\"submit\" value=\"invite\"/>
      </form>";
$result = query("SELECT
                    invite.first_name as first_name,
                    invite.last_name as last_name,
                    invite.email as email,
                    invite.egd_pin as egd_pin,
                    invite.from_user_id as from_user_id,
                    CONCAT(user.first_name, ' ', user.last_name) as from_user_name,
                    user.username as from_user_username
                 FROM
                   invite, user
                 WHERE
                   invite.from_user_id = user.id".(canAccessAllInvites() ? "" : " and invite.from_user_id=".userID()));
if ($result->num_rows == 0)
{
  echo "No pending invites";
  return;
}

echo "<table class='data-table'><tr><th>Name</th><th>EGD</th><th>email</th>";
if (canAccessAllInvites())
  echo "<th>Creator</th>";
echo "</tr>";
while($row = $result->fetch_assoc())
{
  echo "<tr>";
  echo "<td>".$row["first_name"]." ".$row["last_name"]."</td>";
  echo "<td>".egdLink($row["egd_pin"])."</td>";
  echo "<td>".$row["email"]."</td>";
  if (canAccessAllInvites())
    echo "<td>".playerLink($row["from_user_id"], $row["from_user_name"], $row["from_user_username"])."</td>";
  echo "</tr>";
}
echo "</table>";
?>
