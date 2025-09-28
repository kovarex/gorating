<?php
if (empty(@$_POST['username']))
  redirectWithMessageCustom("/login", "username not provided");

if (empty(@$_POST['password']))
  redirectWithMessageCustom("/login", "password not provided");

$user = query("SELECT * from user where user.username=".escape($_POST["username"]))->fetch_assoc();

if (!$user)
  redirectWithMessageCustom("/login", "User \"".$_POST["username"]."\" not found");

if (!password_verify($_POST['password'], $user["password"]))
  redirectWithMessageCustom("/login", "Wrong password!");

$_SESSION["user"] = $user;
header("Location: player?id=".$_SESSION["user"]["id"]);
?>