<?php
require("src/register_helper.php");
$checkResult = checkRegistrationAvailability($_GET["id"], $_GET["secret"]);
if (is_string($checkResult))
{
  echo $checkResult;
  return;
}

$invite = query("SELECT * FROM invite WHERE id=".escape($_GET["id"]))->fetch_assoc();
assert(!empty($invite));
echo "<div>You can now finish your registration with the following details</div><br/>";

if (!empty(@$invite["user_id"]))
  $user = query("SELECT * FROM user WHERE id=".escape($invite["user_id"]))->fetch_assoc();

echo " <form method=\"post\" action=\"register_action\">
       <table>
         <tr>
           <td>First name:</label></td>
           <td>".(isset($user) ? $user["first_name"] : $invite["first_name"])."</td>
         </tr>
         <tr>
           <td>Last name:</label></td>
           <td>".(isset($user) ? $user["last_name"] : $invite["last_name"])."</td>
         </tr>
         <tr>
           <td>email:</label></td>
           <td>".$invite["email"]."</td>
         </tr>";
if (isset($user))
  echo "<tr><td>Existing user:</td><td>".playerLink($user)."</td></tr>";
else
  echo "<tr><td>Country:</td><td>".countrySelector()."</td></tr>";
echo    "<tr>
           <td><label for=\"username\">Username:</label></td>
           <td><input name=\"username\" type=\"text\"/></td>
        </tr>
        <tr>
          <td><label for=\"password\">Password:</label></td>
          <td><input name=\"password\" type=\"password\"/></td>
        </tr>
      </table>
      <input type=\"hidden\" name=\"invite_id\" value=\"".$_GET["id"]."\"/>
      <input type=\"hidden\" name=\"secret\" value=\"".$_GET["secret"]."\"/>
      <input type=\"hidden\" name=\"redirect\" value=\"/register?id=".$_GET["id"]."&secret=".$_GET["secret"]."\"/>
      <input type=\"submit\" value=\"Register\"/>
    </form>";
?>
