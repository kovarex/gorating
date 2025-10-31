<?php

session_name("gorating_id");
if (!empty($_COOKIE["gorating_id"]))
{
  ini_set('session.gc_maxlifetime', 3600 * 24 * 7); // a week
  session_set_cookie_params(3600 * 24 * 7);
  session_start();
}

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

function canProcessTournament()
{
  return userCanDo(ADMIN_LEVEL_MOD);
}

function canAccessAllInvites()
{
  return userCanDo(ADMIN_LEVEL_MOD);
}

function canSeeInviters()
{
  return userCanDo(ADMIN_LEVEL_MOD);
}

function canSeeEmails()
{
  return userCanDo(ADMIN_LEVEL_MOD);
}

function canInvite()
{
  return userCanDo(ADMIN_LEVEL_USER);
}

function canEditPlayerName()
{
  return userCanDo(ADMIN_LEVEL_MOD);
}

function canDeleteAnyGame()
{
  return userCanDo(ADMIN_LEVEL_ADMIN);
}

function canAccessDeletedGames()
{
  return userCanDo(ADMIN_LEVEL_ADMIN);
}

function canEditAnyGame()
{
  return userCanDo(ADMIN_LEVEL_ADMIN);
}

function canAccessChanges()
{
  return userCanDo(ADMIN_LEVEL_ADMIN);
}

function canEditWinner()
{
  return userCanDo(ADMIN_LEVEL_MOD);
}

function canEditGame($winnerUserID, $loserUserID, $timestamp)
{
  if (canEditAnyGame())
    return true;
  if (!userID())
    return false;
  if (userID() != $winnerUserID and userID() != $loserUserID)
    return false;

  $timeFirst = strtotime($timestamp);
  $timeSecond = strtotime(date("Y-m-d H:i:s"));
  $differenceInSeconds = $timeSecond - $timeFirst;
  return $differenceInSeconds < SECONDS_TO_ALLOW_EDIT_MY_GAMES;
}

function canShowStatistics()
{
  return userCanDo(ADMIN_LEVEL_OWNER);
}

function checkLoggedIn()
{
  if (!userID())
    die("Not logged in");
}

?>
