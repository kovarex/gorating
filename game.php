<?php
$id = $_GET["id"];
$game = query("SELECT \n".
              " winner.id as winner_id,\n".
              " winner.name as winner_name,\n".
              " winner.username as winner_username,\n".
              " winner.rating as winner_rating,\n".
              " loser.id as loser_id,\n".
              " loser.name as loser_name,\n".
              " loser.username as loser_username,\n".
              " loser.rating as loser_rating,\n".
              " game.id as game_id,\n".
              " game.location as game_location,\n".
              " game.winner_comment as game_winner_comment,\n".
              " game.loser_comment as game_loser_comment,\n".
              " game.handicap as game_handicap,\n".
              " game.komi as game_komi,\n".
              " game.timestamp as game_timestamp,\n".
              " length(game.sgf) > 0 as has_sgf \n".
              "FROM \n".
              "  game \n".
              "    JOIN user AS winner ON game.winner_user_id=winner.id\n".
              "    JOIN user AS loser ON game.loser_user_id=loser.id\n".
              "WHERE game.id=".escape($id))->fetch_assoc();

if (!$game)
  return "Game not found";

echo "<table class=\"centered-data-table\">\n";
echo "<caption><a href=\"edit_game?id=".$game["game_id"]."&redirect=".urlencode("/game?id=".$game["game_id"])."\">Edit game</a></caption>";
echo "<tr><td>ID:</td><td>".$game["game_id"]."</td></tr>";
echo "<tr><td>Winner:</td><td>".playerLink($game, "winner")."</td></tr>";
echo "<tr><td>Loser:</td><td>".playerLink($game, "loser")."</td></tr>";
echo "<tr><td>Location:</td><td>".$game["game_location"]."</td></tr>";
echo "<tr><td>Winner comment:</td><td>".$game["game_winner_comment"]."</td></tr>";
echo "<tr><td>Loser comment:</td><td>".$game["game_loser_comment"]."</td></tr>";
echo "<tr><td>Handicap:</td><td>".$game["game_handicap"]."</td></tr>";
echo "<tr><td>Komi:</td><td>".$game["game_komi"]."</td></tr>";
echo "<tr><td>Time:</td><td>".date("d. m. Y H:i", strtotime($game["game_timestamp"]))."</td></tr>";
echo "<tr><td>SGF:</td><td>".($game["has_sgf"] ? SGFLink($game["game_id"]) : "not present")."</td></tr>";
echo "</table>\n";
?>
