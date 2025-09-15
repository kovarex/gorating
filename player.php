<?php
$player = query("SELECT * from user where id=".escape($_GET["id"]))->fetch_assoc();
if (empty($player))
  die("Unknown player with id=".$_GET["id"]);

echo "<h1>".$player["first_name"]." ".$player["last_name"]."</h1>";
echo "<table>";
echo "<tr><td>Rating:</td><td>".round($player["rating"])."</td></tr>";
echo "<tr><td>EGD Rating:</td><td>".$player["egd_rating"]."</td></tr>";
echo "</table>";

$games = query("SELECT
                 game.id as game_id,
                 winner.id as winner_id,
                 winner.first_name as winner_first_name,
                 winner.last_name as winner_last_name,
                 winner.egd_pin as winner_egd_pin,
                 game.winner_old_rating as winner_old_rating,
                 game.winner_new_rating as winner_new_rating,
                 loser.id as loser_id,
                 loser.first_name as loser_first_name,
                 loser.last_name as loser_last_name,
                 loser.egd_pin as loser_egd_pin,
                 game.loser_old_rating as loser_old_rating,
                 game.loser_new_rating as loser_new_rating,
                 game_type.name as game_type_name,
                 game.location as game_location,
                 game.winner_comment as winner_comment,
                 game.loser_comment as loser_comment,
                 game.timestamp as game_timestamp,
                 length(game.sgf) > 0 as has_sgf
               FROM game, user as winner, user as loser, game_type
               WHERE
                 game.winner_user_id = winner.id and
                 game.loser_user_id = loser.id and
                 game.game_type_id = game_type.id and
                 (winner_user_id=".escape($_GET["id"])." or loser_user_id=".escape($_GET["id"]).")
               ORDER BY game.timestamp DESC");

if ($games->num_rows != 0)
{
  echo "<table class=\"data-table\">";
  echo   "<tr><th>Result</th><th>Rating change</th><th>Opponent</th><th>Game type</th><th>Time</th><th>Location</th><th>Comment</th><th>Opponent</th><th>SGF</th></tr>";
  while($row = $games->fetch_assoc())
  {
     echo "<tr>";
     $winner = ($row["winner_id"] == $_GET["id"]);
     $prefix = $winner ? "loser_" : "winner_";
     $myResultName = $winner ? "winner" : "loser";
     $myPrefix = $myResultName."_";
     echo "<td style=\"text-align:center;\">".($winner ? "WIN" : "LOSS")."</td>";
     echo "<td style=\"text-align:center;\"><span class=\"".$myResultName."\">".round($row[$myPrefix."old_rating"])."&rarr;".round($row[$myPrefix."new_rating"])."</span></td>";
     echo "<td>".playerLink($row[$prefix."id"], $row[$prefix."first_name"]." ".$row[$prefix."last_name"])." (".round($row[$prefix."new_rating"]).")</td>";
     echo "<td>".$row["game_type_name"]."</td>";
     echo "<td>".date("d. m. Y H:i", strtotime($row["game_timestamp"]))."</td>";
     echo "<td style=\"text-align:center;\">".$row["game_location"]."</td>";
     echo "<td style=\"text-align:center;\">".$row[$myPrefix."comment"]."</td>";
     echo "<td style=\"text-align:center;\">".$row[$prefix."comment"]."</td>";

     echo "<td>";
     if ($row["has_sgf"])
       echo "<a href=\"/sgf?id=".$row["game_id"]."\">SGF</a>";
     echo "</td>";

     echo "</tr>";
  }
  echo "</table>";
}
else
  echo "No games."
?>
