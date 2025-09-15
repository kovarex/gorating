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


echo " <form method=\"post\" action=\"register_action\">
       <table>
         <tr>
           <td>First name:</label></td>
           <td>".$invite["first_name"]."</td>
         </tr>
         <tr>
           <td>Last name:</label></td>
           <td>".$invite["last_name"]."</td>
         </tr>
         <tr>
           <td>email:</label></td>
           <td>".$invite["email"]."</td>
         </tr>";
if (!empty($invite["egd_pin"]))
  echo "<tr><td>Related EGD account</td><td>".egdLink($invite["egd_pin"])."</td></tr>";
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
      <input type=\"submit\" value=\"Register\"/>
    </form>";
?>
