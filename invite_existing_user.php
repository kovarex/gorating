<?php
checkLoggedIn();
if (empty(@$_GET["user_id"]))
{
  echo "User id to invite not specified";
  die();
}

$player = query("SELECT * FROM user WHERE id=".escape($_GET["user_id"]))->fetch_assoc();
if (!$player)
{
  echo "User with id=".$_GET["user_id"]." not found";
  die();
}

echo "<form method=\"post\" action=\"invite_existing_user_action\">
        <table>
          <tr>
            <td><label for=\"first_name\">First name:</label></td>
            <td>".$player["first_name"]."</td>
          </tr>
          <tr>
            <td><label for=\"last_name\">Last name:</label></td>
            <td>".$player["last_name"]."</td>
          </tr>
          <tr>
            <td><label for=\"egd_pin\">EGD pin:</label></td>
            <td>".egdLink(@$player["egd_pin"])."</td>
          </tr>
          <tr>
            <td>Rating</td>
            <td>".$player["rating"]."</td>
          </tr>
          <tr>
            <td><label for=\"email\">email:</label></td>
            <td><input type=\"text\" name=\"email\"/></td>
          </tr>
        </table>
        <input type=\"hidden\" name=\"user_id\" value=\"".$_GET["user_id"]."\"/>
        <input type=\"hidden\" name=\"redirect\" value=\"invites\"/>
        <input type=\"submit\" value=\"invite\"/>
      </form>";
?>
