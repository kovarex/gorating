<?php

function checkLogin($username, $password)
{
  if (empty($username))
    return false;

  $user = query("SELECT * from user where user.username=".escape($username))->fetch_assoc();

  if (empty($user))
    return "User not found!";

  if (!password_verify($password, $user["password"]))
    return "Wrong password!";

  $_SESSION["user"] = $user;
  return true;
}

require("src/db.php");
ini_set('session.gc_maxlifetime', 3600 * 24 * 7); // a week
session_set_cookie_params(3600 * 24 * 7);
session_start();
$loginResult = checkLogin(@$_POST['username'], @$_POST['password']);

if ($loginResult === true)
{
  header("Location: player?id=".$_SESSION["user"]["id"]);
  die();
}

require("src/header_internal.php");

if (is_string($loginResult))
  echo $loginResult;

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
 <form method="post">
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
<?php
}
?>
