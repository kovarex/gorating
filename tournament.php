<?php
$id = $_GET["id"];
if (empty($id))
{
  echo "ID not specified.";
  return;
}

$tournament = query("SELECT
                       egd_tournament.*,
                       country.name as country_name,
                       game_type.name as game_type_name
                     FROM
                       egd_tournament JOIN country ON egd_tournament.country_id = country.id
                                      JOIN game_type ON egd_tournament.game_type_id = game_type.id
                     WHERE egd_tournament.id=".escape($id))->fetch_assoc();
if (empty($tournament))
{
  echo "Tournament with id=".$id." not found.";
  return;
}

echo "<h1>".$tournament["name"]."</h1>";
echo "<table class=\"centered-table\">";
echo "<tr><td>EGD:</td><td>".egdTournamentLink($tournament["egd_key"])."</td></tr>";
echo "<tr><td>Date:</td><td>".date("d. m. Y", strtotime($tournament["timestamp"]))."</td></tr>";
echo "<tr><td>Type:</td><td>".$tournament["game_type_name"]."</td></tr>";
echo "<tr><td>Players:</td><td>".$tournament["player_count"]."</td></tr>";
echo "<tr><td>Country:</td><td>".$tournament["country_name"]."</td></tr>";
echo "<tr><td>City:</td><td>".$tournament["city"]."</td></tr>";
echo "</table><br/>";

$data = query("SELECT
                 game.id as game_id,
                 game.winner_user_id as winner_user_id,
                 winner.name as winner_name,
                 winner.username as winner_username,
                 game.winner_old_rating as winner_rating,
                 game.winner_old_egd_rating as winner_egd_rating,
                 game.loser_user_id as loser_user_id,
                 loser.name as loser_name,
                 loser.username as loser_username,
                 game.loser_old_rating as loser_rating,
                 game.loser_old_egd_rating as loser_egd_rating,
                 game.egd_tournament_round as round,
                 winner_result.placement as winner_placement,
                 loser_result.placement as loser_placement,
                 game.jigo as jigo,
                 length(game.sgf) > 0 as has_sgf
               FROM
                 game as game,
                 user as loser,
                 user as winner,
                 egd_tournament_result as winner_result,
                 egd_tournament_result as loser_result
               WHERE
                 game.deleted = false and
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
  $thisName = $row[$thisPrefix."name"];
  $thisUsername = $row[$thisPrefix."username"];

  $otherPrefix = $horizontalPlayerIsWinner ? "loser_" : "winner_";
  $otherUserID = $row[$otherPrefix."user_id"];
  $otherName = $row[$otherPrefix."name"];
  $otherUsername = $row[$otherPrefix."username"];

  $horizontalPlacement = min($winnerPlacement, $loserPlacement);
  $table[$horizontalPlacement][$round]["id"] = $otherUserID;
  $table[$horizontalPlacement][$round]["name"] = $otherName;
  $table[$horizontalPlacement][$round]["username"] = $otherUsername;
  $table[$horizontalPlacement][$round]["rating"] = $row[$otherPrefix."rating"];
  $table[$horizontalPlacement][$round]["egd_rating"] = $row[$otherPrefix."egd_rating"];
  $table[$horizontalPlacement][$round]["result"] = ($row["jigo"] ? "jigo" : $horizontalPlayerIsWinner);
  $table[$horizontalPlacement][$round]["has_sgf"] = $row["has_sgf"];
  $table[$horizontalPlacement][$round]["game_id"] = $row["game_id"];
  $placementInfo[$horizontalPlacement]["id"] = $thisUserID;
  $placementInfo[$horizontalPlacement]["name"] = $thisName;
  $placementInfo[$horizontalPlacement]["username"] = $thisUsername;
  $placementInfo[$horizontalPlacement]["rating"] = $row[$thisPrefix."rating"];
  $placementInfo[$horizontalPlacement]["egd_rating"] = $row[$thisPrefix."egd_rating"];

  $verticalPlacement = max($winnerPlacement, $loserPlacement);
  $table[$verticalPlacement][$round]["id"] = $thisUserID;
  $table[$verticalPlacement][$round]["name"] = $thisName;
  $table[$verticalPlacement][$round]["username"] = $thisUsername;
  $table[$verticalPlacement][$round]["rating"] = $row[$thisPrefix."rating"];
  $table[$verticalPlacement][$round]["egd_rating"] = $row[$thisPrefix."egd_rating"];
  $table[$verticalPlacement][$round]["result"] = ($row["jigo"] ? "jigo" : !$horizontalPlayerIsWinner);
  $table[$verticalPlacement][$round]["has_sgf"] = $row["has_sgf"];
  $table[$verticalPlacement][$round]["game_id"] = $row["game_id"];
  $placementInfo[$verticalPlacement]["id"] = $otherUserID;
  $placementInfo[$verticalPlacement]["name"] = $otherName;
  $placementInfo[$verticalPlacement]["username"] = $otherUsername;
  $placementInfo[$verticalPlacement]["rating"] = $row[$otherPrefix."rating"];
  $placementInfo[$verticalPlacement]["egd_rating"] = $row[$otherPrefix."egd_rating"];
}

for ($placement = 1; $placement <= $tournament["player_count"]; $placement++)
{
  if (!@$placementInfo[$placement])
    continue;
  echo "<tr>";
  $roundInfo = $placementInfo[$placement];

  echo "<td>".$placement.". ".playerLink($roundInfo)."</td>";
  for ($round = 1; $round <= $tournament["round_count"]; $round++)
  {
    echo "<td>";
    $cellData = @$table[$placement][$round];
    if ($cellData != NULL)
    {
      if (canEditAnyGame())
        echo "<a href=\"/edit_game?id=".$cellData["game_id"]."&redirect=".urlencode("/tournament?id=".$id)."\">Edit</a> ";
      if ($cellData["result"] === "jigo")
        echo "JIGO";
      else
      {
        echo "<span class=\"".($cellData["result"] ? "winner" : "loser")."\">";
        echo $cellData["result"] ? "WIN" : "LOSS";
        echo "</span>";
      }
      echo " ".playerLink($cellData);
      if ($cellData["has_sgf"])
        echo " ".SGFLink($cellData["game_id"]);
    }
    echo "</td>";
  }
  echo "</tr>";
}
echo "</table>";
?>
