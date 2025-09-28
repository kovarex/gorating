<?php
if (!canShowStatistics())
  return "No rights";
$_SESSION["statistics"] = !@$_SESSION["statistics"];
redirectWithMessageCustom("/", "Statistics turned ".($_SESSION["statistics"] ? "ON" : "OFF"));
?>