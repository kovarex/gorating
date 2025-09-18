<?php
$id = $_GET["id"];
if (empty($id))
{
  echo "ID not specified.";
  return;
}

$tournament = query("SELECT * FROM egd_tournament WHERE id=".escape($id))->fetch_assoc();
if (empty($tournament))
{
  echo "Tournament with id=".$id." not found.";
  return;
}

echo "<h1>".$tournament["name"]."</h1>";
echo "EGD:<a href=\"https://www.europeangodatabase.eu/EGD/Tournament_Card.php?&key=".$tournament["egd_key"]."\">".$tournament["egd_key"]."</a><br/>";

$data = query("SELECT
                 game.winner_user_id as winner_user_id,
                 winner.first_name as winner_first_name,
                 winner.last_name as winner_last_name,
                 game.loser_user_id as loser_user_id,
                 loser.first_name as loser_first_name,
                 loser.last_name as loser_last_name,
                 game.egd_tournament_round as round,
                 winner_result.placement as winner_placement,
                 loser_result.placement as loser_placement
               FROM
                 game as game,
                 user as loser,
                 user as winner,
                 egd_tournament_result as winner_result,
                 egd_tournament_result as loser_result
               WHERE
                 game.egd_tournament_id=".escape($id)." and
                 game.winner_user_id = winner.id and
                 winner_result.user_id = winner.id and
                 winner_result.egd_tournament_id =".escape($id)." and
                 game.loser_user_id = loser.id and
                 loser_result.user_id = loser.id and
                 loser_result.egd_tournament_id =".escape($id)."
               ORDER BY
                 LEAST(winner_result.placement, loser_result.placement),
                 game.egd_tournament_round");
echo "<table class=\"data-table\">";

echo "<tr>";
echo "<th>Placement</th>";
for ($i = 1; $i <= $tournament["round_count"]; $i++)
  echo "<th>Round ".$i."</th>";
echo "</tr>";

while ($row = $data->fetch_assoc())
{
  $round = $row["round"];
  $winnerPlacement = $row["winner_placement"];
  $loserPlacement = $row["loser_placement"];
  $horizontalPlayerIsWinner = $winnerPlacement < $loserPlacement;
  
  $thisPrefix = $horizontalPlayerIsWinner ? "winner_" : "loser_";
  $thisUserID = $row[$thisPrefix."user_id"];
  $thisName = $row[$thisPrefix."first_name"]." ".$row[$thisPrefix."last_name"];
  
  $otherPrefix = $horizontalPlayerIsWinner ? "loser_" : "winner_";
  $otherUserID = $row[$otherPrefix."user_id"];
  $otherName = $row[$otherPrefix."first_name"]." ".$row[$otherPrefix."last_name"];
  
  $horizontalPlacement = min($winnerPlacement, $loserPlacement);
  $table[$horizontalPlacement][$round]["id"] = $otherUserID;
  $table[$horizontalPlacement][$round]["name"] = $otherName;
  $table[$horizontalPlacement][$round]["result"] = $horizontalPlayerIsWinner;
  $placementInfo[$horizontalPlacement]["id"] = $thisUserID;
  $placementInfo[$horizontalPlacement]["name"] = $thisName;
  
  $verticalPlacement = max($winnerPlacement, $loserPlacement);
  $table[$verticalPlacement][$round]["id"] = $thisUserID;
  $table[$verticalPlacement][$round]["name"] = $thisName;
  $table[$verticalPlacement][$round]["result"] = !$horizontalPlayerIsWinner;
  $placementInfo[$verticalPlacement]["id"] = $otherUserID;
  $placementInfo[$verticalPlacement]["name"] = $otherName;
}

for ($placement = 1; $placement <= $tournament["player_count"]; $placement++)
{
  if (!@$placementInfo[$placement])
    continue;
  echo "<tr>";
  echo "<td>".$placement.". ".playerLink($placementInfo[$placement]["id"], $placementInfo[$placement]["name"])."</td>";
  for ($round = 1; $round <= $tournament["round_count"]; $round++)
  {
    echo "<td>";
    $cellData = @$table[$placement][$round];
    if ($cellData != NULL)
    {
      echo "<span class=\"".($cellData["result"] ? "winner" : "loser")."\">";
       echo $cellData["result"] ? "WIN" : "LOSS";
      echo "</span>";
      echo " ".playerLink($cellData["id"], $cellData["name"]);
    }
    echo "</td>";
  }
  echo "</tr>";
}
echo "</table>";
?>
