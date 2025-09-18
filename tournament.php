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
$roundCount = $tournament["round_count"];
$lastRound = 100000;
echo "<table class=\"data-table\">";

echo "<tr>";
echo "<th>Placement</th>";
for ($i = 1; $i <= $roundCount; $i++)
  echo "<th>Round ".$i."</th>";
$placement = 0;

function processMissingCell($resultToRepeat, $lastRound, $placement)
{
  echo "<td>";
  $fromBefore = @$resultToRepeat[$lastRound][$placement];
  if (!empty($fromBefore))
  {
    echo "<span class=\"".($fromBefore["result"] ? "winner" : "loser")."\">";
     echo $fromBefore["result"] ? "WIN" : "LOSS";
    echo "</span>";
    echo " ".playerLink($fromBefore["opponent_id"], $fromBefore["opponent_name"]);
  }
  echo "</td>";
}

while ($row = $data->fetch_assoc())
{
  $round = $row["round"];
  $winnerPlacement = $row["winner_placement"];
  $loserPlacement = $row["loser_placement"];
  $horizontalPlayerIsWinner = $winnerPlacement < $loserPlacement;
  $horizontalPlacement = min($winnerPlacement, $loserPlacement);
  $verticalPlacement = max($winnerPlacement, $loserPlacement);
  
  $thisPrefix = $horizontalPlayerIsWinner ? "winner_" : "loser_";
  $otherPrefix = $horizontalPlayerIsWinner ? "loser_" : "winner_";
  
  $thisUserID = $row[$thisPrefix."user_id"];
  $otherUserID = $row[$thisPrefix."user_id"];
  
  $thisName = $row[$thisPrefix."first_name"]." ".$row[$thisPrefix."last_name"];
  $otherName = $row[$otherPrefix."first_name"]." ".$row[$otherPrefix."last_name"];
  
  if ($round < $lastRound)
  {
    while ($lastRound <= $roundCount)
    {
      processMissingCell($resultToRepeat, $lastRound, $placement);
      $lastRound++;
    }
    
    $lastRound = 1;
    $placement++;
    
    echo "</tr><tr>";
    echo "<td>".$horizontalPlacement.". ".playerLink($thisUserID, $thisName)."</td>";
  }

  while ($lastRound < $round)
  {
    processMissingCell($resultToRepeat, $lastRound, $placement);
    $lastRound++;
  }

  echo "<td>";
  echo "<span class=\"".($horizontalPlayerIsWinner ? "winner" : "loser")."\">";
  echo $horizontalPlayerIsWinner ? "WIN" : "LOSS";
  echo "</span>";
  echo " ".playerLink($otherUserID, $otherName);
  
  if ($verticalPlacement > $horizontalPlacement)
  {
    $dataToReuse["user_id"] = $otherUserID;
    $dataToReuse["user_name"] = $otherName;
    $dataToReuse["opponent_id"] = $thisUserID;
    $dataToReuse["opponent_name"] = $thisName;
    $dataToReuse["result"] = !$horizontalPlayerIsWinner;
    $resultToRepeat[$round][$verticalPlacement] = $dataToReuse;
  }
  echo "</td>";
  
  $lastRound++;
}

while ($lastRound <= $roundCount)
{
  processMissingCell($resultToRepeat, $lastRound, $placement);
  $lastRound++;
}

for ($placement = $placement + 1;$placement <= $tournament["player_count"]; ++$placement)
{
  echo "<tr>";
  $fromBefore = NULL;
  for ($i = 1; $i <= $roundCount and empty($fromBefore); $i++)
    $fromBefore = @$resultToRepeat[$i][$placement];
  echo "<td>".$placement.". ".playerLink($fromBefore["user_id"], $fromBefore["user_name"])."</td>";
  
  for ($i = 1; $i < $roundCount; $i++)
    processMissingCell($resultToRepeat, $i, $placement);
  echo "</tr>";
}
  
echo "</tr>";
echo "</table>";
?>
