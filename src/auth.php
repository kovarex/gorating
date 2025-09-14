<?php
ini_set('session.gc_maxlifetime', 3600 * 24 * 7); // a week
session_set_cookie_params(3600 * 24 * 7);
session_start();

function userID()
{
  return @$_SESSION["user"]["id"];
}

function checkLoggedIn()
{
  if (empty(userID()))
    die("Not logged in");
}

?>
