<?php
require("src/header_internal.php");
if (!empty($_SESSION["user"]))
{
  echo "Currently logged in as ".$_SESSION["user"]["username"];
  echo "<form method=\"post\">
          <input type=\"submit\" value=\"Logoff\"/>
          <input type=\"hidden\" name=\"action\" value=\"logoff\"/>
        </form>";
}
else
{
 ?>
 <form method="post" action="/login_action">
    <table>
      <tr>
        <td><label for="username">Username:</label></td>
        <td><input name="username" type="text"/></td>
      </tr>
      <tr>
        <td><label for="password">Password:</label></td>
        <td><input name="password" type="password"/></td>
      </tr>
    </table>
    <input type="submit" value="Login"/>
  </form>
  <a href="reset_password">Reset password</a>
<?php
}
?>
