<?php
require ("src/egd_api.php");
if (@$_GET["secret"] != $scrappingSecret)
  die("Scrapping secret wrong.");
echo getUrlContentSafe($_GET["url"], $_POST);
?>