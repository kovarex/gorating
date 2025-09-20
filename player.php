<?php
$player = query("SELECT * from user where id=".escape($_GET["id"]))->fetch_assoc();
if (empty($player))
  die("Unknown player with id=".$_GET["id"]);

echo "<h1>".$player["first_name"]." ".$player["last_name"]."</h1>";
echo "<table class=\"centered-table\">";
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
                 game.winner_old_egd_rating as winner_old_egd_rating,
                 game.winner_new_egd_rating as winner_new_egd_rating,
                 loser.id as loser_id,
                 loser.first_name as loser_first_name,
                 loser.last_name as loser_last_name,
                 loser.egd_pin as loser_egd_pin,
                 game.loser_old_rating as loser_old_rating,
                 game.loser_new_rating as loser_new_rating,
                 game.loser_old_egd_rating as loser_old_egd_rating,
                 game.loser_new_egd_rating as loser_new_egd_rating,
                 game_type.name as game_type_name,
                 game.location as game_location,
                 game.winner_comment as winner_comment,
                 game.loser_comment as loser_comment,
                 game.timestamp as game_timestamp,
                 game.winner_is_black as winner_is_black,
                 game.handicap as handicap,
                 game.komi as komi,
                 length(game.sgf) > 0 as has_sgf,
                 egd_tournament.egd_key as egd_tournament_key,
                 egd_tournament.name as egd_tournament_name,
                 egd_tournament.id as egd_tournament_id
               FROM game LEFT JOIN egd_tournament ON game.egd_tournament_id = egd_tournament.id,
                    user as winner,
                    user as loser,
                    game_type
               WHERE
                 game.winner_user_id = winner.id and
                 game.loser_user_id = loser.id and
                 game.game_type_id = game_type.id and
                 (winner_user_id=".escape($_GET["id"])." or loser_user_id=".escape($_GET["id"]).")
               ORDER BY
                 game.timestamp DESC,
                 game.egd_tournament_round DESC");

if ($games->num_rows != 0)
{
  echo "<table class=\"data-table\">";
  echo   "<tr><th>Result</th><th>Rating change</th><th>Opponent</th><th>Game type</th><th>Color</th><th>Handicap</th><th>Time</th><th>Tournament</th><th>Location</th><th>Comment</th><th>Opponent</th><th>SGF</th></tr>";
  while($row = $games->fetch_assoc())
  {
     echo "<tr>";
     $winner = ($row["winner_id"] == $_GET["id"]);
     $prefix = $winner ? "loser_" : "winner_";
     $myResultName = $winner ? "winner" : "loser";
     $myPrefix = $myResultName."_";
     echo "<td style=\"text-align:center;\">".($winner ? "WIN" : "LOSS")."</td>";
     echo "<td style=\"text-align:center;\">";

     $suffix = "_rating";
     if (empty($row[$myPrefix."old_rating"]))
     {
       $suffix = "_egd_rating";
       echo "(EGD) ";
     }

     echo   "<span class=\"".$myResultName."\">".round($row[$myPrefix."old".$suffix])."&rarr;".round($row[$myPrefix."new".$suffix])."</span>";
     echo "</td>";

     $ratingToShow = $row[$prefix."new_rating"];
     if (empty($ratingToShow))
       $ratingToShow = $row[$prefix."new_egd_rating"];
     echo "<td>".playerLink($row[$prefix."id"], $row[$prefix."first_name"]." ".$row[$prefix."last_name"])." (".round($ratingToShow).")</td>";
     echo "<td style=\"text-align:center;\">".$row["game_type_name"]."</td>";
     echo "<td style=\"text-align:center;\">".(boolval($winner) == boolval($row["winner_is_black"]) ? "Black" : "White")."</td>";
     echo "<td style=\"text-align:center;\">";
       if ($row["handicap"] == 0 and ($row["komi"] == 6.5 or $row["komi"] == 7.5))
         echo "Even";
       else
       {
         if ($row["handicap"] != 0)
         {
           echo $row["handicap"]."h";
           if ($row["komi"] != 0.5)
             echo " komi ".$row["komi"];
         }
         else
           echo "komi ".$row["komi"];
       }
     echo "</td>";
     echo "<td>".date("d. m. Y H:i", strtotime($row["game_timestamp"]))."</td>";

     echo "<td>";
     if (!empty($row["egd_tournament_id"]))
     {
       $shortenedTournamentName = substr(readableTournamentName($row["egd_tournament_name"]), 0, 30);
       echo tournamentLink($row["egd_tournament_id"], $shortenedTournamentName);
     }
     echo "</td>";

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
