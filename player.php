<?php
$player = query("SELECT * from user where id=".escape($_GET["id"]))->fetch_assoc();
if (empty($player))
  die("Unknown player with id=".$_GET["id"]);

echo "<h1>".$player["first_name"]." ".$player["last_name"]."</h1>";
echo "<table>";
echo "<tr><td>Rating:</td><td>".$player["rating"]."</td></tr>";
echo "<tr><td>EGD Rating:</td><td>".$player["egd_rating"]."</td></tr>";
echo "</table>";

$games = query("SELECT
                 winner.id as winner_id,
                 winner.first_name as winner_first_name,
                 winner.last_name as winner_last_name,
                 winner.egd_pin as winner_egd_pin,
                 loser.id as loser_id,
                 loser.first_name as loser_first_name,
                 loser.last_name as loser_last_name,
                 loser.egd_pin as loser_egd_pin,
                 game_type.name as game_type_name,
                 game.location as game_location,
                 game.winner_comment as winner_comment,
                 game.loser_comment as loser_comment
               FROM game, user as winner, user as loser, game_type
               WHERE
                  game.winner_user_id = winner.id and
                  game.loser_user_id = loser.id and
                  game.game_type_id = game_type.id and
                 (winner_user_id=".escape($_GET["id"])." or loser_user_id=".escape($_GET["id"]).")");

if ($games->num_rows != 0)
{
  echo "<table class=\"data-table\">";
  echo   "<tr><th>Result</th><th>Opponent</th><th>Game type</th><th>Location</th><th>Comment</th><th>Opponent</th></tr>";
  while($row = $games->fetch_assoc())
  {
     echo "<tr>";
     $winner = ($row["winner_id"] == $_GET["id"]);
     $prefix = $winner ? "loser_" : "winner_";
     $myPrefix = $winner ? "winner_" : "loser_";
     echo "<td>".($winner ? "WIN" : "LOSS")."</td>";
     echo "<td>".playerLink($row[$prefix."id"], $row[$prefix."first_name"]." ".$row[$prefix."last_name"])."</td>";
     echo "<td>".$row["game_type_name"]."</td>";
     echo "<td>".$row["game_location"]."</td>";
     echo "<td>".$row[$myPrefix."comment"]."</td>";
     echo "<td>".$row[$prefix."comment"]."</td>";
     echo "</tr>";
  }
  echo "</table>";
}
else
  echo "No games."
?>
