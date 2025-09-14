<?php
ini_set('session.gc_maxlifetime', 3600 * 24 * 7); // a week
session_set_cookie_params(3600 * 24 * 7);
session_start();
$_SESSION["user"] = NULL;
header("Location: /");
?>