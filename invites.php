<?php
checkLoggedIn();
if (!empty(@$_GET["pin"]))
  $player = query("SELECT first_name, last_name FROM user WHERE egd_pin=".escape($_GET["pin"]))->fetch_assoc();

echo "<div class=\"centered-div\">";
echo "Only  invite playres NOT in EGD here!<br/>\n";
echo "To invite EGD players, use the Invite link under their name.";
echo "</div>";
echo "<form method=\"post\" action=\"invite_action\">
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
            <td><label for=\"rating\">Starting rating (be responsible):</label></td>
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

require_once("src/table_viewer.php");
$table = new TableViewer("invite
                            LEFT JOIN user ON invite.user_id=user.id
                            JOIN user as inviter ON invite.from_user_id=inviter.id".(canAccessAllInvites() ? "" : "WHERE invite.from_user_id=".userID()),
                         $_GET);

$table->setPrimarySort(new SortDefinition("timestamp", false));

$table->addColumn("name",
                  "Name",
                  array(array("IF(user.id, CONCAT(user.first_name, ' ', user.last_name), CONCAT(invite.first_name, ' ', invite.last_name))", "name"),
                        array("user.id", "id"),
                        array("user.username", "username")),
                  function($row)
                  {
                    if (@$row["id"])
                      echo playerLink($row);
                    else
                      echo $row["name"];
                  });

$table->addColumn("email",
                  "Email",
                  array(array("invite.email", "email")),
                  function($row) { echo $row["email"]; });

if (canAccessAllInvites())
  $table->addColumn("inviter_name",
                    "Inviter",
                    array(array("CONCAT(inviter.first_name, ' ', inviter.last_name)", "inviter_name"),
                          array("inviter.id", "inviter_id"),
                          array("inviter.username", "inviter_username")),
                    function($row) { echo  playerLink($row, "inviter"); });

$table->addColumn("invite",
                  "Invite link",
                  array(array("invite.id", "invite_id"),
                        array("invite.secret", "invite_secret")),
                  function($row) { echo  inviteAddress($row["invite_id"], $row["invite_secret"]); });

$table->addColumn("timestamp",
                   "Timestamp",
                   array(array("invite.timestamp", "timestamp")),
                   function($row) { echo date("d. m. Y H:i", strtotime($row["timestamp"])); });

$table->render();
?>
