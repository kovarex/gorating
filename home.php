<?php
require_once("src/tournament_helper.php");
require_once("src/rating_helper.php");

function showLatestGames($limit = NULL, $caption = NULL)
{
  $table = new TableViewer("   game
                                 JOIN user as winner ON game.winner_user_id = winner.id
                                 JOIN user as loser ON game.loser_user_id = loser.id
                                 JOIN game_type ON game.game_type_id = game_type.id
                             WHERE
                               game.deleted = false and
                               game.egd_tournament_id IS NULL",
                           $_GET);
  $table->limit = $limit;
  $table->caption = $caption;
  $table->tableClass = "centered-data-table";

  $table->addColumn("black",
                    "Black",
                    array(array("IF(game.winner_is_black, winner.id, loser.id)", "black_id"),
                          array("IF(game.winner_is_black, winner.name, loser.name)", "black_name"),
                          array("IF(game.winner_is_black, winner.username, loser.username)", "black_username"),
                          array("IF(game.winner_is_black, winner.rating, loser.rating)", "black_rating"),
                          array("game.winner_is_black", "winner_is_black"),
                          array("IF(game.jigo, 'JIGO', IF(game.winner_is_black, 'WIN', 'LOSS'))", "black_result")),
                    function($row) { echo showWinOrLoss($row["black_result"])." ".playerLink($row, "black"); });

  $table->addColumn("white",
                    "White",
                    array(array("IF(game.winner_is_black, loser.id, winner.id)", "white_id"),
                          array("IF(game.winner_is_black, loser.name, winner.name)", "white_name"),
                          array("IF(game.winner_is_black, loser.username, winner.username)", "white_username"),
                          array("IF(game.winner_is_black, loser.rating, winner.rating)", "white_rating"),
                          array("IF(game.jigo, 'JIGO', IF(game.winner_is_black, 'LOSS', 'WIN'))", "white_result")),
                    function($row) { echo showWinOrLoss($row["white_result"])." ".playerLink($row, "white"); });

  $table->addColumn("handicap",
                    "Handicap",
                    array(array("game.handicap", "handicap"),
                          array("game.komi", "komi")),
                    function($row) { echo showHandicap($row["handicap"], $row["komi"]); },
                    "style=\"text-align:center;\"");

  $table->addColumn("game_type_name",
                    "Game type",
                    array(array("game_type.name", "game_type_name")),
                    function($row) { echo $row["game_type_name"]; },
                    "style=\"text-align:center;\"");

  $table->addColumn("timestamp",
                    "Date",
                    array(array("game.timestamp", "timestamp")),
                    function($row) { echo date("d. m. Y H:i", strtotime($row["timestamp"])); },
                    "style=\"text-align:center;\"");

  $table->addColumn("has_sgf",
                    "SGF",
                    array(array("length(game.sgf) > 0", "has_sgf"),
                          array("game.id", "game_id")),
                    function($row){ if ($row["has_sgf"]) echo "<a href=\"/sgf?id=".$row["game_id"]."\">SGF</a>"; });

  $table->setLastSort(new SortDefinition("timestamp", false));
  $table->render();
}

showLatestGames(10, "<h2>Latest friendly games</h2>");
showTournaments(10, "<h2>Latest tournaments</h2>");
?>
