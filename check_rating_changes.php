<?php
require_once("src/rating_change_helper.php");
$pageStart = microtime(true);
$data = query("SELECT id from user where rating_changes_checked=false");
echo "Remaining ".$data->num_rows." users to check.<br/>\n";
for ($i = 0; $i < 1; $i++)
{
  $row = $data->fetch_assoc();
  if (!$row)
    break;
  checkUserRatingChanges($row["id"]);
}
$pageEnd = microtime(true);
$execution_time = ($pageEnd - $pageStart);
echo "Executed in ".round($execution_time, 4)." seconds";
?>
