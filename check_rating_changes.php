<?php
require_once("src/rating_change_helper.php");
$pageStart = microtime(true);
$data = query("SELECT id from user where rating_change_checked=false");
echo "Remaining ".$data->num_rows." users to check.<br/>\n";

function tryToCheckRepeatedly($id)
{
  for ($j = 0; $j < 10; $j++)
    if (checkUserRatingChanges($id))
      return true;
  return false;
}

for ($i = 0; $i < 1000; $i++)
{
  $row = $data->fetch_assoc();
  if (!$row)
    break;

  if (!tryToCheckRepeatedly($row["id"]))
  {
    echo "This user seems to be broken, manual check needed.<br/>\n";
    die();
  }
}
$pageEnd = microtime(true);
$execution_time = ($pageEnd - $pageStart);
echo "Executed in ".round($execution_time, 4)." seconds";
?>
