<?php
$ratingUpdate = query("SELECT * from rating_update WHERE finished = false")->fetch_assoc();
if (!$ratingUpdate)
  return "Nothing to update";
$limit = 100;
$gamesToProcess = query("SELECT * from game where last_rating_update_id<".$ratingToUpdate["id"]." and timestamp >='".date("Y-m-d H:i:s", strtotime($ratingUpdate["timestamp"]))."' LIMIT ".$limit);
while ($gameToProcess = $gamesToProcess->fetch_assoc())
{

}
?>