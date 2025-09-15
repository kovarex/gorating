<?php
ini_set('session.gc_maxlifetime', 3600 * 24 * 7); // a week
session_set_cookie_params(3600 * 24 * 7);
session_start();

function userID()
{
  return @$_SESSION["user"]["id"];
}

function adminLevel()
{
  if (!userID())
    return 1000;
  else
    return @$_SESSION["user"]["admin_level_id"];
}

function userCanDo($level)
{
  return adminLevel() <= $level;
}

function canAccessAllInvites()
{
  return userCanDo(ADMIN_LEVEL_MOD);
}

function checkLoggedIn()
{
  if (!userID())
    die("Not logged in");
}

?>
